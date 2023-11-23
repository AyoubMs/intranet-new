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
            $state = strtolower($state);
            if (str_contains($state, 'ressources')) {
                return EtatDemandeConge::where('etat_demande', 'like', "clo%")->first()->id;
            } else if (str_contains($state, 'wfm')) {
                return EtatDemandeConge::where('etat_demande', 'like', "validated by %wfm")->first()->id;
            } else if (str_contains($state, 'ops')) {
                return EtatDemandeConge::where('etat_demande', 'like', "validated by %ops%")->first()->id;
            } else if (str_contains($state, 'tous')) {
                return EtatDemandeConge::where('etat_demande', 'like', "validated by clo%")->first()->id;
            } else if (str_contains($state, 'super')) {
                return EtatDemandeConge::where('etat_demande', 'like', "validated by %sup%")->first()->id;
            }
        } else if (str_contains(strtolower($state), 'clo')) {
            return EtatDemandeConge::where('etat_demande', 'like', "clo%")->first()->id;
        } else if (str_contains(strtolower($state), 'ann')) {
            return EtatDemandeConge::where('etat_demande', 'like', "can%")->first()->id;
        } else if (str_contains(strtolower($state), 'cre')) {
            return EtatDemandeConge::where('etat_demande', 'like', "cre%")->first()->id;
        } else if (str_contains(strtolower($state), 'rej')) {
            $state = strtolower($state);
            if (str_contains($state, 'ressources')) {
                return EtatDemandeConge::where('etat_demande', 'like', "rejected by hr")->first()->id;
            } else if (str_contains($state, 'wfm')) {
                return EtatDemandeConge::where('etat_demande', 'like', "rejected by %wfm")->first()->id;
            } else if (str_contains($state, 'ops')) {
                return EtatDemandeConge::where('etat_demande', 'like', "rejected by %ops%")->first()->id;
            } else if (str_contains($state, 'super')) {
                return EtatDemandeConge::where('etat_demande', 'like', "rejected by %sup%")->first()->id;
            }
            return EtatDemandeConge::where('etat_demande', "rejected")->first()->id;
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
        return User::where('matricule', $matricule)->first()->id ?? null;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $congesPath = storage_path().'\app\public\conges.csv';

        $fillConges = function($data) {
            $datesDebut = [$data[13] ?? null, $data[15] ?? null, $data[17] ?? null, $data[20] ?? null];
            $datesFin = [$data[14] ?? null, $data[16] ?? null, $data[19] ?? null, $data[21] ?? null];
            $data[8] = $data[8] ?? null;
            if ($data[8] and $data[9] and $data[23] and $data[2]) {
                DemandeConge::factory()->create([
                    'date_demande' => $data[8],
                    'date_retour' => $data[9],
                    'periode' => self::getPeriod(self::getDate($datesDebut), self::getDate($datesFin)),
                    'date_debut' => self::getDate($datesDebut),
                    'date_fin' => self::getDate($datesFin),
                    'etat_demande_id' => self::getEtatDemandeID($data[23]),
                    'user_id' => self::getUserID($data[2])
                ]);
//                $demande_state = EtatDemandeConge::where('id', self::getEtatDemandeID($data[23]))->first();
//                if (str_contains($demande_state->etat_demande, 'cancel') or str_contains($demande_state->etat_demande, 'close')) {
//
//                }
            }
        };

        Utils::getDataFromDBOrValidateInjectionFile($fillConges, $congesPath);
    }
}
