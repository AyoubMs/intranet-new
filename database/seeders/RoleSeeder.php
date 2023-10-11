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
            'Vigie', 'Responsable des Opérations', 'Chargé Recrutement',
            'Administrateur Systèmes et Réseaux – Support',
            'Agent moyens généraux', 'Formateur',
            'Chargé de planification et statistiques ', 'Chargé de formation',
            'Chargé de recrutement', 'Chargé de Qualité et Process',
            'Directeur Ressources Humaines',
            'Chargée de Communication et moyens Généraux',
            'Coordinateur Qualité Formation', 'Chargé de Formation',
            'Coursier', 'Responsable des opérations', 'Coordinateur Vigie',
            'Chargée de Communication',
            'Chargé de reporting et statistique\xa0',
            'Head OF Operational Excellence', 'Directeur Production',
            'Office Manager', 'Directrice RH', 'Chargé RH', 'Data Miner',
            "Responsable d'opration", 'Coordinateur CPS',
            'Directeur des Systèmes informatiques', 'Contrôleur de gestion',
            'Stagiaire Développeur', 'Infirmière', 'Chargée RH', 'Développeur',
            'Chargée de correction des incohérences',
            'Responsable des moyens généraux',
            'Assistante moyens généraux et Communication',
            'Chargée de mission auprès de la direction',
            'Head of Data Protection and internal Audit',
            'Stagiaire Marketing et communication',
            'Responsable IT, Réseaux et Sécurité', 'Stagiaire IT',
            'Stagiaire RH', 'Chargé des moyens généraux',
            'Responsable Département RH', "Responsable d'opérations",
            'Chargée de communication Marketing et Evénementiel',
            'Stagiaire Communication et Marketing', 'Stagiaire Juriste',
            'Stagiaire It Dévloppeur', 'RPA Developer', 'Conseiller Client',
            'Full Stack Javascript Developer', 'Chargée de recrutement',
            'Developpeur', 'Stagiaire Assistante de Direction',
            'Coordinatrice Qualité Formation',
            'Chargé de planification et statistiques', 'Expert métier FR', 'Expert métier NL', 'Agent FR', 'Agent NL'];

        foreach ($profiles as $profile) {
            Role::factory()->create([
                'name' => $profile
            ]);
        }
    }
}
