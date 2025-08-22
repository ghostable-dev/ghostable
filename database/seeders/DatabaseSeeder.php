<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Blog\Seeders\PostSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PostSeeder::class);
    }
}
