<?php

namespace Database\Seeders;

use App\Models\Url;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $data = [];

        $urls = file_get_contents(database_path('data/urls.json'));

        $urls = json_decode($urls, true);

        foreach ($urls as $url) {
            $data[] = ['url' => $url];
        }

        Url::insert($data);
    }
}
