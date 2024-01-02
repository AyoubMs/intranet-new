<?php

namespace Database\Seeders;

use App\Http\Controllers\DemandeCongeController;
use App\Models\DemandeConge;
use App\Models\EtatDemandeConge;
use App\Models\TypeConge;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemandeCongeSeeder extends Seeder
{
    public static function getProchainValidateur($etat_demande_id, $role_id)
    {
        $is_created = $etat_demande_id === self::getEtatDemandeID("created");
        $is_agent = DemandeCongeController::isAgent($role_id);
        $is_validated_by_superviseur = $etat_demande_id === self::getEtatDemandeID("validated by supervisor");
        $is_validated_by_agent_wfm = in_array($etat_demande_id, array(self::getEtatDemandeID("validated by vigie"), self::getEtatDemandeID("validated by wfm"), self::getEtatDemandeID('validated by cps')));
        $is_validated_by_ops_manager = $etat_demande_id == self::getEtatDemandeID('validated by ops manager');
        $is_supervisor = DemandeCongeController::isSupervisor($role_id);
        $is_validated_by_directeur = $etat_demande_id === self::getEtatDemandeID('validated by director');
        $is_validated_by_resp_wfm = in_array($etat_demande_id, array(self::getEtatDemandeID('validated by coordinateur vigie'), self::getEtatDemandeID('validated by coordinateur cps')));
        $is_ops_manager = DemandeCongeController::isOpsManager($role_id);
        $is_resp_rh = DemandeCongeController::isResponsableRH($role_id);
        $is_resp_wfm = DemandeCongeController::isWFMCoordinator($role_id);
        $is_it_support = DemandeCongeController::isITAgent($role_id);
        $is_validated_by_resp_it = $etat_demande_id === self::getEtatDemandeID('validated by resp it');
        $is_resp_it = DemandeCongeController::isITResponsable($role_id);
        $is_agent_wfm = DemandeCongeController::isCPS($role_id) or DemandeCongeController::isVigie($role_id);
        $is_agent_moyens_generaux = DemandeCongeController::isAgentMG($role_id);
        $is_resp_moyens_generaux = DemandeCongeController::isRespMG($role_id);
        $is_charge_formation = DemandeCongeController::isChargeFormation($role_id);
        $is_validated_by_coordinateur_qualite_formation = $etat_demande_id === self::getEtatDemandeID('validated by coordinateur qualite formation');
        $is_validated_by_responsable_qualite_formation = $etat_demande_id === self::getEtatDemandeID('validated by responsable qualite formation');
        $is_coordinateur_qualite_formation = DemandeCongeController::isCoordinatorQualiteFormation($role_id);
        $is_resp_qualite_formation = DemandeCongeController::isResponsableQualiteFormation($role_id);
        $is_charge_rh = DemandeCongeController::isChargeRH($role_id);
        $is_validated_by_resp_rh = $etat_demande_id === self::getEtatDemandeID('validated by resp rh');
        $is_cps = DemandeCongeController::isCPS($role_id);
        $is_infirmiere_de_travail = DemandeCongeController::isInfirmiereDeTravail($role_id);
        $is_charge_mission_aupres_direction = DemandeCongeController::isChargeMissionAupresDirection($role_id);
        $is_charge_qualite_process = DemandeCongeController::isChargeQualiteProcess($role_id);
        $is_data_protection_officer = DemandeCongeController::isDataProtectionOfficer($role_id);
        $is_charge_comm_marketing = DemandeCongeController::isChargeCommMarketing($role_id);
        $is_charge_recrutement = DemandeCongeController::isChargeRecrutement($role_id);
        if ($is_agent) {
            // Agent funnel
            if ($is_created) {
                return array('superviseur', 'agent wfm');
            } else if ($is_validated_by_superviseur) {
                return 'agent wfm';
            } else if ($is_validated_by_agent_wfm) {
                return 'ops manager';
            } else if ($is_validated_by_ops_manager) {
                return array('charge rh', 'resp rh');
            }
        }
        else if ($is_supervisor) {
            // Supervisor funnel
            if ($is_created) {
                return array('ops manager', 'directeur');
            } else if ($is_validated_by_ops_manager) {
                return 'agent wfm';
            } else if ($is_validated_by_agent_wfm) {
                return 'charge rh';
            } else if ($is_validated_by_directeur) {
                return 'resp wfm';
            } else if ($is_validated_by_resp_wfm) {
                return 'resp rh';
            }
        }
        else if ($is_ops_manager) {
            if ($is_created) {
                return array('directeur', 'resp rh');
            } else if ($is_validated_by_directeur) {
                return 'resp rh';
            }
        }
        else if ($is_resp_rh) {
            if ($is_created) {
                return 'directeur';
            }
        }
        else if ($is_resp_wfm) {
            if ($is_created) {
                return array('directeur', 'resp rh');
            } else if ($is_validated_by_directeur) {
                return 'resp rh';
            }
        }
        else if ($is_it_support) {
            if ($is_created) {
                return 'resp it';
            } else if ($is_validated_by_resp_it) {
                return 'charge rh';
            }
        }
        else if ($is_resp_it) {
            if ($is_created) {
                return array('directeur', 'resp rh');
            } else if ($is_validated_by_directeur) {
                return 'resp rh';
            }
        }
        else if ($is_agent_wfm) {
            if ($is_created) {
                return array('resp wfm', 'directeur');
            } else if ($is_validated_by_resp_wfm) {
                return 'charge rh';
            } else if ($is_validated_by_directeur) {
                return 'resp rh';
            }
        }
        else if ($is_agent_moyens_generaux) {
            if ($is_created) {
                return 'resp rh';
            }
        }
        else if ($is_resp_moyens_generaux) {
            if ($is_created) {
                return array('directeur', 'resp rh');
            } else if ($is_validated_by_directeur) {
                return 'resp rh';
            }
        }
        else if ($is_charge_formation) {
            if ($is_created) {
                return array('coordinateur qualite formation', 'responsable qualite formation');
            } else if ($is_validated_by_coordinateur_qualite_formation) {
                return 'charge rh';
            } else if ($is_validated_by_responsable_qualite_formation) {
                return 'resp rh';
            }
        }
        else if ($is_coordinateur_qualite_formation) {
            if ($is_created) {
                return 'directeur';
            } else if ($is_validated_by_directeur) {
                return 'resp rh';
            }
        }
        else if ($is_resp_qualite_formation) {
            if ($is_created) {
                return array('directeur', 'resp rh');
            } else if ($is_validated_by_directeur) {
                return 'resp rh';
            }
        }
        else if ($is_charge_rh) {
            if ($is_created) {
                return array('resp rh', 'directeur');
            } else if ($is_validated_by_resp_rh) {
                return 'directeur';
            }
        }
        else if ($is_cps) {
            if ($is_created) {
                return array('resp wfm', 'directeur');
            } else if ($is_validated_by_resp_wfm) {
                return 'charge rh';
            } else if ($is_validated_by_directeur) {
                return 'resp rh';
            }
        }
        else if ($is_infirmiere_de_travail) {
            if ($is_created) {
                return array('resp rh', 'directeur');
            }
        }
        else if ($is_charge_mission_aupres_direction) {
            if ($is_created) {
                return 'directeur';
            } else if ($is_validated_by_directeur) {
                return 'resp rh';
            }
        }
        else if ($is_charge_qualite_process) {
            if ($is_created) {
                return array('coordinateur qualite formation', 'responsable qualite formation');
            } else if ($is_validated_by_coordinateur_qualite_formation) {
                return 'charge rh';
            } else if ($is_validated_by_responsable_qualite_formation) {
                return 'resp rh';
            }
        }
        else if ($is_data_protection_officer) {
            if ($is_created) {
                return 'directeur';
            } else if ($is_validated_by_resp_rh) {
                return 'resp rh';
            }
        }
        else if ($is_charge_comm_marketing) {
            if ($is_created) {
                return 'directeur';
            } else if ($is_validated_by_directeur) {
                return 'charge rh';
            }
        }
        else if ($is_charge_recrutement) {
            if ($is_created) {
                return 'responsable qualite formation';
            } else if ($is_validated_by_responsable_qualite_formation) {
                return 'resp rh';
            }
        }
        return '';
    }

    protected static function getTypeConge($data)
    {
        if ($data[13] or $data[14] or $data[15] or $data[16]) {
            return TypeConge::where('name', 'like', 'conge paye')->first()->id;
        } else if ($data[17] or $data[19]) {
            return TypeConge::where('name', 'like', 'evenement special')->first()->id;
        } else if ($data[20] or $data[21]) {
            return TypeConge::where('name', 'like', 'sans solde')->first()->id;
        }
        return 1;
    }

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
                return EtatDemandeConge::where('etat_demande', 'like', "clo%")->first()->id;
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
                $demand = DemandeConge::factory()->create([
                    'date_demande' => $data[8],
                    'date_retour' => $data[9],
                    'periode' => self::getPeriod(self::getDate($datesDebut), self::getDate($datesFin)),
                    'date_debut' => self::getDate($datesDebut),
                    'date_fin' => self::getDate($datesFin),
                    'etat_demande_id' => self::getEtatDemandeID($data[23]),
                    'user_id' => self::getUserID($data[2]),
                    'type_conge_id' => self::getTypeConge($data),
                    'nombre_jours' => $data[10],
                    'date_validation_niveau_1' => ($data[24] ?? null) === "" ? null : ($data[24] ?? null),
                ]);
                $user = User::where('id', $demand->user_id)->first();
                if ($user) {
                    $demand->prochain_validateur = self::getProchainValidateur($demand->etat_demande_id, $user->role_id);
                }
                $demand->save();
//                $demande_state = EtatDemandeConge::where('id', self::getEtatDemandeID($data[23]))->first();
//                if (str_contains($demande_state->etat_demande, 'cancel') or str_contains($demande_state->etat_demande, 'close')) {
//
//                }
            }
        };

        Utils::getDataFromDBOrValidateInjectionFile($fillConges, $congesPath);
    }
}
