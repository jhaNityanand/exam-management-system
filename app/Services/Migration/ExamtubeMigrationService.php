<?php

namespace App\Services\Migration;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Cms\BlogComment;
use App\Models\Cms\NewsletterSubscriber;
use App\Models\Organization;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserOrganization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ExamtubeMigrationService
{
    /** @var array<int, int> Old Category ID => New BlogCategory ID */
    protected array $categoryMap = [];

    /** @var array<int, int> Old Blog ID => New Blog ID */
    protected array $blogMap = [];

    /** @var array<int, int> Old User ID => New User ID */
    protected array $userMap = [];

    /** @var array<string, int> Cleaned User Name (lowercase) => New User ID */
    protected array $userMapByName = [];

    public function __construct(
        protected LegacySqlParser $parser,
        protected ContentEnhancer $contentEnhancer,
        protected SeoEnhancer $seoEnhancer,
        protected LegacyImageImporter $imageImporter,
        protected ImportLogger $logger
    ) {}

    /**
     * Run the full Examtube migration pipeline.
     */
    public function migrate(?string $customSqlPath = null, ?Command $command = null, int $organizationId = 1): ImportLogger
    {
        @ini_set('memory_limit', '512M');

        $sqlPath = $this->parser->findSqlFilePath($customSqlPath);
        $this->logger->writeLog("Starting legacy migration using SQL file: {$sqlPath}");

        // Ensure target organization exists
        $org = Organization::query()->find($organizationId) ?: Organization::query()->first();
        if (! $org) {
            throw new \RuntimeException("No Organization found in database to attach imported records.");
        }
        $organizationId = $org->id;

        // 1. Parse SQL file
        $sqlData = $this->parser->parseFile($sqlPath);

        // 2. Migrate Users & Profiles
        if (isset($sqlData['users'])) {
            $this->migrateUsers($sqlData['users'], $sqlData['profile'] ?? [], $organizationId);
        }

        $authorUser = User::query()->where('email', 'info@examtube.in')->first()
            ?: User::query()->where('email', 'vidyanand.in3@gmail.com')->first()
            ?: User::query()->first();

        // 3. Migrate Categories
        if (isset($sqlData['categories'])) {
            $this->migrateCategories($sqlData['categories'], $organizationId, $authorUser?->id);
        }

        // 4. Migrate Blogs (and inline Tags)
        if (isset($sqlData['blogs'])) {
            $this->migrateBlogs($sqlData['blogs'], $organizationId, $authorUser?->id);
        }

        // 5. Migrate Comments
        if (isset($sqlData['comments'])) {
            $this->migrateComments($sqlData['comments'], $organizationId);
        }

        // 6. Migrate Newsletter Emails
        if (isset($sqlData['email'])) {
            $this->migrateNewsletterEmails($sqlData['email'], $organizationId);
        }

        // 7. Migrate Standalone Media (content_image)
        if (isset($sqlData['content_image'])) {
            $this->migrateContentImages($sqlData['content_image'], $organizationId);
        }

        $this->logger->displaySummary($command);

        return $this->logger;
    }

    /**
     * Migrate old Users and Profiles into User, UserOrganization, and Profile.
     *
     * @param  list<array<string, mixed>>  $users
     * @param  list<array<string, mixed>>  $profiles
     */
    protected function migrateUsers(array $users, array $profiles, int $organizationId): void
    {
        $this->logger->startTable('users', count($users));

        // Index profiles by old user_id
        $profileByUserId = [];
        foreach ($profiles as $prof) {
            $uId = (int) ($prof['user_id'] ?? 0);
            if ($uId > 0) {
                $profileByUserId[$uId] = $prof;
            }
        }

        foreach ($users as $oldUser) {
            $oldId = (int) ($oldUser['id'] ?? 0);
            $email = strtolower(trim((string) ($oldUser['email'] ?? '')));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->logger->recordSkipped('users', "Invalid email for user ID {$oldId}: '{$email}'");
                continue;
            }

            try {
                $name = mb_substr($this->contentEnhancer->enhanceTitle((string) ($oldUser['name'] ?? 'User')), 0, 185);
                $oldRole = strtolower(trim((string) ($oldUser['role'] ?? 'user')));
                $newRole = match ($oldRole) {
                    'admin', 'org_admin' => 'org_admin',
                    default => 'candidate',
                };

                $user = User::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => Hash::make('password'),
                        'email_verified_at' => ! empty($oldUser['email_verified_at']) ? $oldUser['email_verified_at'] : now(),
                    ]
                );

                $this->userMap[$oldId] = $user->id;
                $this->userMapByName[strtolower($name)] = $user->id;

                // Attach to Organization
                UserOrganization::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'organization_id' => $organizationId,
                    ],
                    [
                        'role' => $newRole,
                        'status' => 'active',
                        'is_primary' => true,
                    ]
                );

                // Handle Profile
                $profData = $profileByUserId[$oldId] ?? null;
                $bio = $profData ? $this->contentEnhancer->enhanceHtml((string) ($profData['message'] ?? '')) : null;

                $avatarGallery = null;
                if ($profData && ! empty($profData['image'])) {
                    $avatarGallery = $this->imageImporter->importImage((string) $profData['image'], $organizationId, [
                        'source' => 'import',
                        'module' => 'profile',
                        'folder' => 'users',
                        'alt_text' => $name,
                    ]);
                }

                $socialLinks = [];
                if ($profData) {
                    foreach (['facebook', 'instagram', 'linkedin', 'twitter', 'youtube', 'gmail', 'telegram'] as $key) {
                        $actualKey = $key === 'linkedin' ? 'linkdin' : $key;
                        if (! empty($profData[$actualKey])) {
                            $socialLinks[$key] = trim((string) $profData[$actualKey]);
                        }
                    }
                }

                Profile::query()->updateOrCreate(
                    ['id' => $user->id],
                    [
                        'status' => 'active',
                        'bio' => $bio ? mb_substr(strip_tags($bio), 0, 1000) : null,
                        'avatar' => $avatarGallery?->file_path ?: $avatarGallery?->file_url,
                        'default_organization_id' => $organizationId,
                        'social_links' => ! empty($socialLinks) ? $socialLinks : null,
                    ]
                );

                $this->logger->recordSuccess('users');
            } catch (\Throwable $e) {
                $this->logger->recordFailed('users', "User ID {$oldId} failed: " . $e->getMessage());
            }
        }

        $this->logger->endTable('users');
    }

    /**
     * Migrate Categories to BlogCategory table.
     *
     * @param  list<array<string, mixed>>  $categories
     */
    protected function migrateCategories(array $categories, int $organizationId, ?int $authorId): void
    {
        $this->logger->startTable('blog_categories', count($categories));

        foreach ($categories as $oldCat) {
            $oldId = (int) ($oldCat['id'] ?? 0);
            $rawName = (string) ($oldCat['name'] ?? '');
            if ($rawName === '') {
                $this->logger->recordSkipped('blog_categories', "Category ID {$oldId} has no name.");
                continue;
            }

            try {
                $name = mb_substr($this->contentEnhancer->enhanceTitle($rawName), 0, 185);
                $rawSlug = ! empty($oldCat['url']) ? Str::slug($oldCat['url']) : Str::slug($name);

                $existingCat = BlogCategory::query()
                    ->where('organization_id', $organizationId)
                    ->where(function ($q) use ($name, $rawSlug) {
                        $q->where('name', $name)->orWhere('slug', $rawSlug);
                    })
                    ->first();

                $slug = $existingCat ? $existingCat->slug : $this->generateUniqueSlug(BlogCategory::class, $rawSlug, $organizationId);

                $desc = $this->contentEnhancer->enhanceHtml((string) ($oldCat['description'] ?? ''));
                $status = strtolower((string) ($oldCat['status'] ?? 'Active')) === 'active' ? 'active' : 'inactive';

                $seo = $this->seoEnhancer->enhanceCategorySeo([
                    'name' => $name,
                    'description' => $desc,
                    'slug' => $slug,
                ], config('app.url', 'https://examtube.in'));

                $coverGallery = null;
                if (! empty($oldCat['image'])) {
                    $coverGallery = $this->imageImporter->importImage((string) $oldCat['image'], $organizationId, [
                        'source' => 'import',
                        'module' => 'blog',
                        'folder' => 'category',
                        'alt_text' => $name,
                    ]);
                }

                $category = BlogCategory::query()->updateOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'name' => $name,
                    ],
                    [
                        'slug' => $slug,
                        'description' => $desc,
                        'status' => $status,
                        'meta_title' => $seo['meta_title'],
                        'meta_description' => $seo['meta_description'],
                        'meta_keywords' => $seo['meta_keywords'],
                        'canonical_url' => $seo['canonical_url'],
                        'og_title' => $seo['og_title'],
                        'og_description' => $seo['og_description'],
                        'og_image_id' => $coverGallery?->id,
                        'robots' => $seo['robots'],
                        'created_by' => $authorId,
                        'updated_by' => $authorId,
                    ]
                );

                $this->categoryMap[$oldId] = $category->id;
                $this->logger->recordSuccess('blog_categories');
            } catch (\Throwable $e) {
                $this->logger->recordFailed('blog_categories', "Category ID {$oldId} failed: " . $e->getMessage());
            }
        }

        $this->logger->endTable('blog_categories');
    }

    /**
     * Migrate Blogs to Blog model and handle Tags.
     *
     * @param  list<array<string, mixed>>  $blogs
     */
    protected function migrateBlogs(array $blogs, int $organizationId, ?int $authorId): void
    {
        $this->logger->startTable('blogs', count($blogs));

        foreach ($blogs as $oldBlog) {
            $oldId = (int) ($oldBlog['id'] ?? 0);
            $rawTitle = (string) ($oldBlog['title'] ?? '');
            if ($rawTitle === '') {
                $this->logger->recordSkipped('blogs', "Blog ID {$oldId} has no title.");
                continue;
            }

            try {
                $title = mb_substr($this->contentEnhancer->enhanceTitle($rawTitle), 0, 185);
                $rawSlug = ! empty($oldBlog['url']) ? Str::slug($oldBlog['url']) : Str::slug($title);
                $rawSlug = mb_substr($rawSlug, 0, 180);

                // Check existing by exact title or slug
                $existingBlog = Blog::query()
                    ->where('organization_id', $organizationId)
                    ->where(function ($q) use ($title, $rawSlug) {
                        $q->where('title', $title)->orWhere('slug', $rawSlug);
                    })
                    ->first();

                $slug = $existingBlog ? $existingBlog->slug : $this->generateUniqueSlug(Blog::class, $rawSlug, $organizationId);

                // Clean content and rewrite embedded images
                $rawContent = (string) ($oldBlog['content'] ?? '');
                $cleanedHtml = $this->contentEnhancer->enhanceHtml($rawContent);
                $rewritten = $this->imageImporter->rewriteContentImages($cleanedHtml, $organizationId);
                $finalContent = $rewritten['content'];

                $excerpt = $this->contentEnhancer->generateExcerpt($finalContent);
                $status = strtolower((string) ($oldBlog['status'] ?? 'Active')) === 'active' ? 'published' : 'draft';
                $oldCatId = (int) ($oldBlog['category_id'] ?? 0);
                $newCatId = $this->categoryMap[$oldCatId] ?? null;

                // Import cover/banner image
                $bannerGallery = null;
                if (! empty($oldBlog['image'])) {
                    $bannerGallery = $this->imageImporter->importImage((string) $oldBlog['image'], $organizationId, [
                        'source' => 'import',
                        'module' => 'blog',
                        'folder' => 'blog',
                        'alt_text' => $title,
                    ]);
                }

                // Enhance SEO
                $seo = $this->seoEnhancer->enhanceBlogSeo([
                    'title' => $title,
                    'content' => $finalContent,
                    'slug' => $slug,
                    'meta_title' => $oldBlog['meta_title'] ?? '',
                    'meta_description' => $oldBlog['meta_description'] ?? '',
                    'tags' => $oldBlog['tags'] ?? '',
                ], config('app.url', 'https://examtube.in'));

                $createdAt = ! empty($oldBlog['created_at']) ? $oldBlog['created_at'] : now();
                $updatedAt = ! empty($oldBlog['updated_at']) ? $oldBlog['updated_at'] : now();

                $authorName = ! empty($oldBlog['author']) ? mb_substr(trim((string) $oldBlog['author']), 0, 185) : 'Examtube Team';
                $resolvedAuthorId = $this->userMapByName[strtolower($authorName)] ?? $authorId;

                $blog = Blog::query()->updateOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'slug' => $slug,
                    ],
                    [
                        'blog_category_id' => $newCatId,
                        'title' => $title,
                        'excerpt' => $excerpt,
                        'content' => $finalContent,
                        'banner_image_id' => $bannerGallery?->id,
                        'og_image_id' => $bannerGallery?->id,
                        'author_id' => $resolvedAuthorId,
                        'author_name' => $authorName,
                        'status' => $status,
                        'published_at' => $status === 'published' ? $createdAt : null,
                        'view_count' => (int) ($oldBlog['view'] ?? 0),
                        'seo_title' => $seo['seo_title'],
                        'seo_description' => $seo['seo_description'],
                        'seo_keywords' => $seo['seo_keywords'],
                        'og_title' => $seo['og_title'],
                        'og_description' => $seo['og_description'],
                        'canonical_url' => $seo['canonical_url'],
                        'robots' => $seo['robots'],
                        'is_ai_generated' => $seo['is_ai_generated'],
                        'created_by' => $resolvedAuthorId,
                        'updated_by' => $resolvedAuthorId,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]
                );

                $this->blogMap[$oldId] = $blog->id;

                // Sync tags
                if (! empty($oldBlog['tags'])) {
                    $this->syncBlogTags($blog, (string) $oldBlog['tags'], $organizationId);
                }

                $this->logger->recordSuccess('blogs');
            } catch (\Throwable $e) {
                $this->logger->recordFailed('blogs', "Blog ID {$oldId} failed: " . $e->getMessage());
            }
        }

        $this->logger->endTable('blogs');
    }

    /**
     * Extract tags string and attach to Blog.
     */
    protected function syncBlogTags(Blog $blog, string $tagsString, int $organizationId): void
    {
        $rawTags = explode(',', $tagsString);
        $tagIds = [];

        foreach ($rawTags as $raw) {
            $tagName = trim($raw);
            if ($tagName === '' || strlen($tagName) < 2 || strlen($tagName) > 50) {
                continue;
            }

            $tagSlug = mb_substr(Str::slug($tagName), 0, 185);
            $tag = BlogTag::query()->firstOrCreate(
                [
                    'organization_id' => $organizationId,
                    'slug' => $tagSlug,
                ],
                [
                    'name' => Str::title(mb_substr($tagName, 0, 185)),
                ]
            );

            $tagIds[] = $tag->id;
        }

        if (! empty($tagIds)) {
            $blog->tags()->syncWithoutDetaching($tagIds);
        }
    }

    /**
     * Migrate old Comments to BlogComment model.
     *
     * @param  list<array<string, mixed>>  $comments
     */
    protected function migrateComments(array $comments, int $organizationId): void
    {
        $this->logger->startTable('blog_comments', count($comments));

        foreach ($comments as $oldComment) {
            $oldId = (int) ($oldComment['id'] ?? 0);
            $body = trim((string) ($oldComment['message'] ?? ''));
            if ($body === '') {
                $this->logger->recordSkipped('blog_comments', "Comment ID {$oldId} has empty message.");
                continue;
            }

            try {
                // Try linking to imported blog if parent_id or old blog link exists
                $oldBlogId = (int) ($oldComment['reply_to'] ?? $oldComment['parent_id'] ?? 0);
                $newBlogId = $this->blogMap[$oldBlogId] ?? null;

                if (! $newBlogId) {
                    // Fallback to first available blog
                    $newBlogId = Blog::query()->where('organization_id', $organizationId)->value('id');
                }

                if (! $newBlogId) {
                    $this->logger->recordSkipped('blog_comments', "No blog found to attach comment ID {$oldId}.");
                    continue;
                }

                $authorName = ! empty($oldComment['name']) ? mb_substr(trim((string) $oldComment['name']), 0, 185) : 'Anonymous Reader';
                $authorEmail = ! empty($oldComment['email']) ? mb_substr(trim((string) $oldComment['email']), 0, 185) : 'reader@examtube.in';
                $status = strtolower((string) ($oldComment['status'] ?? 'Active')) === 'active' ? 'approved' : 'pending';

                BlogComment::query()->firstOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'blog_id' => $newBlogId,
                        'author_email' => $authorEmail,
                        'body' => $body,
                    ],
                    [
                        'author_name' => $authorName,
                        'status' => $status,
                        'created_at' => ! empty($oldComment['created_at']) ? $oldComment['created_at'] : now(),
                        'updated_at' => ! empty($oldComment['updated_at']) ? $oldComment['updated_at'] : now(),
                    ]
                );

                $this->logger->recordSuccess('blog_comments');
            } catch (\Throwable $e) {
                $this->logger->recordFailed('blog_comments', "Comment ID {$oldId} failed: " . $e->getMessage());
            }
        }

        $this->logger->endTable('blog_comments');
    }

    /**
     * Migrate old newsletter emails to NewsletterSubscriber model.
     *
     * @param  list<array<string, mixed>>  $emails
     */
    protected function migrateNewsletterEmails(array $emails, int $organizationId): void
    {
        $this->logger->startTable('newsletter_subscribers', count($emails));

        foreach ($emails as $oldEmail) {
            $email = strtolower(trim((string) ($oldEmail['email'] ?? '')));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->logger->recordSkipped('newsletter_subscribers', "Invalid email: '{$email}'");
                continue;
            }

            try {
                $status = strtolower((string) ($oldEmail['status'] ?? 'Active')) === 'active' ? 'subscribed' : 'unsubscribed';
                $subAt = ! empty($oldEmail['subscribed_at']) ? $oldEmail['subscribed_at'] : (! empty($oldEmail['created_at']) ? $oldEmail['created_at'] : now());

                NewsletterSubscriber::query()->updateOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'email' => $email,
                    ],
                    [
                        'status' => $status,
                        'source' => 'legacy_import',
                        'subscribed_at' => $subAt,
                    ]
                );

                $this->logger->recordSuccess('newsletter_subscribers');
            } catch (\Throwable $e) {
                $this->logger->recordFailed('newsletter_subscribers', "Email {$email} failed: " . $e->getMessage());
            }
        }

        $this->logger->endTable('newsletter_subscribers');
    }

    /**
     * Migrate standalone content images from content_image table into Gallery.
     *
     * @param  list<array<string, mixed>>  $images
     */
    protected function migrateContentImages(array $images, int $organizationId): void
    {
        $this->logger->startTable('content_image', count($images));

        foreach ($images as $imgRow) {
            $imageRef = (string) ($imgRow['image_url'] ?? $imgRow['image_name'] ?? '');
            if ($imageRef === '') {
                $this->logger->recordSkipped('content_image', "Empty image reference.");
                continue;
            }

            try {
                $gallery = $this->imageImporter->importImage($imageRef, $organizationId, [
                    'source' => 'import',
                    'module' => 'gallery',
                    'folder' => 'content',
                ]);

                if ($gallery) {
                    $this->logger->recordSuccess('content_image');
                } else {
                    $this->logger->recordSkipped('content_image', "Image file not found for '{$imageRef}'");
                }
            } catch (\Throwable $e) {
                $this->logger->recordFailed('content_image', "Image '{$imageRef}' failed: " . $e->getMessage());
            }
        }

        $this->logger->endTable('content_image');
    }

    /**
     * Generate a unique slug for a given Eloquent Model class on collision.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    protected function generateUniqueSlug(string $modelClass, string $baseSlug, int $organizationId): string
    {
        $slug = mb_substr(Str::slug($baseSlug), 0, 180);
        if ($slug === '') {
            $slug = 'item-' . Str::random(5);
        }

        $originalSlug = $slug;
        $count = 1;

        while ($modelClass::query()->where('organization_id', $organizationId)->where('slug', $slug)->exists()) {
            $slug = mb_substr("{$originalSlug}-{$count}", 0, 185);
            $count++;
        }

        return $slug;
    }
}
