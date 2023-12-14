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
        $states = ['created', 'validated by resp it', 'validated by director', 'validated by supervisor', 'validated by ops manager', 'validated by wfm', 'validated by vigie', 'validated by cps', 'validated by cci', 'validated by coordinateur vigie', 'validated by coordinateur cps', 'validated by head of operational excellence', 'validated by resp hr', 'validated by charge hr', 'rejected by resp it', 'rejected by director', 'rejected by supervisor', 'rejected by ops manager', 'rejected by wfm', 'rejected by vigie', 'rejected by cps', 'rejected by cci', 'rejected by coordinateur vigie', 'rejected by coordinateur cps', 'rejected by head of operational excellence', 'rejected by charge hr', 'rejected by resp hr', 'rejected by hr', 'rejected', 'canceled', 'closed'];

        foreach ($states as $state) {
            EtatDemandeConge::factory()->create([
                'etat_demande' => $state
            ]);
        }
    }
}
