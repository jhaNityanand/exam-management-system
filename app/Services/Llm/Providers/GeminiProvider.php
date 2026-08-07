<?php

namespace App\Services\Llm\Providers;

use App\Services\Llm\DTOs\LlmResponse;
use App\Services\Llm\Exceptions\LlmException;

class GeminiProvider extends AbstractProvider
{
    public function name(): string
    {
        return 'gemini';
    }

    protected function sendChat(string $system, string $user, array $options = []): LlmResponse
    {
        $baseUrl = filled($this->baseUrl()) ? $this->baseUrl() : 'https://generativelanguage.googleapis.com/v1beta';
        $model = rawurlencode($this->model());
        $url = $baseUrl.'/models/'.$model.':generateContent?key='.urlencode($this->apiKey());

        $response = $this->http()->post($url, [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $user]],
                ],
            ],
            'generationConfig' => [
                'temperature' => $this->temperature(),
                'maxOutputTokens' => $this->maxTokens(),
                'responseMimeType' => 'application/json',
            ],
        ]);

        if ($response->failed()) {
            throw LlmException::providerUnavailable(
                $this->name(),
                'HTTP '.$response->status().': '.$response->body()
            );
        }

        $json = $response->json();
        $parts = data_get($json, 'candidates.0.content.parts', []);
        $content = '';
        if (is_array($parts)) {
            foreach ($parts as $part) {
                $content .= (string) ($part['text'] ?? '');
            }
        }

        if (trim($content) === '') {
            throw LlmException::invalidResponse($this->name(), 'Empty completion content.');
        }

        $usage = data_get($json, 'usageMetadata', []);

        return new LlmResponse(
            content: $content,
            promptTokens: isset($usage['promptTokenCount']) ? (int) $usage['promptTokenCount'] : null,
            completionTokens: isset($usage['candidatesTokenCount']) ? (int) $usage['candidatesTokenCount'] : null,
            totalTokens: isset($usage['totalTokenCount']) ? (int) $usage['totalTokenCount'] : null,
            model: $this->model(),
            raw: is_array($json) ? $json : [],
        );
    }
}
