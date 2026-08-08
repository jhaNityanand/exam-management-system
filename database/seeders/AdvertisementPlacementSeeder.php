<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\Advertisement\AdvertisementService;
use Illuminate\Database\Seeder;

class AdvertisementPlacementSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(AdvertisementService::class);
        $organizations = Organization::all();

        if ($organizations->isEmpty()) {
            $service->seedDefaultPlacements(1, true);
            $service->forgetCache(1);

            return;
        }

        foreach ($organizations as $org) {
            $service->seedDefaultPlacements($org->id, true);
            $service->forgetCache($org->id);
        }
    }
}
