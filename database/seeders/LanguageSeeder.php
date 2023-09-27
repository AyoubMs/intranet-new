<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = ['Allemand', 'Français', 'Anglais', 'Dutch', 'Espagnol', 'Italien', 'Français/Anglais', 'Russe', 'IT', 'Arabe', 'français',
            'Portugais', 'Roumain', 'Polonais'];

        foreach ($languages as $language) {
            Language::factory()->create([
                'name' => $language
            ]);
        }
    }
}
