<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class QuestionService
{
    public function getByOrganization(int $orgId, int $perPage = 20): LengthAwarePaginator
    {
        return Question::where('organization_id', $orgId)
            ->with(['category', 'createdBy'])
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Question
    {
        $data['created_by'] = Auth::id();
        $this->normalizeAnswers($data);

        return Question::create($data);
    }

    public function update(Question $question, array $data): Question
    {
        $this->normalizeAnswers($data);
        $question->update($data);

        return $question->fresh();
    }

    public function delete(Question $question): bool
    {
        return $question->delete();
    }

    public function getCategoriesForOrg(int $orgId): Collection
    {
        return Category::where('organization_id', $orgId)->orderBy('name')->get();
    }

    public function getStats(int $orgId): array
    {
        return [
            'total' => Question::where('organization_id', $orgId)->count(),
            'by_category' => Question::where('organization_id', $orgId)
                ->selectRaw('category_id, COUNT(*) as aggregate')
                ->groupBy('category_id')
                ->get()
                ->load('category'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function normalizeAnswers(array &$data): void
    {
        if (! empty($data['allows_multiple'])) {
            $data['correct_answers'] = array_values(array_filter($data['correct_answers'] ?? []));
            $data['correct_answer'] = (string) ($data['correct_answers'][0] ?? '');
        } else {
            $data['correct_answers'] = null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @return array<int, array{text: string, image_path: ?string}>
     */
    public function normalizeOptionsFromRequest(array $options, Request $request): array
    {
        $out = [];
        foreach ($options as $i => $row) {
            $text = is_array($row) ? (string) ($row['text'] ?? '') : '';
            $imagePath = is_array($row) ? ($row['image_path'] ?? null) : null;
            if ($request->hasFile("options.{$i}.image")) {
                $imagePath = $request->file("options.{$i}.image")->store('question-options', 'public');
            }
            $out[] = [
                'text' => $text,
                'image_path' => $imagePath,
            ];
        }

        return $out;
    }
}
