<?php

namespace Database\Seeders;

use App\Models\DemandeConge;
use App\Models\EtatDemandeConge;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemandeCongeSeeder extends Seeder
{
    protected static function getPeriod($dateDebut, $dateFin)
    {
        return $dateDebut . ' - ' . $dateFin;
    }

    protected static function getEtatDemandeID($state)
    {
        if (str_contains(strtolower($state), 'val')) {
            return EtatDemandeConge::where('etat_demande', 'like', "val%")->first()->id;
        } else if (str_contains(strtolower($state), 'clo')) {
            return EtatDemandeConge::where('etat_demande', 'like', "clo%")->first()->id;
        } else if (str_contains(strtolower($state), 'ann')) {
            return EtatDemandeConge::where('etat_demande', 'like', "can%")->first()->id;
        } else if (str_contains(strtolower($state), 'cre')) {
            return EtatDemandeConge::where('etat_demande', 'like', "cre%")->first()->id;
        } else if (str_contains(strtolower($state), 'rej')) {
            return EtatDemandeConge::where('etat_demande', 'like', "rej%")->first()->id;
        }
    }

    protected static function getDate($dates)
    {
        foreach ($dates as $date) {
            if ($date !== '') {
                return $date;
            }
        }
        return null;
    }

    protected static function getUserID($matricule)
    {
        return User::where('matricule', $matricule)->first()->id;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $congesPath = storage_path().'\app\public\conges.csv';

        $fillConges = function($data) {
            $datesDebut = [$data[13], $data[15], $data[17], $data[20]];
            $datesFin = [$data[14], $data[16], $data[19], $data[21]];
            DemandeConge::factory()->create([
                'date_demande' => $data[8],
                'date_retour' => $data[9],
                'periode' => self::getPeriod(self::getDate($datesDebut), self::getDate($datesFin)),
                'date_debut' => self::getDate($datesDebut),
                'date_fin' => self::getDate($datesFin),
                'etat_demande_id' => self::getEtatDemandeID($data[23]),
                'user_id' => self::getUserID($data[2])
            ]);
        };

        Utils::getDataFromDBOrValidateInjectionFile($fillConges, $congesPath);
    }
}
