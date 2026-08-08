<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('llm_accounts')) {
            return;
        }

        $records = DB::table('llm_accounts')->get();

        foreach ($records as $record) {
            $key = (string) ($record->api_key ?? '');
            if ($key === '') {
                continue;
            }

            // Check if key is already encrypted
            try {
                Crypt::decryptString($key);
                // Already encrypted
            } catch (\Throwable $e) {
                // Key is plaintext — encrypt it
                DB::table('llm_accounts')
                    ->where('id', $record->id)
                    ->update(['api_key' => Crypt::encryptString($key)]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('llm_accounts')) {
            return;
        }

        $records = DB::table('llm_accounts')->get();

        foreach ($records as $record) {
            $key = (string) ($record->api_key ?? '');
            if ($key === '') {
                continue;
            }

            try {
                $decrypted = Crypt::decryptString($key);
                DB::table('llm_accounts')
                    ->where('id', $record->id)
                    ->update(['api_key' => $decrypted]);
            } catch (\Throwable $e) {
                // Key was not encrypted
            }
        }
    }
};
