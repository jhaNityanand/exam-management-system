<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\UniqueUserSlug;
use Database\Seeders\Support\SeederContact;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Free admin@examtube.in if it still belongs to the old org-admin account.
        $adminHolder = User::query()->where('email', SeederContact::EMAIL_ADMIN)->first();
        if ($adminHolder && $adminHolder->username === 'org-admin') {
            if (! User::query()->where('email', SeederContact::EMAIL_INFO)->exists()) {
                $adminHolder->forceFill([
                    'email' => SeederContact::EMAIL_INFO,
                    'name' => 'Organization Admin',
                ])->save();
            } else {
                $adminHolder->forceFill([
                    'email' => 'org-admin-migrating@examtube.in',
                ])->save();
            }
        }

        $users = [
            [
                'email' => SeederContact::EMAIL_ADMIN,
                'name' => 'Admin',
                'username' => 'admin',
                'legacy_emails' => ['admin@example.in'],
            ],
            [
                'email' => SeederContact::EMAIL_INFO,
                'name' => 'Organization Admin',
                'username' => 'org-admin',
                'legacy_emails' => ['org-admin-migrating@examtube.in'],
            ],
            [
                'email' => 'candidate@examtube.in',
                'name' => 'Candidate',
                'username' => 'candidate',
                'legacy_emails' => [],
            ],
        ];

        foreach ($users as $data) {
            $user = User::query()->where('email', $data['email'])->first()
                ?: User::query()->where('username', $data['username'])->first()
                ?: (! empty($data['legacy_emails'])
                    ? User::query()->whereIn('email', $data['legacy_emails'])->first()
                    : null);

            if ($user) {
                $user->forceFill([
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ])->save();
            } else {
                $user = User::query()->create([
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ]);
            }

            UniqueUserSlug::ensureFor($user);
        }

        // Drop any leftover legacy demo login that was not remapped.
        User::query()->where('email', 'admin@example.in')->delete();
    }
}
