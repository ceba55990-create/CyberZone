<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('country')->upsert([
            ['code' => 'AU', 'name' => 'Australia', 'population' => 24016400],
            ['code' => 'BR', 'name' => 'Brazil', 'population' => 206135893],
            ['code' => 'CA', 'name' => 'Canada', 'population' => 35985751],
            ['code' => 'CN', 'name' => 'China', 'population' => 1375210000],
            ['code' => 'DE', 'name' => 'Germany', 'population' => 81459000],
            ['code' => 'FR', 'name' => 'France', 'population' => 64513242],
            ['code' => 'GB', 'name' => 'United Kingdom', 'population' => 65097000],
            ['code' => 'IN', 'name' => 'India', 'population' => 1285400000],
            ['code' => 'UA', 'name' => 'Ukraine', 'population' => 41167335],
            ['code' => 'US', 'name' => 'United States', 'population' => 323995528],
        ], ['code'], ['name', 'population']);
    }
}
