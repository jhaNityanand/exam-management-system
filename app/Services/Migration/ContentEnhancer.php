<?php

namespace App\Services\Migration;

use Illuminate\Support\Str;

class ContentEnhancer
{
    /**
     * Clean and enhance a blog post title or category title.
     */
    public function enhanceTitle(?string $title): string
    {
        if (! is_string($title) || trim($title) === '') {
            return 'Untitled';
        }

        $clean = trim($title);
        // Remove zero-width spaces and BOM
        $clean = preg_replace('/[\x{FEFF}\x{200B}-\x{200D}]/u', '', $clean) ?? $clean;
        // Normalize multiple spaces into single space
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;

        // Fix capitalization/punctuation glitches
        $clean = Str::ucfirst($clean);

        return trim($clean);
    }

    /**
     * Clean and format legacy HTML content for modern rendering.
     */
    public function enhanceHtml(?string $html): string
    {
        if (! is_string($html) || trim($html) === '') {
            return '';
        }

        $cleaned = $html;

        // 1. Remove BOM, zero-width spaces, and control characters
        $cleaned = preg_replace('/[\x{FEFF}\x{200B}-\x{200D}]/u', '', $cleaned) ?? $cleaned;

        // 2. Remove MS Word tags and comments (<o:p>, <o:p></o:p>, <!-- ... -->)
        $cleaned = preg_replace('/<o:p\b[^>]*>.*?<\/o:p>/is', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/<\/?o:p\b[^>]*>/is', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/<!--.*?-->/s', '', $cleaned) ?? $cleaned;

        // 3. Remove inline MS Word / MSO style attributes (e.g. style="mso-themecolor:text1;...")
        $cleaned = preg_replace_callback('/style=(["\'])(.*?)\1/is', function ($matches) {
            $styles = explode(';', $matches[2]);
            $keptStyles = [];
            foreach ($styles as $style) {
                $trimmed = trim($style);
                if (
                    $trimmed === '' ||
                    str_starts_with(strtolower($trimmed), 'mso-') ||
                    str_starts_with(strtolower($trimmed), 'font-family:')
                ) {
                    continue;
                }
                $keptStyles[] = $trimmed;
            }

            return ! empty($keptStyles) ? 'style="' . implode('; ', $keptStyles) . '"' : '';
        }, $cleaned) ?? $cleaned;

        // 4. Strip empty spans that carry no useful styling
        $cleaned = preg_replace('/<span\s*>(.*?)<\/span>/is', '$1', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/<span\s+style=""\s*>(.*?)<\/span>/is', '$1', $cleaned) ?? $cleaned;

        // 5. Clean up redundant empty paragraphs, empty breaks, and empty tags
        $cleaned = preg_replace('/<p\s*>\s*(&nbsp;|\s)*<\/p>/is', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/<p\s*>\s*<\/p>/is', '', $cleaned) ?? $cleaned;

        // 6. Demote any <h1> tags inside blog body to <h2> for proper SEO heading hierarchy
        $cleaned = preg_replace('/<h1(\b[^>]*)>/i', '<h2$1>', $cleaned) ?? $cleaned;
        $cleaned = str_replace('</h1>', '</h2>', $cleaned);

        // 7. Standardize list items
        $cleaned = preg_replace('/<li\s+class="MsoNormal"\s*>/i', '<li>', $cleaned) ?? $cleaned;

        // 8. Trim multiple leading/trailing break lines
        $cleaned = preg_replace('/^(<br\s*\/?>|\s)+/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/(<br\s*\/?>|\s)+$/i', '', $cleaned) ?? $cleaned;

        return trim($cleaned);
    }

    /**
     * Generate an excerpt from HTML content if no excerpt is provided.
     */
    public function generateExcerpt(?string $html, int $limit = 220): string
    {
        if (! is_string($html) || trim($html) === '') {
            return '';
        }

        $plain = strip_tags($html);
        $plain = preg_replace('/[\x{FEFF}\x{200B}-\x{200D}]/u', '', $plain) ?? $plain;
        $plain = preg_replace('/\s+/', ' ', $plain) ?? $plain;

        return Str::limit(trim($plain), $limit);
    }
}
