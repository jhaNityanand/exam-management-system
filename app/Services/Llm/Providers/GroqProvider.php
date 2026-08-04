<?php

namespace App\Services\Llm\Providers;

use App\Services\Llm\DTOs\LlmResponse;
use App\Services\Llm\Exceptions\LlmException;

class GroqProvider extends AbstractProvider
{
    public function name(): string
    {
        return 'groq';
    }

    protected function sendChat(string $system, string $user, array $options = []): LlmResponse
    {
        $response = $this->http()
            ->withToken($this->apiKey())
            ->post($this->baseUrl().'/chat/completions', [
                'model' => $this->model(),
                'temperature' => $this->temperature(),
                'max_tokens' => $this->maxTokens(),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if ($response->failed()) {
            throw LlmException::providerUnavailable(
                $this->name(),
                'HTTP '.$response->status().': '.$response->body()
            );
        }

        $json = $response->json();
        $content = data_get($json, 'choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw LlmException::invalidResponse($this->name(), 'Empty completion content.');
        }

        return new LlmResponse(
            content: $content,
            promptTokens: data_get($json, 'usage.prompt_tokens'),
            completionTokens: data_get($json, 'usage.completion_tokens'),
            totalTokens: data_get($json, 'usage.total_tokens'),
            model: data_get($json, 'model', $this->model()),
            raw: is_array($json) ? $json : [],
        );
    }
}
