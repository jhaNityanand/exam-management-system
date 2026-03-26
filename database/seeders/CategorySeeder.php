<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo-org')->first();
        if (! $org) {
            return;
        }

        $root = Category::firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'General'],
            ['description' => 'Top-level category', 'status' => 'active']
        );

        Category::firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'Mathematics'],
            ['parent_id' => $root->id, 'description' => 'Math subcategory', 'status' => 'active']
        );
    }
}
