<?php

namespace App\Console\Commands;

use App\Services\Seo\SeoSiteGenerator;
use Illuminate\Console\Command;

class GenerateSeoFilesCommand extends Command
{
    protected $signature = 'seo:generate {--org= : Organization ID (defaults to current/first)}';

    protected $description = 'Generate sitemap index, robots.txt, feeds, humans.txt, security.txt, and manifest.json';

    public function handle(SeoSiteGenerator $generator): int
    {
        $orgId = $this->option('org') !== null ? (int) $this->option('org') : current_organization_id();

        if (! $orgId) {
            $this->error('No organization found. Seed the database first.');

            return self::FAILURE;
        }

        $this->info("Generating SEO files for organization #{$orgId}…");
        $result = $generator->generate($orgId);

        $this->info('Generated at: '.$result['generated_at']);
        foreach ($result['url_counts'] as $section => $count) {
            $this->line(sprintf('  - %-16s %d URL(s)', $section, $count));
        }

        $this->info('Files written to the public directory.');

        return self::SUCCESS;
    }
}
