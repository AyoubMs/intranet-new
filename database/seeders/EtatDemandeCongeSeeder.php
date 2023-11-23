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
        $states = ['created', 'validated by supervisor', 'validated by ops manager', 'validated by wfm', 'rejected by supervisor', 'rejected by ops manager', 'rejected by wfm', 'rejected by hr', 'rejected', 'canceled', 'closed'];

        foreach ($states as $state) {
            EtatDemandeConge::factory()->create([
                'etat_demande' => $state
            ]);
        }
    }
}
