<?php

namespace Database\Seeders;

use App\Blog\Seeders\PostSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PostSeeder::class,
            IntegrationSeeder::class,
        ]);
    }
}
