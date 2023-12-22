<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profiles = ['Conseiller client', 'Responsable RH', 'Responsable Production',
            'Responsable IT', 'Agent de sécurité', 'Superviseur',
            "Agent d'entretien", 'Informaticien', 'Expert métier',
            'Expert Métier', 'Responsable de Production', 'Chef de Projet',
            'Vigie', 'Responsable des Opérations',
            'Administrateur Systèmes et Réseaux – Support',
            'Agent moyens généraux', 'Formateur', 'Chargé de formation',
            'Chargé de recrutement', 'Chargé de Qualité et Process', 'Directeur', 'Directeur de Site', 'Directrice de Site',
            'Directeur Ressources Humaines',
            'Chargée de Communication et moyens Généraux',
            'Chargé de Communication et moyens Généraux',
            'Coordinateur Qualité Formation',
            'Coursier', 'Coordinateur Vigie',
            'Chargée de Communication',
            'Head OF Operational Excellence', 'Directeur Production',
            'Office Manager', 'Directrice RH', 'Chargé RH', 'Data Miner',
            "Responsable d'opération", 'Coordinateur CPS',
            'Directeur des Systèmes informatiques', 'Contrôleur de gestion',
            'Stagiaire Développeur', 'Infirmière', 'Chargée RH',
            'Chargée de correction des incohérences',
            'Chargé de planification et statistiques',
            'Chargé de reporting et statistiques',
            'Responsable des moyens généraux',
            'Assistante moyens généraux et Communication',
            'Chargée de mission auprès de la direction',
            'Head of Data Protection and internal Audit', 'Data Protection Officer',
            'Stagiaire Marketing et communication',
            'Responsable IT, Réseaux et Sécurité', 'Stagiaire IT',
            'Stagiaire RH', 'Chargé des moyens généraux',
            'Responsable Département RH', "Responsable d'opérations",
            'Chargée de communication Marketing et Evénementiel',
            'Chargé de communication Marketing et Evénementiel',
            'Stagiaire Communication et Marketing', 'Stagiaire Juriste',
            'Stagiaire It Dévloppeur', 'RPA Developer',
            'Full Stack Javascript Developer', 'Chargée de recrutement',
            'Developpeur', 'Stagiaire Assistante de Direction',
            'Responsable Qualité Formation et Recrutement', 'Responsable Formation',
            'Coordinatrice Qualité Formation', 'Expert métier FR', 'Expert métier NL', 'Agent FR', 'Agent NL'];

        foreach ($profiles as $profile) {
            Role::factory()->create([
                'name' => $profile
            ]);
        }
    }
}
