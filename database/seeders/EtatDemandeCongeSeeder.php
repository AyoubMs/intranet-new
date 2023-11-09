<?php

namespace Database\Seeders;

use App\Models\EtatDemandeConge;
use Illuminate\Database\Seeder;

class EtatDemandeCongeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = ['validated', 'canceled', 'rejected', 'created', 'closed'];

        foreach ($states as $state) {
            EtatDemandeConge::factory()->create([
                'etat_demande' => $state
            ]);
        }
    }
}
