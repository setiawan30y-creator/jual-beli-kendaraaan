<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceDataSeeder::class);

        // Data demo tidak dijalankan di production.
        if (! app()->environment('production')) {
            $this->call(DemoSeeder::class);
        }
    }
}
