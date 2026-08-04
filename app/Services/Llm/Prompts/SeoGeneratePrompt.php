<?php

namespace App\Services\Llm\Prompts;

class SeoGeneratePrompt
{
    public function system(): string
    {
        return <<<'PROMPT'
You are an expert SEO copywriter for an exam preparation and educational platform (Examtube).
Write concise, accurate, non-spammy SEO metadata that matches the content intent.
Never invent facts that are not supported by the provided content.
Do not generate or suggest images. Open Graph image will use the site default.
Return ONLY valid JSON — no markdown fences, no commentary.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function user(array $content): string
    {
        $payload = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Generate high-quality SEO metadata for the following content item.

Content JSON:
{$payload}

Return a single JSON object with exactly these keys:
{
  "seo_title": "string (max 60 chars, primary SEO title)",
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
     * @param  list<array{id: int|string, content: array<string, mixed>}>  $items
     */
    public function batchUser(array $items): string
    {
        $payload = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Generate high-quality SEO metadata for EACH content item below in one response.

Items JSON (array):
{$payload}

Return a JSON object where each key is the item "id" (as a string) and each value is:
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
}
