<?php

namespace Database\Seeders;

use App\Models\Notebook;
use Illuminate\Database\Seeder;

class notebookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 0; $i < 50; $i++) {
            Notebook::factory()->create();
        }
    }
}
