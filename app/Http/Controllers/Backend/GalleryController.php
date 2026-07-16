<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Gallery\StoreEditorGalleryRequest;
use App\Http\Requests\Backend\Gallery\StoreGalleryRequest;
use App\Http\Requests\Backend\Gallery\UpdateGalleryRequest;
use App\Models\Gallery;
use App\Services\GalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GalleryController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(protected GalleryService $galleryService) {}

    public function index(): View
    {
        $orgId = $this->currentOrgId();
        $stats = $this->galleryService->stats($orgId);

        return view('backend.gallery.index', [
            'stats' => $stats,
            'perPageOptions' => config('gallery.per_page_options', [12, 24, 48, 96]),
            'endpoints' => [
                'list' => route('admin.gallery.data'),
                'store' => route('admin.gallery.store'),
                'commit' => route('admin.gallery.commit'),
                'bulkDelete' => route('admin.gallery.bulk-delete'),
                'bulkRestore' => route('admin.gallery.bulk-restore'),
                'bulkForceDelete' => route('admin.gallery.bulk-force-delete'),
                'stats' => route('admin.gallery.stats'),
                'editBase' => url('/admin/gallery'),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $orgId = $this->currentOrgId();
        $paginator = $this->galleryService->paginate($orgId, [
            'search' => $request->string('search')->toString(),
            'kind' => $request->string('kind')->toString() ?: 'all',
            'sort' => $request->string('sort')->toString() ?: 'newest',
            'trash' => $request->string('trash')->toString() ?: 'active',
            'folder' => $request->string('folder')->toString(),
            'source' => $request->string('source')->toString(),
            'per_page' => $request->integer('per_page', config('gallery.per_page_default', 24)),
            'page' => $request->integer('page', 1),
        ]);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (Gallery $item) => $this->galleryService->toArray($item))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'stats' => $this->galleryService->stats($orgId),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'stats' => $this->galleryService->stats($this->currentOrgId()),
        ]);
    }

    public function store(StoreGalleryRequest $request): JsonResponse
    {
        $orgId = $this->currentOrgId();
        $created = $this->galleryService->uploadMany($request->uploadedFiles(), $orgId, [
            'source' => $request->input('source', 'gallery'),
            'module' => $request->input('module', 'gallery'),
            'alt_text' => $request->input('alt_text'),
            'description' => $request->input('description'),
        ]);

        return response()->json([
            'message' => count($created) === 1
                ? 'File uploaded successfully.'
                : count($created).' files uploaded successfully.',
            'data' => array_map(fn (Gallery $item) => $this->galleryService->toArray($item), $created),
            'stats' => $this->galleryService->stats($orgId),
        ], 201);
    }

    /**
     * Permanently save one staged file from the gallery pending queue.
     * When "original" is provided, stores both paths on a single row.
     */
    public function commit(\App\Http\Requests\Backend\Gallery\CommitGalleryRequest $request): JsonResponse
    {
        $orgId = $this->currentOrgId();
        $file = $request->file('file');
        $original = $request->file('original');
        $meta = [
            'source' => $request->input('source', 'gallery'),
            'module' => $request->input('module', 'gallery'),
            'alt_text' => $request->input('alt_text'),
        ];

        if ($original instanceof \Illuminate\Http\UploadedFile && $original->isValid()) {
            $gallery = $this->galleryService->upload($original, $orgId, array_merge($meta, [
                'kind' => 'image',
                'original_name' => $original->getClientOriginalName() ?: $file->getClientOriginalName(),
            ]));
            $gallery = $this->galleryService->saveModifiedFile($gallery, $file);
        } else {
            $gallery = $this->galleryService->upload($file, $orgId, $meta);
        }

        return response()->json([
            'message' => 'File saved successfully.',
            'data' => $this->galleryService->toArray($gallery),
            'stats' => $this->galleryService->stats($orgId),
        ], 201);
    }

    /**
     * Rich-text editor upload — stores gallery record(s) and returns TinyMCE location.
     * For images with an original file, creates original + adjusted rows.
     */
    public function storeEditor(StoreEditorGalleryRequest $request): JsonResponse
    {
        $orgId = $this->currentOrgId();
        $kind = (string) $request->input('kind', 'file');
        $file = $request->file('file');
        $original = $request->file('original');

        $pair = $this->galleryService->uploadForEditor($file, $orgId, $original, [
            'kind' => $kind === 'file' ? null : $kind,
            'display_name' => $request->input('display_name'),
            'module' => $request->input('module', 'editor'),
            'source' => 'editor',
        ]);

        return response()->json($this->galleryService->editorUploadResponse($pair));
    }

    public function show(int $id): JsonResponse
    {
        $gallery = $this->findOrFailScoped($id, true);

        return response()->json([
            'data' => $this->galleryService->toArray($gallery),
        ]);
    }

    public function update(UpdateGalleryRequest $request, int $id): JsonResponse
    {
        $gallery = $this->findOrFailScoped($id);

        if ($request->filled('original_name')) {
            $gallery = $this->galleryService->rename($gallery, (string) $request->input('original_name'));
        }

        if ($request->hasAny(['alt_text', 'description', 'status'])) {
            $gallery = $this->galleryService->updateMeta($gallery, $request->validated());
        }

        return response()->json([
            'message' => 'File updated successfully.',
            'data' => $this->galleryService->toArray($gallery),
        ]);
    }

    /**
     * Save a client-side edited image as modified_file_path (keeps original intact).
     */
    public function saveEdit(\App\Http\Requests\Backend\Gallery\SaveGalleryEditRequest $request, int $id): JsonResponse
    {
        $gallery = $this->findOrFailScoped($id);
        abort_unless($gallery->isImage(), 422, 'Only images can be edited.');

        $gallery = $this->galleryService->saveModifiedFile($gallery, $request->file('file'));

        if ($request->filled('alt_text')) {
            $gallery = $this->galleryService->updateMeta($gallery, [
                'alt_text' => $request->input('alt_text'),
            ]);
        }

        return response()->json([
            'message' => 'Edited image saved.',
            'data' => $this->galleryService->toArray($gallery),
            'stats' => $this->galleryService->stats($this->currentOrgId()),
        ]);
    }

    /**
     * Discard modified version and display the original again.
     */
    public function revert(int $id): JsonResponse
    {
        $gallery = $this->findOrFailScoped($id);
        abort_unless($gallery->hasModification(), 422, 'This file has no edited version.');

        $gallery = $this->galleryService->revertToOriginal($gallery);

        return response()->json([
            'message' => 'Reverted to original image.',
            'data' => $this->galleryService->toArray($gallery),
            'stats' => $this->galleryService->stats($this->currentOrgId()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $gallery = $this->findOrFailScoped($id);
        $this->galleryService->softDelete($gallery);

        return response()->json([
            'message' => 'File moved to bin.',
            'stats' => $this->galleryService->stats($this->currentOrgId()),
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $gallery = $this->findOrFailScoped($id, true);
        abort_unless($gallery->trashed(), 422, 'File is not in the bin.');

        $restored = $this->galleryService->restore($gallery);

        return response()->json([
            'message' => 'File restored successfully.',
            'data' => $this->galleryService->toArray($restored),
            'stats' => $this->galleryService->stats($this->currentOrgId()),
        ]);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $gallery = $this->findOrFailScoped($id, true);
        $this->galleryService->forceDelete($gallery);

        return response()->json([
            'message' => 'File permanently deleted.',
            'stats' => $this->galleryService->stats($this->currentOrgId()),
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request);
        $count = $this->galleryService->softDeleteMany($this->currentOrgId(), $ids);

        return response()->json([
            'message' => "{$count} file(s) moved to bin.",
            'stats' => $this->galleryService->stats($this->currentOrgId()),
        ]);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request);
        $count = $this->galleryService->restoreMany($this->currentOrgId(), $ids);

        return response()->json([
            'message' => "{$count} file(s) restored.",
            'stats' => $this->galleryService->stats($this->currentOrgId()),
        ]);
    }

    public function bulkForceDelete(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request);
        $count = $this->galleryService->forceDeleteMany($this->currentOrgId(), $ids);

        return response()->json([
            'message' => "{$count} file(s) permanently deleted.",
            'stats' => $this->galleryService->stats($this->currentOrgId()),
        ]);
    }

    public function download(Request $request, int $id): StreamedResponse
    {
        $gallery = $this->findOrFailScoped($id, true);
        $disk = $gallery->disk ?: 'public';
        $variant = $request->string('variant')->toString() ?: 'display';

        $path = match ($variant) {
            'original' => $gallery->original_file_path ?: $gallery->file_path,
            'modified' => $gallery->modified_file_path ?: $gallery->file_path,
            default => $gallery->displayPath(),
        };

        abort_unless(
            $path && \Illuminate\Support\Facades\Storage::disk($disk)->exists($path),
            404,
            'File not found on disk.'
        );

        $downloadName = $gallery->original_name;
        if ($variant === 'original' && $gallery->hasModification()) {
            $downloadName = 'original_'.$gallery->original_name;
        }

        return \Illuminate\Support\Facades\Storage::disk($disk)->download($path, $downloadName);
    }

    protected function findOrFailScoped(int $id, bool $withTrashed = false): Gallery
    {
        $query = Gallery::query()->forOrg($this->currentOrgId());
        if ($withTrashed) {
            $query->withTrashed();
        }

        $gallery = $query->findOrFail($id);
        abort_if($gallery->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this file.');

        return $gallery;
    }

    /**
     * @return list<int>
     */
    protected function validatedIds(Request $request): array
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        return array_values(array_unique(array_map('intval', $validated['ids'])));
    }
}
