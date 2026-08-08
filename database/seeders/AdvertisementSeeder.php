<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\Advertisement\AdvertisementService;
use Illuminate\Database\Seeder;

class AdvertisementSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(AdvertisementService::class);
        $organizations = Organization::all();

        if ($organizations->isEmpty()) {
            $service->seedDefaults(1, true);

            return;
        }

        foreach ($organizations as $org) {
            $service->seedDefaults($org->id, true);
        }
    }
}
