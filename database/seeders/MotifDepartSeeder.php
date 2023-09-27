<?php

namespace Database\Seeders;

use App\Models\MotifDepart;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MotifDepartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $motifs = ["Fin de période d'essai", 'Démission', 'Licenciement ',
            'Abandon de poste', 'Licenciement', 'Licenciement économique',
            'démission', 'abandon de poste', 'Retraite',
            "Démission/Fin d'activité",
            "Démission/ Départ à l'étranger pr étude",
            'Démission/Raisons personnelles', 'Abandon de formation',
            'Démission/ Raisons personnelles', 'Démission/ Perso',
            'Abandon de poste / a intégré pour débaucher agents NL',
            'Abandon de poste suite à débauchage', "N'a pas intégré",
            'Abandon de Formation', 'Fin de stage', 'Fin de Formation',
            'Fin de formation', 'Fin de contrat ANAPEC', 'Barcelone',
            'Elle reste à Barcelone', 'Fin de Stage', 'Fin contrat CDD',
            'Doublon', 'Changement de Matricule paie avec Comptable',
            'Fin de contrat'];

        foreach ($motifs as $motif) {
            MotifDepart::factory()->create([
                'name' => $motif
            ]);
        }
    }
}
