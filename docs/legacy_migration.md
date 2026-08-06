# Legacy Examtube Application Data Migration Guide

This guide documents the architecture, services, file locations, usage, and troubleshooting steps for migrating legacy Examtube application data and uploaded media into the new Laravel 11 platform.

---

## 1. Overview

The migration system imports legacy content from the previous Examtube application (`public/old-application/` or `public/old-examtube/`) into the new multi-tenant database structure.

### Imported Data Includes:
- **Blog Categories** (`categories` -> `BlogCategory`)
- **Blog Posts** (`blogs` -> `Blog`)
- **Blog Tags & Tag Relations** (Extracted from old blog tags -> `BlogTag` & `blog_tag_relations`)
- **Blog Comments** (`comments` -> `BlogComment`)
- **Newsletter Subscribers** (`email` -> `NewsletterSubscriber`)
- **Gallery Images & Media Files** (`content_image` & embedded blog images -> `Gallery` + physical copies in `storage/app/public/gallery/`)

---

## 2. Directory Structure & Source Files

```
public/
  ├── old-application/               # Legacy backup folder (git-ignored)
  │     ├── u967843851_examtube.sql  # Database SQL dump
  │     └── images/                  # Legacy media assets
  │           ├── blog/              # Blog cover images
  │           ├── category/          # Category cover images
  │           ├── content/           # Inline embedded article images
  │           └── users/             # Profile avatars
  └── old-examtube/                 # Alternative directory fallback
```

---

## 3. Modular Architecture

The migration logic is built using clean, decoupled Laravel services:

| Component | Path | Description |
| --- | --- | --- |
| **SQL Parser** | [app/Services/Migration/LegacySqlParser.php](file:///c:/wamp64/www/exam-management-system/app/Services/Migration/LegacySqlParser.php) | Quote-aware SQL parser extracting table records into array structures. |
| **Content Enhancer** | [app/Services/Migration/ContentEnhancer.php](file:///c:/wamp64/www/exam-management-system/app/Services/Migration/ContentEnhancer.php) | Sanitizes dirty legacy HTML, strips MS Word junk tags (`<o:p>`, `mso-style`), cleans typography, and structures heading hierarchy. |
| **SEO Enhancer** | [app/Services/Migration/SeoEnhancer.php](file:///c:/wamp64/www/exam-management-system/app/Services/Migration/SeoEnhancer.php) | Uses `App\Services\Llm\LlmService` (or smart rule-based fallback) to generate unique Meta Titles, Meta Descriptions, Keywords, and Open Graph attributes. |
| **Image Importer** | [app/Services/Migration/LegacyImageImporter.php](file:///c:/wamp64/www/exam-management-system/app/Services/Migration/LegacyImageImporter.php) | Copies physical images to `storage/app/public/gallery/`, registers entries in `galleries` using SHA-256 content deduplication, and rewrites embedded HTML image `src` URLs. |
| **Import Logger** | [app/Services/Migration/ImportLogger.php](file:///c:/wamp64/www/exam-management-system/app/Services/Migration/ImportLogger.php) | Tracks metrics, execution times, success/fail/skip counts, and writes logs to `storage/logs/migration-*.log`. |
| **Main Service** | [app/Services/Migration/ExamtubeMigrationService.php](file:///c:/wamp64/www/exam-management-system/app/Services/Migration/ExamtubeMigrationService.php) | Main orchestrator coordinating full data migration pipeline. |

---

## 4. How to Run the Import

### Option A: Via Artisan Command (Recommended)

```bash
php artisan legacy:import-examtube
```

#### Command Options:
- `--file=path/to/backup.sql` : Specify custom SQL dump file path.
- `--org=1` : Target Organization ID (default is `1`).

### Option B: Via Database Seeder

```bash
php artisan db:seed --class=ExamtubeLegacyDataSeeder
```

---

## 5. Key Features & Safeguards

1. **Idempotency & Safe Re-runs**:
   - Running the importer multiple times update-or-creates existing records without generating duplicates.
2. **SHA-256 Image Deduplication**:
   - Prevents duplicate media files from accumulating in storage.
3. **Collision-Free Slugs**:
   - Automatically appends numerical suffixes (`-1`, `-2`) on slug collisions.
4. **Resilient Error Handling**:
   - Catches single-row failures gracefully and continues processing remaining records.
5. **Detailed Logging**:
   - Generates formatted summary tables in console and persistent log files in `storage/logs/migration-YYYY-MM-DD_HH-II-SS.log`.

---

## 6. Verification & Troubleshooting

- **Logs location:** `storage/logs/migration-*.log`
- **Verify Blog list:** Open `/admin/blogs` or visit `/blogs` on public site.
- **Verify Media Gallery:** Open `/admin/gallery` to view imported images.
