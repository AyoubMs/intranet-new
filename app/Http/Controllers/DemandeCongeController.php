<?php

namespace App\Http\Controllers;

use App\Jobs\AcceptDemandJob;
use App\Jobs\LoadDataFromDB;
use App\Models\DemandeConge;
use App\Models\DemandeCongeLogs;
use App\Models\DemandeCongeStack;
use App\Models\EtatDemandeConge;
use App\Models\ModificationSoldeComment;
use App\Models\Role;
use App\Models\TypeConge;
use App\Models\User;

//use Illuminate\Database\Query\Builder;
use Dflydev\DotAccessData\Data;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Vtiful\Kernel\Excel;
use Illuminate\Database\Eloquent\Collection;

class DemandeCongeController extends Controller
{

    protected static function bundleConditionsAndQueries($input, $request)
    {
        return self::getDemands($input, $request);
    }

    protected static function getDataByType($type, $query, $isRoles = false, $etat_demande_ids = [], $user_ids = [])
    {
        dispatch(new LoadDataFromDB($query, $type, $isRoles, $etat_demande_ids, $user_ids, str_contains($type, 'demands')));
        if ($isRoles) {
            return json_decode(Redis::get($type . "_roles"));
        }
        return json_decode(Redis::get($type));
    }

    protected static function getProfileCondition($role_id)
    {
        if (self::isSupervisor($role_id)) {
            return true;
        } else if (self::isOpsManager($role_id)) {
            return true;
        } else if (self::isWFM($role_id)) {
            return true;
        } else if (self::isHR($role_id)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isHR($role_id)
    {
        return in_array($role_id, array_merge(...self::getHRIds()));
    }

    public static function isChargeRH($role_id)
    {
        $charge_rh_ids = Role::where('name', 'like', "%charge% rh")->pluck('id')->toArray();
        return in_array($role_id, $charge_rh_ids);
    }

    public static function isResponsableRH($role_id)
    {
        return $role_id === Role::where('name', 'responsable rh')->first()->id;
    }

    /**
     * @return array
     */
    protected static function WFMIds(): array
    {
        $wfm_ids = [];
        $wfm_ids[] = Role::where('name', 'like', "%statis%")->pluck('id')->toArray();
        $wfm_ids[] = Role::where('name', 'like', "coordinateur cps%")->pluck('id')->toArray();
        $wfm_ids[] = Role::where('name', 'like', "coordinateur vigie%")->pluck('id')->toArray();
        $wfm_ids[] = Role::where('name', 'like', "%correction%")->pluck('id')->toArray();
        $wfm_ids[] = Role::where('name', 'vigie')->pluck('id')->toArray();
        $wfm_ids[] = Role::where('name', 'head of operational excellence')->pluck('id')->toArray();
        return $wfm_ids;
    }

    public static function isWFM($role_id)
    {
        $wfm_ids = array_merge(...self::WFMIds());
        return in_array($role_id, $wfm_ids);
    }

    public static function isSupervisor($role_id)
    {
        return $role_id === Role::where('name', 'Superviseur')->first()->id;
    }

    public static function isOpsManager($role_id)
    {
        return in_array($role_id, Role::where('name', 'like', '%Opération%')->pluck('id')->toArray());
    }

    public static function isAgent($role_id)
    {
        return in_array($role_id, Role::where('name', 'like', "agent%")->orWhere('name', 'like', "expert%")->orWhere('name', 'like', "conseiller%")->pluck('id')->toArray());
    }

    public static function isDirector($role_id)
    {
        return $role_id === Role::where('name', 'directeur')->first()->id;
    }

    public static function isVigie($role_id)
    {
        return $role_id === Role::where('name', 'vigie')->first()->id;
    }

    public static function isCPS($role_id)
    {
        return in_array($role_id, Role::where('name', 'like', "%statis%")->pluck('id')->toArray());
    }

    public static function isCCI($role_id)
    {
        return $role_id === Role::where('name', 'like', "%correction%")->first()->id;
    }

    public static function isCoordinator($role_id)
    {
        return in_array($role_id, Role::where('name', 'like', "%coordina%")->pluck('id')->toArray());
    }

    public static function isHeadOfOperationalExcellence($role_id)
    {
        return $role_id === Role::where('name', 'head of operational excellence')->first()->id;
    }

    protected static function getOpsManagersIds($type)
    {
        if ($type === 'roles') {
            return self::getDataByType('getOpsManagersIds', "", true);
        }
        return self::getDataByType('getOpsManagersIds', "");
    }

    protected static function getWFMCoordinatorIds($type)
    {
        if ($type === 'roles') {
            return self::getDataByType('getWFMCoordinatorIds', "", true);
        }
        return self::getDataByType('getWFMCoordinatorIds', "");
    }

    protected static function getHeadOfOperationalExcellenceIds($type)
    {
        if ($type === 'roles') {
            return self::getDataByType('getHeadOfOperationalExcellenceIds', "", true);
        }
        return self::getDataByType('getHeadOfOperationalExcellenceIds', "");
    }

    protected static function getVigieCoordinatorIds()
    {
        return self::getDataByType('getVigieCoordinatorIds', "");
    }

    protected static function getVigieIds()
    {
        return self::getDataByType('getVigieIds', "");
    }

    protected static function getCPSIds()
    {
        return self::getDataByType('getCPSIds', "");
    }

    protected static function getCPSCoordinatorIds()
    {
        return self::getDataByType('getCPSCoordinatorIds', "");
    }

    protected static function getWFMAgentsIds()
    {
        return self::getDataByType('getWFMAgentsIds', "");
    }

    protected static function getChargeRHIds($type)
    {
        if ($type === 'roles') {
            return self::getDataByType('getChargeRHIds', "", true);
        }
        return self::getDataByType('getChargeRHIds', "");
    }

    /**
     * @param array $input
     * @return mixed
     */
    protected static function getDemands(array $input, $request, $export = false)
    {
        $query = DemandeConge::when($input, function ($query, $input) {
            foreach ($input as $key => $value) {
                switch ($key) {
                    case 'date_demande_debut':
                        if (!is_null($value)) {
                            $query->whereDate('date_demande', '>=', $value);
                        }
                        break;
                    case 'date_demande_fin':
                        if (!is_null($value)) {
                            $query->whereDate('date_demande', '<=', $value);
                        }
                        break;
                    case 'date_debut_conge_debut':
                        if (!is_null($value)) {
                            $query->whereDate('date_debut', '>=', $value);
                        }
                        break;
                    case 'date_debut_conge_fin':
                        if (!is_null($value)) {
                            $query->whereDate('date_debut', '<=', $value);
                        }
                        break;
                    case 'date_fin_conge_debut':
                        if (!is_null($value)) {
                            $query->whereDate('date_fin', '>=', $value);
                        }
                        break;
                    case 'date_fin_conge_fin':
                        if (!is_null($value)) {
                            $query->whereDate('date_fin', '<=', $value);
                        }
                        break;
                    case 'user_ids':
                        if (is_null($input['matricule'])) {
                            if (!empty($value) and !self::isHR($input['role']->id) and !self::isCoordinator($input['role']->id)
                            ) {
                                $user_ids = array_merge(...$value);
                                $query->whereIn('user_id', $user_ids);
                            } else if (self::isSupervisor($input['role']->id)) {
                                $query->whereIn('user_id', $value);
                            } else if (self::isCoordinator($input['role']->id)) {
                                $user_ids = array_merge(...$value);
                                $query->whereIn('user_id', $user_ids);
                            }
                        }
                        break;
                    case 'user_id':
                        $user_ids = [];
                        if (!is_null($value)) {
                            $user_ids[] = array($value);
                        }
                        if (self::isSupervisor($input['role']->id)) {
                            if (!empty($input['agent_ids'])) {
                                $user_ids[] = array_merge(...$input['agent_ids']);
                            }
                        }
                        if (self::isOpsManager($input['role']->id) || self::isWFM($input['role']->id) and !empty($input['supervisor_ids'])) {
                            $user_ids[] = $input['supervisor_ids'];
                        }
                        if (self::isOpsManager($input['role']->id) || self::isWFM($input['role']->id) and !empty($input['agent_ids'])) {
                            $user_ids[] = $input['agent_ids'] ?? [];
                        }
                        if ($input['principal_user']->id === $value && self::isHR($input['role']->id) and !empty($input['supervisor_ids']) and !empty($input['agent_ids'])) {
                            $user_ids[] = array_merge(...$input['supervisor_ids']);
                            $user_ids[] = array_merge(...$input['agent_ids']);
                            $user_ids[] = self::getOpsManagersIds('');
                            $user_ids[] = self::getHeadOfOperationalExcellenceIds('roles');
                        }
                        if (self::isDirector($input['role']->id)) {
                            $user_ids[] = self::getSupervisorIds("");
                            $user_ids[] = self::getOpsManagersIds('');
                            // head of operational excellence
                            $user_ids[] = self::getHeadOfOperationalExcellenceIds("");
                        }
                        if (self::isITResponsable($input['role']->id)) {
                            $user_ids[] = self::getITAgentIds("");
                        }
                        if (is_null($input['matricule'])) {
                            $user_ids = array_merge(...$user_ids);
                            $query->whereIn('user_id', $user_ids);
                        } else {
                            $query->where('user_id', $value);
                        }
                        break;
                    case 'role':
                        $proprietary_demands = self::getDataByType('proprietary_demands', "", "", EtatDemandeConge::pluck('id')->toArray(), array($input['principal_user']->id)) ?? [];
                        if (self::isResponsableQualiteFormation($value->id) and is_null($input['matricule'])) {
                            $charge_formation_demands_for_resp_qualite_formation_ids = self::getDataByType('charge_formation_demands_for_resp_qualite_formation_ids' . $input['principal_user']->id, '', false, EtatDemandeConge::whereNot('etat_demande', 'canceled')->pluck('id')->toArray(), self::getChargeFormationIds('')) ?? [];
                            $charge_qualite_process_demands_for_responsable_qualite_formation_ids = self::getDataByType('charge_qualite_process_demands_for_responsable_qualite_formation_ids' . $input['principal_user']->id, '', false, EtatDemandeConge::whereNot('etat_demande', 'canceled')->pluck('id')->toArray(), self::getChargeQualiteProcessIds('')) ?? [];
                            $charge_recrutement_demands_for_responsable_qualite_formation_ids = self::getDataByType('charge_recrutement_demands_for_responsable_qualite_formation_ids' . $input['principal_user']->id, '', false, EtatDemandeConge::whereNot('etat_demande', 'canceled')->pluck('id')->toArray(), self::getChargeRecrutementIds('')) ?? [];
                            $responsable_formation_demands_for_resp_qualite_formation_ids = self::getDataByType('responsable_formation_demands_for_resp_qualite_formation_ids' . $input['principal_user']->id, '', false, EtatDemandeConge::whereNot('etat_demande', 'canceled')->pluck('id')->toArray(), self::getResponsableFormationIds('')) ?? [];
                            $query->orWhereIn('id', $charge_formation_demands_for_resp_qualite_formation_ids)->orWhereIn('id', $charge_qualite_process_demands_for_responsable_qualite_formation_ids)->orWhereIn('id', $charge_recrutement_demands_for_responsable_qualite_formation_ids)->orWhereIn('id', $responsable_formation_demands_for_resp_qualite_formation_ids)->orWhereIn('id', $proprietary_demands);
                        } else if (self::isCoordinatorQualiteFormation($value->id) and is_null($input['matricule'])) {
                            $charge_formation_demands_for_coordinator_qualite_formation_ids = self::getDataByType('charge_formation_demands_for_coordinator_qualite_formation_ids' . $input['principal_user']->id, '', false, EtatDemandeConge::whereNot('etat_demande', 'canceled')->pluck('id')->toArray(), self::getChargeFormationIds('')) ?? [];
                            $charge_qualite_process_demands_for_coordinator_qualite_formation_ids = self::getDataByType('charge_qualite_process_demands_for_coordinator_qualite_formation_ids' . $input['principal_user']->id, '', false, EtatDemandeConge::whereNot('etat_demande', 'canceled')->pluck('id')->toArray(), self::getChargeQualiteProcessIds('')) ?? [];
                            $query->orWhereIn('id', $charge_qualite_process_demands_for_coordinator_qualite_formation_ids)->orWhereIn('id', $charge_formation_demands_for_coordinator_qualite_formation_ids)->orWhereIn('id', $proprietary_demands);
                        } else if (self::isWFM($value->id)) {
                            if (is_null($input['matricule'])) {
                                $query->whereNotIn('etat_demande_id', EtatDemandeConge::where('etat_demande', 'created')->pluck('id')->toArray());
                            }
                            if (is_null($input['matricule']) and !self::isCoordinator($value->id) and !self::isHeadOfOperationalExcellence($value->id) and !empty($input['agent_ids'])) {
                                $user_ids = $input['agent_ids'];
                                if (self::isDirector($value->id)) {
                                    $user_ids[] = self::getSupervisorIds("");
                                }
                                if (gettype($user_ids[0]) === 'array') {
                                    $user_ids = array_merge(...$user_ids);
                                }
                                $agent_created_demands = self::getDataByType('agent_created_demands_for_wfm' . $input['principal_user']->id, '', false, EtatDemandeConge::where('etat_demande', 'created')->pluck('id')->toArray(), $user_ids) ?? [];
                                $query->orWhereIn('id', $proprietary_demands)->orWhereIn('id', $agent_created_demands);
                            }
                            $user_ids = [];
                            if (self::isCoordinator($value->id)) {
                                $user_ids[] = self::getVigieIds();
                                $user_ids[] = self::getCPSIds();
                                $user_ids[] = self::getCCIIds("");
                                $user_ids = array_merge(...$user_ids);
                                if (is_null($input['matricule'])) {
                                    $query->orWhereIn('id', $proprietary_demands);
                                }
                            }
                            if (self::isHeadOfOperationalExcellence($value->id)) {
                                $user_ids[] = self::getVigieCoordinatorIds();
                                $user_ids[] = self::getCPSCoordinatorIds();
                                $user_ids = array_merge(...$user_ids);
                            }
                            if (is_null($input['matricule'])) {
                                $query->orWhereIn('user_id', $user_ids);
                            }
                        } else if (self::isOpsManager($value->id) and !self::isHeadOfOperationalExcellence($value->id) and !empty($input['supervisor_ids'])) {
                            $supervisor_created_demands = self::getDataByType('supervisor_created_demands_for_ops_manager' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), $input['supervisor_ids']) ?? [];
                            $query->whereNotIn('etat_demande_id', EtatDemandeConge::where('etat_demande', 'created')->pluck('id')->toArray());
                            if (is_null($input['matricule'])) {
                                $query->orWhereIn('id', $proprietary_demands)->orWhereIn('id', $supervisor_created_demands);
                            }
                        } else if (self::isHR($value->id)) {
                            if (is_null($input['matricule'])) {
                                $query->whereIn('etat_demande_id', EtatDemandeConge::whereNotIn('etat_demande', ['created', 'validated by supervisor'])->pluck('id')->toArray());
                            }
                            if (self::isResponsableRH($value->id) and is_null($input['matricule'])) {
                                if (!str_contains($input['matricule'], $input['principal_user']->matricule)) {
                                    $opsmanager_created_demands_for_resp_hr = self::getDataByType('opsmanager_created_demands_resp_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getOpsManagersIds('')) ?? [];
                                    $developer_created_demands_for_resp_hr = self::getDataByType('developer_created_demands_resp_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getDeveloperIds('')) ?? [];
                                    $agent_mg_created_demands_for_resp_hr = self::getDataByType('agent_mg_created_demands_resp_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getAgentMGIds('')) ?? [];
                                    $wfm_agents_demands_for_resp_hr = self::getDataByType('wfm_agents_demands_resp_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('validated by director')), self::getWFMAgentsIds('')) ?? [];
                                    $charge_rh_demands_for_resp_hr = self::getDataByType('charge_rh_demands_resp_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getChargeRHIds('')) ?? [];
                                    $resp_mg_created_demands_for_resp_hr = self::getDataByType('resp_mg_created_demands_resp_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getResponsableMGIds('')) ?? [];
                                    $resp_mg_validated_by_director_demands_for_resp_hr = self::getDataByType('resp_mg_validated_by_director_demands_resp_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('validated by director')), self::getResponsableMGIds('')) ?? [];
                                    $infirmiere_travail_created_demands_for_resp_hr = self::getDataByType('infirmiere_travail_created_demands_resp_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getInfirmiereTravailIds('')) ?? [];
                                    $charge_mission_aupres_direction_validated_by_director_for_resp_hr = self::getDataByType('charge_mission_aupres_direction_validated_by_director_demands_resp_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('validated by director')), self::getChargeMissionAupresDirectionIds('')) ?? [];
                                    $charge_formation_demands_for_coordinator_qualite_formation_ids_for_resp_hr = self::getDataByType('charge_formation_demands_for_coordinator_qualite_formation_ids_resp_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('validated by coordinateur qualite formation')), self::getChargeFormationIds('')) ?? [];
                                    $charge_formation_demands_for_resp_qualite_formation_ids_for_resp_hr = self::getDataByType('charge_formation_demands_for_resp_qualite_formation_ids' . $input['principal_user']->id, '', false, array(self::getEtatDemande('validated by responsable qualite formation')), self::getChargeFormationIds('')) ?? [];
                                    $charge_qualite_process_demands_for_responsable_qualite_formation_ids_for_resp_hr = self::getDataByType('charge_qualite_process_demands_for_resp_qualite_formation_ids' . $input['principal_user']->id, "", false, array(self::getEtatDemande('validated by responsable qualite formation')), self::getChargeQualiteProcessIds('')) ?? [];
                                    $coordinator_qualite_formation_demands_validated_by_director_for_resp_hr = self::getDataByType('coordinator_qualite_formation_demands_validated_by_director_for_resp_hr' . $input['principal_user']->id, "", false, array(self::getEtatDemande('validated by director')), self::getCoordinatorQualiteFormationIds('')) ?? [];
                                    $responsable_formation_demands_validated_by_resp_qualite_formation_for_resp_hr = self::getDataByType("responsable_formation_demands_validated_by_resp_qualite_formation_for_resp_hr" . $input['principal_user']->id, "", false, array(self::getEtatDemande('validated by responsable qualite formation')), self::getResponsableFormationIds('')) ?? [];
                                    $data_protection_officer_demands_validated_by_director_for_resp_hr = self::getDataByType('data_protection_officer_demands_validated_by_director_for_resp_hr'.$input['principal_user']->id, "", false, array(self::getEtatDemande('validated by director')), self::getDataProtectionOfficerIds(''));
                                    $demand_ids_for_resp_hr = [];
                                    $demand_ids_for_resp_hr[] = $opsmanager_created_demands_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $wfm_agents_demands_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $charge_rh_demands_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $developer_created_demands_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $agent_mg_created_demands_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $resp_mg_created_demands_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $resp_mg_validated_by_director_demands_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $infirmiere_travail_created_demands_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $charge_mission_aupres_direction_validated_by_director_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $charge_formation_demands_for_resp_qualite_formation_ids_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $charge_formation_demands_for_coordinator_qualite_formation_ids_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $charge_qualite_process_demands_for_responsable_qualite_formation_ids_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $coordinator_qualite_formation_demands_validated_by_director_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $responsable_formation_demands_validated_by_resp_qualite_formation_for_resp_hr;
                                    $demand_ids_for_resp_hr[] = $data_protection_officer_demands_validated_by_director_for_resp_hr;
                                    $demand_ids_for_resp_hr = array_merge(...$demand_ids_for_resp_hr);
                                    $query->orWhereIn('id', $demand_ids_for_resp_hr)->orWhereIn('id', $proprietary_demands);
                                }
                            } else if (self::isChargeRH($value->id) and is_null($input['matricule'])) {
                                $wfm_agents_demands_for_resp_hr = self::getDataByType('wfm_agents_demands_charge_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('validated by coordinateur cps'), self::getEtatDemande('validated by coordinateur vigie')), self::getWFMAgentsIds()) ?? [];
                                $it_agents_demands_ids = self::getDataByType('it_agents_demands_ids_charge_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('validated by resp it')), self::getITAgentIds('')) ?? [];
                                $charge_comm_mktg_validated_by_director_demands = self::getDataByType('charge_comm_mktg_validated_by_director_demands_charge_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('validated by director')), self::getChargeCommMktgIds('')) ?? [];
                                $charge_formation_validated_by_coordinator_qualite_formation_demands_charge_hr = self::getDataByType('charge_formation_validated_by_coordinator_qualite_formation_demands_charge_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('validated by coordinateur qualite formation')), self::getChargeFormationIds('')) ?? [];
                                $charge_qualite_process_validated_by_coordinator_qualite_formation_demands_charge_hr = self::getDataByType('charge_qualite_process_validated_by_coordinator_qualite_formation_demands_charge_hr' . $input['principal_user']->id, '', false, array(self::getEtatDemande('validated by coordinateur qualite formation')), self::getChargeQualiteProcessIds('')) ?? [];
                                $demand_ids = [];
                                $demand_ids[] = $it_agents_demands_ids;
                                $demand_ids[] = $wfm_agents_demands_for_resp_hr;
                                $demand_ids[] = $charge_comm_mktg_validated_by_director_demands;
                                $demand_ids[] = $charge_formation_validated_by_coordinator_qualite_formation_demands_charge_hr;
                                $demand_ids[] = $charge_qualite_process_validated_by_coordinator_qualite_formation_demands_charge_hr;
                                $demand_ids = array_merge(...$demand_ids);
                                $query->orWhereIn('id', $demand_ids)->orWhereIn('id', $proprietary_demands);
                            }
                        } else if (self::isSupervisor($value->id) and is_null($input['matricule'])) {
                            $query->whereIn('etat_demande_id', EtatDemandeConge::pluck('id')->toArray());
                        } else if (self::isDirector($value->id) and is_null($input['matricule'])) {
                            $resprh_created_demands = self::getDataByType('resprh_created_demands_director' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getResponsableHRIds('')) ?? [];
                            $role_ids = [];
                            $role_ids[] = Role::where('name', 'like', "%statis%")->pluck('id')->toArray();
                            $role_ids[] = self::getChargeRHIds('roles');
                            $role_ids = array_merge(...$role_ids);
                            $role_ids[] = Role::where('name', 'like', "vigie")->first()->id;
                            $role_ids[] = Role::where('name', 'like', "%incoh%")->first()->id;
                            $created_demands_profiles_ids = [];
                            $created_demands_profiles_ids[] = self::getWFMAgentsIds();
                            $created_demands_profiles_ids[] = self::getChargeRHIds('');
                            $created_demands_profiles_ids[] = self::getCoordinatorQualiteFormationIds('');
                            $created_demands_profiles_ids[] = self::getDataProtectionOfficerIds('');
                            $created_demands_profiles_ids = array_merge(...$created_demands_profiles_ids);
                            $agent_wfm_and_chargehr_demands = self::getDataByType('agent_wfm_and_chargehr_demands_director' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created'), self::getEtatDemande('validated by resp hr')), $created_demands_profiles_ids) ?? [];
                            $resp_it_created_demands = self::getDataByType('resp_it_created_demands_director' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getResponsableITIds('')) ?? [];
                            $resp_mg_created_demands_for_resp_hr = self::getDataByType('resp_mg_created_demands_director' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getResponsableMGIds('')) ?? [];
                            $infirmiere_travail_created_demands_for_resp_hr = self::getDataByType('infirmiere_travail_created_demands_director' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getInfirmiereTravailIds('')) ?? [];
                            $charge_mission_aupres_direction_created_demands = self::getDataByType('charge_mission_aupres_direction_created_demands_director' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getChargeMissionAupresDirectionIds('')) ?? [];
                            $charge_comm_mktg_created_demands = self::getDataByType('charge_comm_mktg_created_demands_director' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getChargeCommMktgIds('')) ?? [];
                            $charge_recrutement_demands_validated_by_resp_qualite_formation = self::getDataByType('charge_recrutement_demands_validated_by_resp_qualite_formation' . $input['principal_user']->id, '', false, array(self::getEtatDemande('validated by responsable qualite formation')), self::getChargeRecrutementIds('')) ?? [];
                            $query->orWhereIn('id', $resprh_created_demands)->orWhereIn('id', $agent_wfm_and_chargehr_demands)->orWhereIn('id', $resp_it_created_demands)->orWhereIn('id', $resp_mg_created_demands_for_resp_hr)->orWhereIn('id', $infirmiere_travail_created_demands_for_resp_hr)->orWhereIn('id', $charge_mission_aupres_direction_created_demands)->orWhereIn('id', $charge_comm_mktg_created_demands)->orWhereIn('id', $charge_recrutement_demands_validated_by_resp_qualite_formation);
                        } else if (self::isITResponsable("")) {
                            $it_agents_demands_ids = self::getDataByType('it_agents_demands_ids_it_resp' . $input['principal_user']->id, '', false, array(self::getEtatDemande('created')), self::getITAgentIds('')) ?? [];
                            $query->whereIn('id', $it_agents_demands_ids);
                        }
                        break;
                }
            }
        })->orderBy('etat_demande_id', 'asc')->orderBy('date_demande', 'desc')->orderBy('date_retour', 'desc')->with('user')->with('demand')->with('typeDemande');
        if (!$export) {
            return $query->paginate(7);
        } else {
            return $query->get();
        }
    }

    public static function rejectDemand($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $user = json_decode(Redis::get($request->headers->get('Uuid')));
        if (self::isResponsableQualiteFormation($user->role_id)) {
            $demand->etat_demande_id = self::getEtatDemande('rejected by responsable qualite formation');
        } else if (self::isCoordinatorQualiteFormation($user->role_id)) {
            $demand->etat_demande_id = self::getEtatDemande('rejected by coordinateur qualite formation');
        } else if (self::isITResponsable($user->role_id)) {
            $demand->etat_demande_id = self::getEtatDemande("rejected by resp it");
        } else if (self::isSupervisor($user->role_id)) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by sup%")->first()->id;
        } else if (self::isOpsManager($user->role_id) and !self::isHeadOfOperationalExcellence($user->role_id)) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by %ops%")->first()->id;
        } else if (self::isWFM($user->role_id)) {
            if (self::isVigie($user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by vigie")->first()->id;
            } else if (self::isCPS($user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by cps")->first()->id;
            } else if (self::isCCI($user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by cci")->first()->id;
            } else if (self::isCoordinator($user->role_id)) {
                if (str_contains(strtolower($user->role->name), 'vigie')) {
                    $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by coordinateur vigie")->first()->id;
                } else if (str_contains(strtolower($user->role->name), 'cps')) {
                    $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by coordinateur cps")->first()->id;
                }
            } else if (self::isHeadOfOperationalExcellence($user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by head%")->first()->id;
            } else {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by %wfm")->first()->id;
            }
        } else if (self::isHR($user->role_id)) {
            if (self::isChargeRH($user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', "rejected by charge hr")->first()->id;
            } else if (self::isResponsableRH($user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', "rejected by resp hr")->first()->id;
            } else {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', "rejected by hr")->first()->id;
            }
        } else if (self::isDirector($user->role_id)) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'rejected by director')->first()->id;
        }
        // reset the soldes
        $rejector = $user;
        $user = User::where('matricule', $demand->user->matricule)->first();
        self::resetTheSoldes($demand, $user, $rejector);
        return "";
    }

    public static function cancelDemand($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $user = json_decode(Redis::get($request->headers->get('Uuid')));
        $user = User::where('matricule', $user->matricule)->first();
        $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "can%")->first()->id;
        // reset the soldes
        return self::resetTheSoldes($demand, $user, $user);
    }

    public static function acceptDemand($request)
    {
//        dispatch(new AcceptDemandJob($request->all(), $request->headers->get('Uuid')));
//        info(json_encode($request->all()));
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $user = json_decode(Redis::get($request->headers->get('Uuid')));
        $user = User::where('matricule', $user->matricule)->first();
        if (self::isITResponsable($user->role_id)) {
            $demand->etat_demande_id = self::getEtatDemande("validated by resp it");
        }
        if (self::isSupervisor($user->role_id)) {
            $demand->etat_demande_id = self::getEtatDemande("validated by sup%");
        }
        if (self::isWFM($user->role_id)) {
            if (self::isVigie($user->role_id)) {
                $demand->etat_demande_id = self::getEtatDemande("validated by vigie");
            }
            if (self::isCPS($user->role_id)) {
                $demand->etat_demande_id = self::getEtatDemande("validated by cps");
            }
            if (self::isCCI($user->role_id)) {
                $demand->etat_demande_id = self::getEtatDemande("validated by cci");
            }
            if (self::isCoordinator($user->role_id)) {
                if (str_contains(strtolower($user->role->name), 'vigie')) {
                    $demand->etat_demande_id = self::getEtatDemande('validated by coordinateur vigie');
                }
                if (str_contains(strtolower($user->role->name), 'cps')) {
                    $demand->etat_demande_id = self::getEtatDemande('validated by coordinateur cps');
                }
            }
            if (self::isHeadOfOperationalExcellence($user->role_id)) {
                $demand->etat_demande_id = self::getEtatDemande('validated by head%');
            }
        }
        if (self::isHR($user->role_id)) {
            if (self::isResponsableRH($user->role_id) and self::isChargeRH($demand->user->role_id)) {
                $demand->etat_demande_id = self::getEtatDemande('closed');
            } else {
                $demand->etat_demande_id = self::getEtatDemande('closed');
            }
        }
        if (self::isDirector($user->role_id)) {
            if (self::isResponsableRH($demand->user->role_id)) {
                $demand->etat_demande_id = self::getEtatDemande('closed');
            } else if (self::isChargeRH($demand->user->role_id)) {
                $demand->etat_demande_id = self::getEtatDemande('closed');
            } else {
                $demand->etat_demande_id = self::getEtatDemande('validated by director');
            }
        }
//        $demand->save();
        $demand->save();
        return $demand;
    }

    public static function getAffectedDemands($request)
    {
        $user = User::where('matricule', $request['data'])->first();
        $role_id = $user->role_id;

        list($agent_ids, $supervisor_ids) = self::getAgentIdsAndSupervisorIds($user->role_id, $user);
        if (self::isResponsableQualiteFormation($role_id)) {
            $charge_formation_demands_for_responsable_qualite_formation = (new Collection(self::getDataByType('charge_formation_demands_for_responsable_qualite_formation' . $user->id, "", false, array(self::getEtatDemande('created')), self::getChargeFormationIds(''))))->count();
            $charge_qualite_process_demands_for_responsable_qualite_formation = (new Collection(self::getDataByType('charge_qualite_process_demands_for_responsable_qualite_formation' . $user->id, "", false, array(self::getEtatDemande('created')), self::getChargeQualiteProcessIds(''))))->count();
            $charge_recrutement_demands_for_responsable_qualite_formation = (new Collection(self::getDataByType('charge_recrutement_demands_for_responsable_qualite_formation' . $user->id, "", false, array(self::getEtatDemande('created')), self::getChargeRecrutementIds(''))))->count();
            $responsable_formation_created_demands_for_resp_qualite_formation = (new Collection(self::getDataByType('responsable_formation_created_demands_for_resp_qualite_formation' . $user->id, "", false, array(self::getEtatDemande('created')), self::getResponsableFormationIds(''))))->count();
            return $charge_formation_demands_for_responsable_qualite_formation + $charge_qualite_process_demands_for_responsable_qualite_formation + $charge_recrutement_demands_for_responsable_qualite_formation + $responsable_formation_created_demands_for_resp_qualite_formation;
        } else if (self::isCoordinatorQualiteFormation($role_id)) {
            $charge_formation_demands_for_coordinator_qualite_formation = (new Collection(self::getDataByType('charge_formation_demands_for_coordinator_qualite_formation' . $user->id, "", false, array(self::getEtatDemande('created')), self::getChargeFormationIds(''))))->count();
            $charge_qualite_process_demands_for_coordinator_qualite_formation = (new Collection(self::getDataByType('charge_qualite_process_demands_for_coordinator_qualite_formation' . $user->id, "", false, array(self::getEtatDemande('created')), self::getChargeQualiteProcessIds(''))))->count();
            return $charge_formation_demands_for_coordinator_qualite_formation + $charge_qualite_process_demands_for_coordinator_qualite_formation;
        } else if (self::isITResponsable($role_id)) {
            return (new Collection(self::getDataByType('it_agent_created_demands_for_it_responsable' . $user->id, "", false, array(self::getEtatDemande('created')), self::getITAgentIds(''))))->count();
        } else if (self::isSupervisor($role_id)) {
            $user_ids = array_merge(...$agent_ids);
            return (new Collection(self::getDataByType('agent_created_demands_for_supervisor' . $user->id, "", false, array(self::getEtatDemande('created')), $user_ids)))->count();
        } else if (self::isOpsManager($role_id) and !self::isHeadOfOperationalExcellence($role_id)) {
            $agent_count = (new Collection(self::getDataByType('agent_count_demands_for_ops_manager' . $user->id, "", false, array(self::getEtatDemande('validated by vigie'), self::getEtatDemande('validated by cps')), $agent_ids)))->count();
            $supervisor_count = (new Collection(self::getDataByType('supervisor_count_demands_for_ops_manager' . $user->id, "", false, array(self::getEtatDemande('created')), $supervisor_ids)))->count();
            return $agent_count + $supervisor_count;
        } else if (self::isWFM($role_id)) {
            $agent_count = 0;
            $supervisor_count = 0;
            $vigie_count = 0;
            $cps_count = 0;
            $cci_count = 0;
            if (self::isCPS($role_id) || self::isVigie($role_id)) {
                $agent_count = (new Collection(self::getDataByType('agent_count_demands_for_cps_or_vigie' . $user->id, "", false, array(self::getEtatDemande('validated by supervisor')), $agent_ids)))->count();
                $supervisor_count = (new Collection(self::getDataByType('supervisor_count_demands_for_cps_or_vigie' . $user->id, "", false, array(self::getEtatDemande('validated by ops manager')), $supervisor_ids)))->count();
            }
            if (self::isCoordinator($role_id) and self::isWFM($role_id)) {
                $vigie_count = (new Collection(self::getDataByType('vigie_count_demands_for_wfm_coordinator' . $user->id, "", false, array(self::getEtatDemande('created')), self::getVigieIds())))->count();
                $cps_count = (new Collection(self::getDataByType('cps_count_demands_for_wfm_coordinator' . $user->id, '', false, array(self::getEtatDemande('created')), self::getCPSIds())))->count();
                $cci_count = (new Collection(self::getDataByType('cci_count_demands_for_wfm_coordinator' . $user->id, '', false, array(self::getEtatDemande('created')), self::getCCIIds(''))))->count();
                $supervisor_count = (new Collection(self::getDataByType('supervisor_count_demands_for_wfm_coordinator' . $user->id, "", false, array(self::getEtatDemande('validated by director')), $supervisor_ids)))->count();
            } else if (self::isHeadOfOperationalExcellence($role_id)) {
                $vigie_count = (new Collection(self::getDataByType('vigie_count_demands_hoe' . $user->id, '', false, array(self::getEtatDemande('created')), self::getVigieCoordinatorIds())))->count();
                $cps_count = (new Collection(self::getDataByType('cps_count_demands_hoe' . $user->id, '', false, array(self::getEtatDemande('created')), self::getCPSCoordinatorIds())))->count();
            }
            return $agent_count + $supervisor_count + $vigie_count + $cps_count + $cci_count;
        } else if (self::isHR($role_id)) {
            $created_demands_from_profiles_rel_to_resprh = 0;
            $agent_wfm_validated_by_coordinators_wfm = 0;
            $agent_wfm_validated_by_director = 0;
            $wfm_coordinators_validated_by_director = 0;
            $opsmanager_validated_by_director = 0;
            $supervisor_validated_by_agent_wfm_demands = 0;
            $supervisor_validated_by_coordinators_wfm_demands = 0;
            $it_agent_validated_by_resp_it = 0;
            $resp_it_validated_by_director = 0;
            $resp_mg_validated_by_director = 0;
            $charge_mission_aupres_direction_validated_by_director = 0;
            $charge_comm_mktg_validated_by_director = 0;
            $charge_formation_demands_validated_by_coordinator_qualite_formation = 0;
            $charge_formation_demands_validated_by_responsable_qualite_formation = 0;
            $charge_qualite_process_demands_validated_by_coordinator_qualite_formation = 0;
            $charge_recrutement_demands_validated_by_responsable_qualite_formation = 0;
            $coordinator_qualite_formation_demands_validated_by_director = 0;
            $responsable_formation_validated_by_resp_qualite_formation_demands = 0;
            $data_protection_officer_validated_by_director_demands = 0;
            $agent_validated_by_ops_manager = (new Collection(self::getDataByType('agent_demands_validated_by_ops_manager_hr' . $user->id, "", false, array(self::getEtatDemande('validated by ops manager')), self::getAgentIds(''))))->count();
            if (self::isResponsableRH($role_id)) {
                $user_ids = [];
                $user_ids[] = self::getOpsManagersIds('');
                $user_ids[] = self::getWFMCoordinatorIds('');
                $user_ids[] = self::getChargeRHIds('');
                $user_ids[] = self::getResponsableITIds("");
                $user_ids[] = self::getDeveloperIds("");
                $user_ids[] = self::getAgentMGIds("");
                $user_ids[] = self::getResponsableMGIds("");
                $user_ids[] = self::getInfirmiereTravailIds("");
                $user_ids = array_merge(...$user_ids);
                $created_demands_from_profiles_rel_to_resprh = (new Collection(self::getDataByType('created_demands_from_profiles_rel_to_resprh' . $user->id, '', false, array(self::getEtatDemande('created')), $user_ids)))->count();
                $agent_wfm_validated_by_director = (new Collection(self::getDataByType('agent_wfm_demands_validated_by_director_resphr' . $user->id, "", false, array(self::getEtatDemande('validated by director')), self::getWFMAgentsIds())))->count();
                $resp_mg_validated_by_director = (new Collection(self::getDataByType('resp_mg_demands_validated_by_director_resphr' . $user->id, "", false, array(self::getEtatDemande('validated by director')), self::getResponsableMGIds(''))))->count();
                $wfm_coordinators_validated_by_director = (new Collection(self::getDataByType('wfm_coordinators_demands_validated_by_director_resphr' . $user->id, "", false, array(self::getEtatDemande('validated by director')), self::getWFMCoordinatorIds(''))))->count();
                $supervisor_validated_by_coordinators_wfm_demands = (new Collection(self::getDataByType('supervisor_demands_validated_by_coordinators_wfm_demands_resphr' . $user->id, "", false, array(self::getEtatDemande('validated by coordinateur cps'), self::getEtatDemande('validated by coordinateur vigie')), self::getSupervisorIds(''))))->count();
                $resp_it_validated_by_director = (new Collection(self::getDataByType('resp_it_demands_validated_by_director_resphr' . $user->id, "", false, array(self::getEtatDemande('validated by director')), self::getResponsableITIds(""))))->count();
                $charge_mission_aupres_direction_validated_by_director = (new Collection(self::getDataByType('charge_mission_aupres_direction_demands_validated_by_director' . $user->id, "", false, array(self::getEtatDemande('validated by director')), self::getChargeMissionAupresDirectionIds(""))))->count();
                $charge_formation_demands_validated_by_responsable_qualite_formation = (new Collection(self::getDataByType('charge_formation_demands_validated_by_responsable_qualite_formation' . $user->id, "", false, array(self::getEtatDemande('validated by responsable qualite formation')), self::getChargeFormationIds(''))))->count();
                $charge_recrutement_demands_validated_by_responsable_qualite_formation = (new Collection(self::getDataByType('charge_recrutement_demands_validated_by_responsable_qualite_formation' . $user->id, "", false, array(self::getEtatDemande("validated by responsable qualite formation")), self::getChargeRecrutementIds(''))))->count();
                $coordinator_qualite_formation_demands_validated_by_director = (new Collection(self::getDataByType('coordinator_qualite_formation_demands_validated_by_director' . $user->id, "", false, array(self::getEtatDemande('validated by director')), self::getCoordinatorQualiteFormationIds(''))))->count();
                $responsable_formation_validated_by_resp_qualite_formation_demands = (new Collection(self::getDataByType('responsable_formation_validated_by_resp_qualite_formation_demands' . $user->id, "", false, array(self::getEtatDemande('validated by responsable qualite formation')), self::getResponsableFormationIds(''))))->count();
                $data_protection_officer_validated_by_director_demands = (new Collection(self::getDataByType('data_protection_officer_validated_by_director_demands' . $user->id, "", false, array(self::getEtatDemande('validated by director')), self::getDataProtectionOfficerIds(''))))->count();
            } else if (self::isChargeRH($role_id)) {
                $agent_wfm_validated_by_coordinators_wfm = (new Collection(self::getDataByType('agent_wfm_demands_validated_by_coordinators_wfm_chargehr' . $user->id, "", false, array(self::getEtatDemande('created')), self::getWFMAgentsIds())))->count();
                $supervisor_validated_by_agent_wfm_demands = (new Collection(self::getDataByType('supervisor_demands_validated_by_agent_wfm_demands_chargehr' . $user->id, "", false, array(self::getEtatDemande('validated by cps'), self::getEtatDemande('validated by vigie')), self::getSupervisorIds(''))))->count();
                $it_agent_validated_by_resp_it = (new Collection(self::getDataByType("it_agent_demands_validated_by_resp_it_chargehr" . $user->id, "", false, array(self::getEtatDemande('validated by resp it')), self::getAgentIds(''))))->count();
                $charge_comm_mktg_validated_by_director = (new Collection(self::getDataByType("charge_comm_mktg_demands_validated_by_director" . $user->id, "", false, array(self::getEtatDemande('validated by director')), self::getChargeCommMktgIds(''))))->count();
                $charge_formation_demands_validated_by_coordinator_qualite_formation = (new Collection(self::getDataByType('charge_formation_demands_validated_by_coordinator_qualite_formation' . $user->id, "", false, array(self::getEtatDemande('validated by coordinateur qualite formation')), self::getChargeFormationIds(''))))->count();
                $charge_qualite_process_demands_validated_by_coordinator_qualite_formation = (new Collection(self::getDataByType('charge_qualite_process_demands_validated_by_coordinator_qualite_formation' . $user->id, "", false, array(self::getEtatDemande('validated by coordinateur qualite formation')), self::getChargeQualiteProcessIds(""))))->count();
            }
            return $created_demands_from_profiles_rel_to_resprh + $agent_wfm_validated_by_coordinators_wfm + $agent_wfm_validated_by_director + $wfm_coordinators_validated_by_director + $opsmanager_validated_by_director + $supervisor_validated_by_agent_wfm_demands + $supervisor_validated_by_coordinators_wfm_demands + $agent_validated_by_ops_manager + $it_agent_validated_by_resp_it + $resp_it_validated_by_director + $resp_mg_validated_by_director + $charge_mission_aupres_direction_validated_by_director + $charge_comm_mktg_validated_by_director + $charge_formation_demands_validated_by_coordinator_qualite_formation + $charge_formation_demands_validated_by_responsable_qualite_formation + $charge_qualite_process_demands_validated_by_coordinator_qualite_formation + $charge_recrutement_demands_validated_by_responsable_qualite_formation + $coordinator_qualite_formation_demands_validated_by_director + $responsable_formation_validated_by_resp_qualite_formation_demands + $data_protection_officer_validated_by_director_demands;
        } else if (self::isDirector($role_id)) {
            $supervisor_created_demands_ids_count = (new Collection(self::getDataByType('supervisor_created_demands_ids_for_director' . $user->id, "", false, array(self::getEtatDemande('created')), self::getSupervisorIds(''))))->count();
            $user_ids[] = self::getOpsManagersIds('');
            $user_ids[] = self::getWFMCoordinatorIds('');
            $user_ids[] = self::getCPSIds();
            $user_ids[] = self::getCCIIds("");
            $user_ids[] = self::getVigieIds();
            $user_ids[] = self::getChargeRHIds('');
            $user_ids[] = self::getHeadOfOperationalExcellenceIds('');
            $user_ids[] = self::getResponsableITIds("");
            $user_ids[] = self::getResponsableMGIds("");
            $user_ids[] = self::getInfirmiereTravailIds("");
            $user_ids[] = self::getChargeMissionAupresDirectionIds("");
            $user_ids[] = self::getChargeCommMktgIds("");
            $user_ids[] = self::getCoordinatorQualiteFormationIds("");
            $user_ids[] = self::getDataProtectionOfficerIds("");
            $user_ids = array_merge(...$user_ids);
            $created_demands_from_profiles = (new Collection(self::getDataByType('all_demands_for_profiles_rel_to_director' . $user->id, "", false, array(self::getEtatDemande('created')), $user_ids)))->count();
            $resprh_created_demands = (new Collection(self::getDataByType('resprh_created_demands_for_director' . $user->id, "", false, array(self::getEtatDemande('created')), self::getResponsableHRIds(""))))->count();
            $charge_rh_validated_by_resp_rh = (new Collection(self::getDataByType("charge_rh_demands_validated_by_resp_rh_for_director" . $user->id, "", false, array(self::getEtatDemande('validated by resp hr')), self::getChargeRHIds(''))))->count();
            $agent_wfm_created_demands = (new Collection(self::getDataByType("agent_wfm_created_demands_for_director" . $user->id, "", false, array(self::getEtatDemande('created')), self::getWFMAgentsIds())))->count();
            return $created_demands_from_profiles + $supervisor_created_demands_ids_count + $resprh_created_demands + $charge_rh_validated_by_resp_rh + $agent_wfm_created_demands;
        }
        return null;
    }

    public static function getLatestDemand($request)
    {
//        $user = User::where('matricule', $request['data']['matricule'])->first();
//        if ($user) {
//            if (!$user->conges->isEmpty()) {
//                return $user->conges->toQuery()->whereNotIn('etat_demande_id', EtatDemandeConge::whereIn('etat_demande', ['canceled', 'rejected'])->pluck('id')->toArray())->orderBy('date_retour', 'desc')->first();
//            }
//        }
        return null;
    }

    public static function createDemand($request)
    {
        $period = doubleval($request['data']['nombre_jours']);

        $user = User::where('matricule', $request['data']['matricule'])->first();

        $type_conge = $request['data']['type_conge'];
        $demand = DemandeConge::factory()->create([
            'date_demande' => today(),
            'date_retour' => $request['data']['date_retour'],
            'date_debut' => $request['data']['date_debut'],
            'date_fin' => $request['data']['date_fin'],
            'periode' => $request['data']['date_debut'] . " - " . $request['data']['date_fin'],
            'etat_demande_id' => EtatDemandeConge::where('etat_demande', 'created')->first()->id,
            'user_id' => $user->id,
            'nombre_jours' => $period,
            'type_conge_id' => self::getTypeCongeId($type_conge)
        ]);

        $solde_rjf = $user->solde_rjf;
        $demand_stack_elem = DemandeCongeStack::factory()->create([
            'demande_conge_id' => $demand->id,
            'user_id' => $user->id
        ]);
        self::correctSoldes($type_conge, $period, $solde_rjf, $user, $demand_stack_elem, $user);
        Redis::set($request->headers->get('Uuid'), json_encode($user));

        return $demand;

    }

    public static function searchDemands($request)
    {
        return self::bundleConditionsAndQueries(self::getDemandsData($request['data'], $request), $request);
    }

    public static function exportDemandsFile($request)
    {
        $headerCells = ['A1' => 'Matricule', 'B1' => 'Name', 'C1' => 'Operations', 'D1' => 'Managers', 'E1' => 'Nombre de jours', 'F1' => 'Date Validation Niveau 1', 'G1' => 'Date Validation Niveau 2', 'H1' => 'Date Validation Niveau 3', 'I1' => 'Date Validation Niveau 4', 'J1' => 'Date Demande', 'K1' => 'Date Retour', 'L1' => 'Periode', 'M1' => 'Etat Demande'];
        $input = self::getDemandsData($request['data'], $request);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($headerCells as $headerCell => $title) {
            $index = 2;
            $sheet->setCellValue($headerCell, $title);
            foreach (self::getDemands($input, $request, true) as $demand) {
                if ($headerCell[0] === 'A') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->user->matricule);
                } else if ($headerCell[0] === 'B') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->user->first_name . " " . $demand->user->last_name);
                } else if ($headerCell[0] === 'C') {
                    $sheet->setCellValue($headerCell[0] . $index, self::getOperations($demand->user->operations));
                } else if ($headerCell[0] === 'D') {
                    $sheet->setCellValue($headerCell[0] . $index, self::getManagers($demand->user->managers));
                } else if ($headerCell[0] === 'E') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->nombre_jours);
                } else if ($headerCell[0] === 'F') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->date_validation_niveau_1);
                } else if ($headerCell[0] === 'G') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->date_validation_niveau_2);
                } else if ($headerCell[0] === 'H') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->date_validation_niveau_3);
                } else if ($headerCell[0] === 'I') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->date_validation_niveau_4);
                } else if ($headerCell[0] === 'J') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->date_demande);
                } else if ($headerCell[0] === 'K') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->date_retour);
                } else if ($headerCell[0] === 'L') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->periode);
                } else if ($headerCell[0] === 'M') {
                    $sheet->setCellValue($headerCell[0] . $index, self::getStateEtatDemande($demand->demand->etat_demande));
                }
                $index++;
            }
        }
        $demandsPath = storage_path() . '\app\public\export-demands-files\demands.xlsx';

        try {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($demandsPath);
        } catch (Exception $e) {
            info(json_encode($e));
        }


        return response()->file($demandsPath);
    }

    /**
     * @param $data
     * @return array
     */
    public static function getDemandsData($data, $request): array
    {
        $dateDemandeDebut = $data['date_demande_debut'];
        $dateDemandeFin = $data['date_demande_fin'];
        $dateDebutCongeDebut = $data['date_debut_conge_debut'];
        $dateDebutCongeFin = $data['date_debut_conge_fin'];
        $dateFinCongeDebut = $data['date_fin_conge_debut'];
        $dateFinCongeFin = $data['date_fin_conge_fin'];
        $principal_user = User::where('matricule', json_decode(Redis::get($request->headers->get('Uuid')))->matricule ?? '')->with('operations')->first();
        list($user_id, $user_ids) = self::getUserIdsAndUserId($principal_user, $request['data']['matricule']);
        $user = User::where('matricule', $request['data']['matricule'])->first();
        if ($user) {
            $user_id = $user->id;
        }
        list($agent_ids, $supervisor_ids) = self::getAgentIdsAndSupervisorIds($principal_user->role->id, $principal_user);
//        info(json_encode($supervisor_ids));
        $supervisor_ids_output = $supervisor_ids;
        $agent_ids_output = $agent_ids;
//        $supervisor_ids_output = [];
//        $agent_ids_output = [];
        if (is_null($user_id)) {
            $user_id = json_decode(Redis::get($request->headers->get('Uuid')))->id;
        }
        $role = User::where('id', json_decode(Redis::get($request->headers->get('Uuid')))->id)->first()->role;
        return array('date_demande_debut' => $dateDemandeDebut, 'date_demande_fin' => $dateDemandeFin, 'date_debut_conge_debut' => $dateDebutCongeDebut, 'date_debut_conge_fin' => $dateDebutCongeFin, 'date_fin_conge_debut' => $dateFinCongeDebut, 'date_fin_conge_fin' => $dateFinCongeFin, 'user_id' => $user_id, 'user_ids' => $user_ids, 'role' => $role, 'agent_ids' => $agent_ids_output, 'supervisor_ids' => $supervisor_ids_output, 'principal_user' => $principal_user, 'matricule' => $request['data']['matricule']);
    }

    /**
     * @param $principal_user
     * @param $matricule
     * @return array
     */
    protected static function getUserIdsAndUserId($principal_user, $matricule): array
    {
        $user_id = null;
        $user_ids = [];
        if (!is_null($principal_user)) {
            if (self::getProfileCondition($principal_user->role->id)) {
                foreach ($principal_user->operations as $operation) {
                    $user_ids[] = $operation->users->pluck('id')->toArray();
                }
            } else {
                $user_id = User::where('matricule', $matricule ?? $principal_user->matricule)->first()->id ?? null;
            }
        }
        return array($user_id, $user_ids);
    }

    /**
     * @return array
     */
    protected static function getHRIds(): array
    {
        $hr_ids = [];
        $hr_ids[] = Role::where('name', 'like', "%rh%")->pluck('id')->toArray();
        $hr_ids[] = Role::where('name', 'like', "%humain%")->pluck('id')->toArray();
        return $hr_ids;
    }

    /**
     * @param $role_id
     * @param array $user_ids_from_manager
     * @param $user
     * @return array[]
     */
    public static function getAgentIdsAndSupervisorIds($role_id, $user): array
    {
        $agent_ids = [];
        $supervisor_ids = [];
        $user_ids_from_manager = [];
        if (!$user->users->isEmpty()) {
            foreach ($user->users as $user) {
                $user_ids_from_manager[] = $user->id;
            }
        }
        if (self::isSupervisor($role_id)) {
            $agent_ids[] = $user_ids_from_manager;
        } else if (self::isOpsManager($role_id) || self::isWFM($role_id)) {
            foreach ($user->operations as $operation) {
                foreach ($operation->users as $user) {
                    if (self::isAgent($user->role_id)) {
                        $agent_ids[] = $user->id;
                    } else if (self::isSupervisor($user->role_id)) {
                        $supervisor_ids[] = $user->id;
                    }
                }
            }
        } else if (self::isHR($role_id)) {
            $agent_ids[] = User::where('active', true)->whereIn('role_id', Role::where('name', 'like', "agent%")->orWhere('name', 'like', "expert%")->orWhere('name', 'like', "conseiller%")->pluck('id')->toArray())->pluck('id')->toArray();
            $supervisor_ids[] = User::where('active', true)->where('role_id', Role::where('name', 'Superviseur')->first()->id)->pluck('id')->toArray();
        }
        return array($agent_ids, $supervisor_ids);
    }

    public static function refreshDemand(Request $request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->type_conge_id = $request['data']['type_conge_id'];
        $conge_paye_id = TypeConge::where('name', 'conge paye')->first()->id;
        if ($demand->type_conge_id === $conge_paye_id) {
            $user = $demand->user;
            $demand_stack_elem = DemandeCongeStack::where('demande_conge_id', $demand->id)->first();
            $nombre_jours_confirmed = doubleval($request['data']['nombre_jours_confirmed']);
            if (is_null($demand_stack_elem)) {
                $demand_stack_elem = DemandeCongeStack::factory()->create([
                    "demande_conge_id" => $demand->id,
                    "user_id" => $user->id,
                    "solde_cp" => 0,
                    "solde_rjf" => $demand->nombre_jours
                ]);
                $demand_stack_elem->save();
            }
            $validator = json_decode(Redis::get($request->headers->get('Uuid')));
            self::resetTheSoldes($demand, $user, $validator);
            self::correctSoldes("conge paye", $nombre_jours_confirmed, $user->solde_rjf, $user, $demand_stack_elem, $validator);
            $demand->nombre_jours = doubleval($request['data']['nombre_jours_confirmed']);
            $user->save();
        }
        $demand->save();
        return $demand;
    }

    public static function acceptDemandOpsManager(Request $request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande("validated by ops%");
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandITResponsable($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande("validated by resp it");
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandSupervisor($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande("validated by sup%");
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandVigie($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande("validated by vigie");
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandCPS($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande("validated by cps");
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandCCI($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande("validated by cci");
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandCoordinatorVigie($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande('validated by coordinateur vigie');
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandCoordinatorCPS($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande('validated by coordinateur cps');
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandHeadOfOperationalExcellence($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande('validated by head of operational excellence');
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandResponsableRHOrChargeRHOrClose($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande('closed');
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandDirector($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande('validated by director');
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandCoordinatorQualiteFormation(Request $request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande('validated by coordinateur qualite formation');
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function acceptDemandResponsableQualiteFormation(Request $request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $demand->etat_demande_id = self::getEtatDemande('validated by responsable qualite formation');
        self::setDateValidation($request['data'], $demand);
        $demand->save();
        return $demand;
    }

    public static function getDemandesCongeLogs(Request $request)
    {
        $user = json_decode(Redis::get($request->headers->get('Uuid')));
        return DemandeCongeLogs::where('user_id', $user->id)->whereNotNull('modification_solde_comment_id')->orderBy('id', 'desc')->paginate();
    }

    protected static function filterNulls($val)
    {
        return !is_null($val);
    }

    /**
     * @param $demand
     * @param $user
     * @return mixed
     */
    public static function resetTheSoldes($demand, $user, $principal_user)
    {
        $demande_conge_stack_element = DemandeCongeStack::where('demande_conge_id', $demand->id)->first();
        if (!is_null($demande_conge_stack_element)) {
            $demande_conge_log_element = DemandeCongeLogs::factory()->create([
                "modifier_id" => $principal_user->id,
                "nouveau_solde_cp" => $user->solde_cp + $demande_conge_stack_element->solde_cp,
                "nouveau_solde_rjf" => $user->solde_rjf + $demande_conge_stack_element->solde_rjf,
                "ancien_solde_cp" => $user->solde_cp,
                "ancien_solde_rjf" => $user->solde_rjf,
                "modification_solde_comment_id" => UserController::getSoldeCommentId("%Consommation par demande de congé%"),
                "user_id" => $user->id
            ]);
            $demande_conge_log_element->save();
            $user->solde_cp = $user->solde_cp + $demande_conge_stack_element->solde_cp;
            $user->solde_rjf = $user->solde_rjf + $demande_conge_stack_element->solde_rjf;
            $demande_conge_stack_element->delete();
            $demande_conge_stack_element->save();
        }
        $demand->save();
        $user->save();
        return $user;
    }

    protected static function getCCIIds()
    {
        return User::where('role_id', Role::where('name', 'like', "%incoh%")->first()->id)->pluck('id')->toArray();
    }

    public static function getEtatDemande(string $string)
    {
        return EtatDemandeConge::where('etat_demande', 'like', $string)->first()->id;
    }

    protected static function getResponsableITIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getResponsableITIds', "", true);
        }
        return self::getDataByType('getResponsableITIds', "");
    }

    protected static function getResponsableMGIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getResponsableMGIds', "", true);
        }
        return self::getDataByType('getResponsableMGIds', "");
    }

    protected static function getInfirmiereTravailIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getInfirmiereTravailIds', "", true);
        }
        return self::getDataByType('getInfirmiereTravailIds', "");
    }

    protected static function getChargeMissionAupresDirectionIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getChargeMissionAupresDirectionIds', "", true);
        }
        return self::getDataByType('getChargeMissionAupresDirectionIds', "");
    }

    protected static function getChargeCommMktgIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getChargeCommMktgIds', "", true);
        }
        return self::getDataByType('getChargeCommMktgIds', "");
    }

    protected static function getDeveloperIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getDeveloperIds', "", true);
        }
        return self::getDataByType('getDeveloperIds', "");
    }

    protected static function getAgentMGIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getAgentMGIds', "", true);
        }
        return self::getDataByType("getAgentMGIds", "");
    }

    protected static function getSupervisorIds($roles)
    {
        $role_id = Role::where('name', 'superviseur')->first()->id;
        if ($roles === 'roles') {
            return $role_id;
        }
        return User::where('role_id', $role_id)->pluck('id')->toArray();
    }

    private static function getAgentIds($roles)
    {
        $role_ids = Role::where('name', 'like', "agent%")->orWhere('name', 'like', "expert%")->orWhere('name', 'like', "conseiller%")->pluck('id')->toArray();
        if ($roles === 'roles') {
            return $role_ids;
        }
        return User::whereIn('role_id', $role_ids)->pluck('id')->toArray();
    }

    /**
     * @param $type_conge
     * @return mixed
     */
    protected static function getTypeCongeId($type_conge)
    {
        return TypeConge::where('name', 'like', $type_conge)->first()->id;
    }

    /**
     * @param $type_conge
     * @param float $period
     * @param $solde_rjf
     * @param $user
     * @param $demand_stack_elem
     * @param $request
     * @return void
     */
    public static function correctSoldes($type_conge, float $period, $solde_rjf, $user, $demand_stack_elem, $validator): void
    {
        if ($type_conge === "conge paye") {
            $demande_conge_log_element = DemandeCongeLogs::factory()->create([
                "modifier_id" => $validator->id,
                "ancien_solde_cp" => $user->solde_cp,
                "ancien_solde_rjf" => $user->solde_rjf,
                "modification_solde_comment_id" => UserController::getSoldeCommentId("%Consommation par demande de congé%"),
                "user_id" => $user->id
            ]);
//            - $demand_stack_elem->solde_cp
            if ($period >= $solde_rjf) {
                $period = $period - $solde_rjf;
                $user->solde_rjf = 0;
                $user->solde_cp = $user->solde_cp - $period;
                $demand_stack_elem->solde_cp = $period;
                $demand_stack_elem->solde_rjf = $solde_rjf;
            } else {
                $user->solde_rjf = $solde_rjf - $period;
                $demand_stack_elem->solde_cp = 0;
                $demand_stack_elem->solde_rjf = $period;
            }
            $demande_conge_log_element->nouveau_solde_cp = $user->solde_cp;
            $demande_conge_log_element->nouveau_solde_rjf = $user->solde_rjf;
            $demande_conge_log_element->save();
        } else {
            $demand_stack_elem->solde_cp = 0;
            $demand_stack_elem->solde_rjf = 0;
        }

        $demand_stack_elem->save();

        $user->save();
    }

    public static function isCoordinatorQualiteFormation($role_id)
    {
        return $role_id === Role::where('name', 'like', "coordinateur %")->where('name', 'like', "%qualite%")->where('name', 'like', "%formation")->first()->id;
    }

    protected static function getCoordinatorQualiteFormationIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getCoordinatorQualiteFormationIds', "", true);
        }
        return self::getDataByType('getCoordinatorQualiteFormationIds', '');
    }

    public static function isITResponsable($role_id)
    {
        return $role_id === Role::where('name', 'like', "responsable it")->first()->id;
    }

    private static function getITAgentIds($roles)
    {
        $role_ids = Role::whereIn('name', ['informaticien', 'stagiaire it'])->pluck('id')->toArray();
        if ($roles === "roles") {
            return $role_ids;
        }
        return User::whereIn('role_id', $role_ids)->pluck('id')->toArray();
    }

    private static function getResponsableHRIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getResponsableHRIds', "", true);
        }
        return self::getDataByType('getResponsableHRIds', '');
    }

    private static function getChargeFormationIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getChargeFormationIds', "", true);
        }
        return self::getDataByType('getChargeFormationIds', '');
    }

    private static function getOperations($operations)
    {
        $all_operations_string = "";
        foreach ($operations as $operation) {
            $all_operations_string .= $operation->name . " - ";
        }
        return substr($all_operations_string, 0, -3);
    }

    private static function getManagers($managers)
    {
        $all_managers_names_with_operations = "";
        foreach ($managers as $manager) {
            $all_managers_names_with_operations .= $manager->first_name . " " . $manager->last_name . " (";
            foreach ($manager->operations as $operation) {
                $all_managers_names_with_operations .= $operation->name;
            }
            $all_managers_names_with_operations .= " )\n";
        }
        return $all_managers_names_with_operations;
    }

    private static function getStateEtatDemande($etat_demande)
    {
        if (str_contains($etat_demande, 'rejected') or str_contains($etat_demande, 'closed') or str_contains($etat_demande, 'canceled')) {
            return $etat_demande;
        } else {
            return $etat_demande . " (en cours)";
        }
    }

    private static function isResponsableQualiteFormation($role_id)
    {
        return $role_id === Role::where('name', 'like', "responsable%")->where('name', 'like', "%qualit%")->where('name', 'like', "%formation%")->first()->id;
    }

    private static function getChargeQualiteProcessIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getChargeQualiteProcessIds', "", true);
        }
        return self::getDataByType('getChargeQualiteProcessIds', '');
    }

    private static function getChargeRecrutementIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getChargeRecrutementIds', "", true);
        }
        return self::getDataByType('getChargeRecrutementIds', '');
    }

    private static function getResponsableFormationIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getResponsableFormationIds', "", true);
        }
        return self::getDataByType('getResponsableFormationIds', '');
    }

    private static function getDataProtectionOfficerIds($roles)
    {
        if ($roles === "roles") {
            return self::getDataByType('getDataProtectionOfficerIds', "", true);
        }
        return self::getDataByType('getDataProtectionOfficerIds', '');
    }

    /**
     * @param $data
     * @param $demand
     * @return void
     */
    protected static function setDateValidation($data, $demand): void
    {
        $date_validation_number = intval($data['date_validation_number']);
        if ($date_validation_number === 1) {
            $demand->date_validation_niveau_1 = $data['date_validation'];
        } else if ($date_validation_number === 2) {
            $demand->date_validation_niveau_2 = $data['date_validation'];
        } else if ($date_validation_number === 3) {
            $demand->date_validation_niveau_3 = $data['date_validation'];
        } else if ($date_validation_number === 4) {
            $demand->date_validation_niveau_4 = $data['date_validation'];
        }
        $demand->save();
    }
}
