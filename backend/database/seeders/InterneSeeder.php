<?php

namespace Database\Seeders;

use App\Models\Interne;
use Illuminate\Database\Seeder;

class InterneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Interne::factory()->count(10)->create();
    }
}
