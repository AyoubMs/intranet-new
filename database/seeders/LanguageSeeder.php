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
        $languages = ['Français/Anglais', 'Dutch', 'Français', 'Allemand',
            'Espagnol', 'Italien', 'Italien/Anglais', 'Russe', 'Anglais',
            'Dutch/Français', 'IT', 'Arabe', 'français', 'Portugais',
            'Anglais/Français', 'Roumain', 'Polonais', 'Français/Portugais',
            'Allemand/Français'];

        foreach ($languages as $language) {
            Language::factory()->create([
                'name' => $language
            ]);
        }
    }
}
