<?php

namespace Database\Seeders;

use App\Models\LlmAccount;
use Illuminate\Database\Seeder;

class LlmAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Mistral AI (Default Priority 1)
            [
                'provider' => 'mistral',
                'account_name' => 'Mistral Account 1',
                'api_key' => '',
                'model' => 'mistral-small-latest',
                'base_url' => 'https://api.mistral.ai/v1',
                'organization_id' => null,
                'is_active' => true,
                'priority' => 1,
                'notes' => 'Mistral AI Free Tier Primary Account',
            ],
            [
                'provider' => 'mistral',
                'account_name' => 'Mistral Account 2',
                'api_key' => '',
                'model' => 'mistral-small-latest',
                'base_url' => 'https://api.mistral.ai/v1',
                'organization_id' => null,
                'is_active' => true,
                'priority' => 2,
                'notes' => 'Mistral AI Free Tier Secondary Account',
            ],

            // Groq (Priority 2)
            [
                'provider' => 'groq',
                'account_name' => 'Groq Account 1',
                'api_key' => '',
                'model' => 'llama-3.3-70b-versatile',
                'base_url' => 'https://api.groq.com/openai/v1',
                'organization_id' => null,
                'is_active' => true,
                'priority' => 1,
                'notes' => 'Groq Llama-3.3 70B Free Account 1',
            ],
            [
                'provider' => 'groq',
                'account_name' => 'Groq Account 2',
                'api_key' => '',
                'model' => 'llama-3.3-70b-versatile',
                'base_url' => 'https://api.groq.com/openai/v1',
                'organization_id' => null,
                'is_active' => true,
                'priority' => 2,
                'notes' => 'Groq Llama-3.3 70B Free Account 2',
            ],

            // Google Gemini (Priority 3)
            [
                'provider' => 'gemini',
                'account_name' => 'Gemini Account 1',
                'api_key' => '',
                'model' => 'gemini-flash-latest',
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'organization_id' => null,
                'is_active' => true,
                'priority' => 1,
                'notes' => 'Google Gemini Flash Free Account 1',
            ],
            [
                'provider' => 'gemini',
                'account_name' => 'Gemini Account 2',
                'api_key' => '',
                'model' => 'gemini-flash-latest',
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'organization_id' => null,
                'is_active' => true,
                'priority' => 2,
                'notes' => 'Google Gemini Flash Free Account 2',
            ],

            // OpenRouter (Priority 4)
            [
                'provider' => 'openrouter',
                'account_name' => 'OpenRouter Free 1',
                'api_key' => '',
                'model' => 'openrouter/auto',
                'base_url' => 'https://openrouter.ai/api/v1',
                'organization_id' => null,
                'is_active' => true,
                'priority' => 1,
                'notes' => 'OpenRouter Free Model Auto Router 1',
            ],
            [
                'provider' => 'openrouter',
                'account_name' => 'OpenRouter Free 2',
                'api_key' => '',
                'model' => 'openrouter/auto',
                'base_url' => 'https://openrouter.ai/api/v1',
                'organization_id' => null,
                'is_active' => true,
                'priority' => 2,
                'notes' => 'OpenRouter Free Model Auto Router 2',
            ],
        ];

        foreach ($accounts as $data) {
            $existing = LlmAccount::query()
                ->where('provider', $data['provider'])
                ->where('account_name', $data['account_name'])
                ->first();

            $envKey = match ($data['provider']) {
                'mistral' => env('MISTRAL_API_KEY_'.$data['priority'], env('MISTRAL_API_KEY', '')),
                'groq' => env('GROQ_API_KEY_'.$data['priority'], env('GROQ_API_KEY', '')),
                'gemini' => env('GEMINI_API_KEY_'.$data['priority'], env('GEMINI_API_KEY', '')),
                'openrouter' => env('OPENROUTER_API_KEY_'.$data['priority'], env('OPENROUTER_API_KEY', '')),
                default => '',
            };

            if (empty($data['api_key'])) {
                if (! empty($envKey)) {
                    $data['api_key'] = (string) $envKey;
                } elseif ($existing && ! empty($existing->api_key)) {
                    unset($data['api_key']);
                }
            }

            LlmAccount::updateOrCreate(
                [
                    'provider' => $data['provider'],
                    'account_name' => $data['account_name'],
                ],
                $data
            );
        }
    }
}
