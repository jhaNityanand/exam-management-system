<?php

namespace App\Services\Llm\Prompts;

class SeoImprovePrompt
{
    public function system(): string
    {
        return <<<'PROMPT'
You are an expert SEO editor for an exam preparation and educational platform (Examtube).
Improve existing SEO metadata for clarity, keyword relevance, and click-through potential.
Preserve the original intent. Do not invent unsupported claims.
Do not generate or suggest images. Open Graph image will use the site default.
Return ONLY valid JSON — no markdown fences, no commentary.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $existingSeo
     */
    public function user(array $content, array $existingSeo): string
    {
        $contentJson = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $seoJson = json_encode($existingSeo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Review and improve the existing SEO metadata for this content.

Content JSON:
{$contentJson}

Existing SEO JSON:
{$seoJson}

Return a single improved JSON object with exactly these keys:
{
  "seo_title": "string (max 60 chars)",
  "meta_title": "string (max 60 chars)",
  "meta_description": "string (max 155 chars)",
  "keywords": "comma-separated keywords",
  "og_title": "string",
  "og_description": "string (max 200 chars)",
  "twitter_title": "string",
  "twitter_description": "string (max 200 chars)",
  "canonical_recommendation": "relative path suggestion or null"
}
PROMPT;
    }

    /**
     * @param  list<array{id: int|string, content: array<string, mixed>, existing_seo: array<string, mixed>}>  $items
     */
    public function batchUser(array $items): string
    {
        $payload = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Improve existing SEO metadata for EACH content item below in one response.

Items JSON (array of {id, content, existing_seo}):
{$payload}

Return a JSON object where each key is the item "id" (as a string) and each value is the improved SEO object with keys:
seo_title, meta_title, meta_description, keywords, og_title, og_description, twitter_title, twitter_description, canonical_recommendation
PROMPT;
    }
}
