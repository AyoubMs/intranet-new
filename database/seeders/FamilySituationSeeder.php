<?php

namespace Database\Seeders;

use App\Models\FamilySituation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FamilySituationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $situations = ['Célibataire', 'Marié(e)', 'Divorcé(e)'];

        foreach ($situations as $situation) {
            FamilySituation::factory()->create([
                'name' => $situation
            ]);
        }
    }
}
