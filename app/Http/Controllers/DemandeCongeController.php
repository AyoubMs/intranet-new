<?php

namespace App\Http\Controllers;

use App\Models\DemandeConge;
use App\Models\DemandeCongeStack;
use App\Models\EtatDemandeConge;
use App\Models\Role;
use App\Models\TypeConge;
use App\Models\User;

//use Illuminate\Database\Query\Builder;
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

    protected static function isHR($role_id)
    {
        return in_array($role_id, array_merge(...self::getHRIds()));
    }

    protected static function isChargeRH($role_id)
    {
        $charge_rh_ids = Role::where('name', 'like', "%charge% rh")->pluck('id')->toArray();
        return in_array($role_id, $charge_rh_ids);
    }

    protected static function isResponsableRH($role_id)
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

    protected static function isWFM($role_id)
    {
        $wfm_ids = array_merge(...self::WFMIds());
        return in_array($role_id, $wfm_ids);
    }

    protected static function isSupervisor($role_id)
    {
        return $role_id === Role::where('name', 'Superviseur')->first()->id;
    }

    protected static function isOpsManager($role_id)
    {
        return in_array($role_id, Role::where('name', 'like', '%Opération%')->pluck('id')->toArray());
    }

    protected static function isAgent($role_id)
    {
        return in_array($role_id, Role::where('name', 'like', "agent%")->orWhere('name', 'like', "expert%")->orWhere('name', 'like', "conseiller%")->pluck('id')->toArray());
    }

    protected static function isDirector($role_id)
    {
        return $role_id === Role::where('name', 'directeur')->first()->id;
    }

    protected static function isVigie($role_id)
    {
        return $role_id === Role::where('name', 'vigie')->first()->id;
    }

    protected static function isCPS($role_id)
    {
        return in_array($role_id, Role::where('name', 'like', "%statis%")->pluck('id')->toArray());
    }

    protected static function isCCI($role_id)
    {
        return $role_id === Role::where('name', 'like', "%correction%")->first()->id;
    }

    protected static function isCoordinator($role_id)
    {
        return in_array($role_id, Role::where('name', 'like', "%coordina%")->pluck('id')->toArray());
    }

    protected static function isHeadOfOperationalExcellence($role_id)
    {
        return $role_id === Role::where('name', 'head of operational excellence')->first()->id;
    }

    protected static function getOpsManagersIds($type)
    {
        $roles_ids = Role::where('name', 'like', '%operation%')->whereNot('name', 'like', 'head%')->pluck('id')->toArray();
        if ($type === 'roles') {
            return $roles_ids;
        }
        return User::whereIn('role_id', $roles_ids)->pluck('id')->toArray();
    }

    protected static function getWFMCoordinatorIds($type)
    {
        $vigie_coordinators_role_ids = Role::where('name', 'like', 'coordinateur vigie')->first()->id;
        $cps_coordinators_role_ids = Role::where('name', 'like', 'coordinateur cps')->first()->id;
        $role_ids = [];
        $role_ids[] = $vigie_coordinators_role_ids;
        $role_ids[] = $cps_coordinators_role_ids;
        if ($type === 'roles') {
            return $role_ids;
        }
        return User::whereIn('role_id', $role_ids)->pluck('id')->toArray();
    }

    protected static function getHeadOfOperationalExcellenceIds($type)
    {
        $role_ids = Role::where('name', 'like', '%operation%')->where('name', 'like', 'head%')->pluck('id')->toArray();
        if ($type === 'roles') {
            return $role_ids;
        }
        return User::where('role_id', $role_ids)->pluck('id')->toArray();
    }

    protected static function getVigieCoordinatorIds()
    {
        return User::where('role_id', Role::where('name', 'coordinateur vigie')->first()->id)->where('active', true)->pluck('id')->toArray();
    }

    protected static function getVigieIds()
    {
        return User::where('role_id', Role::where('name', 'vigie')->first()->id)->where('active', true)->pluck('id')->toArray();
    }

    protected static function getCPSIds()
    {
        return User::whereIn('role_id', Role::where('name', 'like', "%statis%")->pluck('id')->toArray())->where('active', true)->pluck('id')->toArray();
    }

    protected static function getCPSCoordinatorIds()
    {
        return User::where('role_id', Role::where('name', 'coordinateur cps')->first()->id)->where('active', true)->pluck('id')->toArray();
    }

    protected static function getWFMAgentsIds()
    {
        $cps_role_ids = Role::where('name', 'like', "%statis%")->pluck('id')->toArray();
        $vigie_role_ids = Role::where('name', 'vigie')->pluck('id')->toArray();
        $cci_role_ids = Role::where('name', 'like', "%incoh%")->pluck('id')->toArray();
        $role_ids = [];
        $role_ids[] = $cps_role_ids;
        $role_ids[] = $vigie_role_ids;
        $role_ids[] = $cci_role_ids;
        $role_ids = array_merge(...$role_ids);
        return User::whereIn('role_id', $role_ids)->where('active', true)->pluck('id')->toArray();
    }

    protected static function getChargeRHIds($type)
    {
        $role_ids = Role::where('name', 'like', "%charge% rh")->pluck('id')->toArray();
        if ($type === 'roles') {
            return $role_ids;
        }
        return User::whereIn('role_id', $role_ids)->where('active', true)->pluck('id')->toArray();
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
                        break;
                    case 'user_id':
                        $user_ids = [];
                        if (!is_null($value)) {
                            $user_ids[] = array($value);
                        }
                        if (self::isSupervisor($input['role']->id)) {
                            $user_ids[] = array_merge(...$input['agent_ids']);
                        }
                        if (self::isOpsManager($input['role']->id) || self::isWFM($input['role']->id)) {
                            $user_ids[] = $input['supervisor_ids'];
                        }
                        if (self::isOpsManager($input['role']->id) || self::isWFM($input['role']->id)) {
                            $user_ids[] = $input['agent_ids'];
                        }
                        if ($input['principal_user']->id === $value && self::isHR($input['role']->id)) {
                            $user_ids[] = array_merge(...$input['supervisor_ids']);
                            $user_ids[] = array_merge(...$input['agent_ids']);
                            $user_ids[] = self::getOpsManagersIds('');
                            $user_ids[] = Role::where('name', 'like', '%operation%')->where('name', 'like', 'head%')->pluck('id')->toArray();
                        }
                        if (self::isDirector($input['role']->id)) {
                            $supervisor_ids = Role::where('name', 'Superviseur')->pluck('id')->toArray();
                            $user_ids[] = User::whereIn('role_id', $supervisor_ids)->pluck('id')->toArray();
                            $user_ids[] = self::getOpsManagersIds('');
                            // head of operational excellence
                            $user_ids[] = User::whereIn('role_id', Role::where('name', 'like', '%operation%')->where('name', 'like', 'head%')->pluck('id')->toArray())->pluck('id')->toArray();
                        }
                        if (is_null($input['matricule'])) {
                            $user_ids = array_merge(...$user_ids);
                            $query->whereIn('user_id', $user_ids);
                        } else {
                            $query->where('user_id', $value);
                        }
                        break;
                    case 'role':
                        $proprietary_demands = DemandeConge::whereIn('etat_demande_id', EtatDemandeConge::pluck('id')->toArray())->where('user_id', $input['principal_user']->id)->pluck('id')->toArray();
                        if (self::isWFM($value->id)) {
                            if (is_null($input['matricule'])) {
                                $query->whereNotIn('etat_demande_id', EtatDemandeConge::where('etat_demande', 'created')->pluck('id')->toArray());
                            }
                            if (is_null($input['matricule']) and !self::isCoordinator($value->id) and !self::isHeadOfOperationalExcellence($value->id)) {
                                $user_ids = $input['agent_ids'];
                                if (self::isDirector($value->id)) {
                                    $supervisor_ids = Role::where('name', 'Superviseur')->pluck('id')->toArray();
                                    $user_ids[] = User::whereIn('role_id', $supervisor_ids)->pluck('id')->toArray();
                                }
                                if (gettype($user_ids[0]) === 'array') {
                                    $user_ids = array_merge(...$user_ids);
                                }
                                $agent_created_demands = DemandeConge::whereIn('etat_demande_id', EtatDemandeConge::where('etat_demande', 'created')->pluck('id')->toArray())->whereIn('user_id', $user_ids)->pluck('id')->toArray();
                                $query->orWhereIn('id', $proprietary_demands)->orWhereIn('id', $agent_created_demands);
                            }
                            $user_ids = [];
                            if (self::isCoordinator($value->id)) {
                                $user_ids[] = self::getVigieIds();
                                $user_ids[] = self::getCPSIds();
                                $user_ids[] = self::getCCIIds();
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
                        } else if (self::isOpsManager($value->id) and !self::isHeadOfOperationalExcellence($value->id)) {
                            $supervisor_created_demands = DemandeConge::where('etat_demande_id', EtatDemandeConge::where('etat_demande', 'created')->first()->id)->whereIn('user_id', $input['supervisor_ids'])->pluck('id')->toArray();
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
                                    $opsmanager_created_demands = DemandeConge::where('etat_demande_id', EtatDemandeConge::where('etat_demande', 'created')->first()->id)->where('user_id', self::getOpsManagersIds(''))->pluck('id')->toArray();
                                    $wfm_agents_demands = DemandeConge::whereIn('etat_demande_id', EtatDemandeConge::whereIn('etat_demande', ['validated by director'])->pluck('id')->toArray())->whereIn('user_id', self::getWFMAgentsIds())->pluck('id')->toArray();
                                    $charge_rh_demands = DemandeConge::whereIn('etat_demande_id', EtatDemandeConge::whereIn('etat_demande', ['created'])->pluck('id')->toArray())->whereIn('user_id', self::getChargeRHIds())->pluck('id')->toArray();
                                    $demand_ids = [];
                                    $demand_ids[] = $opsmanager_created_demands;
                                    $demand_ids[] = $wfm_agents_demands;
                                    $demand_ids[] = $charge_rh_demands;
                                    $demand_ids = array_merge(...$demand_ids);
                                    $query->orWhereIn('id', $demand_ids)->orWhereIn('id', $proprietary_demands);
                                }
                            } else if (self::isChargeRH($value->id) and is_null($input['matricule'])) {
                                $wfm_agents_demands = DemandeConge::whereIn('etat_demande_id', EtatDemandeConge::whereIn('etat_demande', ['validated by coordinateur cps', 'validated by coordinateur vigie'])->pluck('id')->toArray())->whereIn('user_id', self::getWFMAgentsIds())->pluck('id')->toArray();
                                $query->orWhereIn('id', $wfm_agents_demands)->orWhereIn('id', $proprietary_demands);
                            }
                        } else if (self::isSupervisor($value->id) and is_null($input['matricule'])) {
                            $query->whereIn('etat_demande_id', EtatDemandeConge::pluck('id')->toArray());
                        } else if (self::isDirector($value->id) and is_null($input['matricule'])) {
                            $resprh_created_demands = DemandeConge::whereIn('user_id', User::where('role_id', Role::where('name', 'responsable rh')->first()->id)->pluck('id')->toArray())->where('etat_demande_id', EtatDemandeConge::where('etat_demande', 'created')->first()->id)->pluck('id')->toArray();
                            $role_ids = [];
                            $role_ids[] = Role::where('name', 'like', "%statis%")->pluck('id')->toArray();
                            $role_ids[] = self::getChargeRHIds('roles');
                            $role_ids = array_merge(...$role_ids);
                            $role_ids[] = Role::where('name', 'like', "vigie")->first()->id;
                            $role_ids[] = Role::where('name', 'like', "%incoh%")->first()->id;
                            $agent_wfm_and_chargehr_demands = DemandeConge::whereIn('user_id', User::whereIn('role_id', $role_ids)->pluck('id')->toArray())->whereIn('etat_demande_id', EtatDemandeConge::whereIn('etat_demande', ['created', 'validated by resp hr'])->pluck('id')->toArray())->pluck('id')->toArray();
                            $query->orWhereIn('id', $resprh_created_demands)->orWhereIn('id', $agent_wfm_and_chargehr_demands);
                        }
                        break;
                }
            }
        })->orderBy('etat_demande_id', 'asc')->orderBy('date_demande', 'desc')->orderBy('date_retour', 'desc')->with('user')->with('demand')->with('typeDemande');
        if (!$export) {
            return $query->paginate();
        } else {
            return $query->get();
        }
    }

    public static function rejectDemand($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $user = json_decode(Redis::get($request->headers->get('Uuid')));
        if (self::isSupervisor($user->role_id)) {
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
        $user = User::where('matricule', $demand->user->matricule)->first();
        return self::resetTheSoldes($demand, $user);
    }

    public static function cancelDemand($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $user = json_decode(Redis::get($request->headers->get('Uuid')));
        $user = User::where('matricule', $user->matricule)->first();
        $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "can%")->first()->id;
        // reset the soldes
        return self::resetTheSoldes($demand, $user);
    }

    public static function acceptDemand($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $user = json_decode(Redis::get($request->headers->get('Uuid')));
        $user = User::where('matricule', $user->matricule)->first();
        if (self::isSupervisor($user->role_id)) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "validated by sup%")->first()->id;
        } else if (self::isWFM($user->role_id)) {
            if (self::isVigie($user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "validated by vigie")->first()->id;
            } else if (self::isCPS($user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "validated by cps")->first()->id;
            } else if (self::isCCI($user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "validated by cci")->first()->id;
            } else if (self::isCoordinator($user->role_id)) {
                if (str_contains(strtolower($user->role->name), 'vigie')) {
                    $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "validated by coordinateur vigie")->first()->id;
                } else if (str_contains(strtolower($user->role->name), 'cps')) {
                    $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "validated by coordinateur cps")->first()->id;
                }
            } else if (self::isHeadOfOperationalExcellence($user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "validated by head%")->first()->id;
            } else {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "validated by wfm")->first()->id;
            }
        } else if (self::isOpsManager($user->role_id) and !self::isHeadOfOperationalExcellence($user->role_id)) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "validated by ops%")->first()->id;
        } else if (self::isHR($user->role_id)) {
            if (self::isResponsableRH($user->role_id) and self::isChargeRH($demand->user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "validated by resp hr")->first()->id;
            } else {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "clo%")->first()->id;
            }
        } else if (self::isDirector($user->role_id)) {
            if (self::isResponsableRH($demand->user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'closed')->first()->id;
            } else if (self::isChargeRH($demand->user->role_id)) {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'closed')->first()->id;
            } else {
                $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'validated by director')->first()->id;
            }
        }
        $demand->type_conge_id = $request['data']['type_conge_id'];
        $demand->nombre_jours = doubleval($request['data']['nombre_jours_confirmed']);
        $demand->save();
        return $demand;
    }

    public static function getAffectedDemands($request)
    {
        $user = User::where('matricule', $request['data'])->first();
        $role_id = $user->role_id;

        list($agent_ids, $supervisor_ids) = self::getAgentIdsAndSupervisorIds($user->role_id, $user);
        if (self::isSupervisor($role_id)) {
            return DemandeConge::whereIn('user_id', ...array_merge($agent_ids) ?? [])->where('etat_demande_id', self::getEtatDemande('created'))->count();
        } else if (self::isOpsManager($role_id) and !self::isHeadOfOperationalExcellence($role_id)) {
            $agent_count = DemandeConge::whereIn('user_id', $agent_ids)->whereIn('etat_demande_id', EtatDemandeConge::whereIn('etat_demande', ["validated by vigie", "validated by cps"])->pluck('id')->toArray())->whereNotNull('user_id')->count();
            $supervisor_count = DemandeConge::whereIn('user_id', $supervisor_ids)->where('etat_demande_id', self::getEtatDemande('created'))->whereNotNull('user_id')->count();
            return $agent_count + $supervisor_count;
        } else if (self::isWFM($role_id)) {
            $agent_count = 0;
            $supervisor_count = 0;
            $vigie_count = 0;
            $cps_count = 0;
            $cci_count = 0;
            if (self::isCPS($role_id) || self::isVigie($role_id)) {
                $agent_count = DemandeConge::where('etat_demande_id', self::getEtatDemande('validated by supervisor'))->whereIn('user_id', $agent_ids)->whereNotNull('user_id')->count();
                $supervisor_count = DemandeConge::where('etat_demande_id', self::getEtatDemande('validated by ops manager'))->whereIn('user_id', $supervisor_ids)->whereNotNull('user_id')->count();
            }
            if (self::isCoordinator($role_id) and self::isWFM($role_id)) {
                $vigie_count = DemandeConge::where('etat_demande_id', self::getEtatDemande('created'))->whereIn('user_id', self::getVigieIds())->count();
                $cps_count = DemandeConge::where('etat_demande_id', self::getEtatDemande('created'))->whereIn('user_id', self::getCPSIds())->count();
                $cci_count = DemandeConge::where('etat_demande_id', self::getEtatDemande('created'))->whereIn('user_id', self::getCCIIds())->count();
                $supervisor_count = DemandeConge::where('etat_demande_id', self::getEtatDemande('validated by director'))->whereIn('user_id', $supervisor_ids)->count();
            } else if (self::isHeadOfOperationalExcellence($role_id)) {
                $vigie_count = DemandeConge::where('etat_demande_id', self::getEtatDemande('created'))->whereIn('user_id', self::getVigieCoordinatorIds())->count();
                $cps_count = DemandeConge::where('etat_demande_id', self::getEtatDemande('created'))->whereIn('user_id', self::getCPSCoordinatorIds())->count();
            }
            return $agent_count + $supervisor_count + $vigie_count + $cps_count + $cci_count;
        } else if (self::isHR($role_id)) {
            $opsmanager_and_wfm_coordinators_created_demands = 0;
            $agent_wfm_validated_by_coordinators_wfm = 0;
            $agent_wfm_validated_by_director = 0;
            $wfm_coordinators_validated_by_director = 0;
            $opsmanager_validated_by_director = 0;
            $supervisor_validated_by_agent_wfm_demands = 0;
            $supervisor_validated_by_coordinators_wfm_demands = 0;
            $agent_validated_by_ops_manager = DemandeConge::where('etat_demande_id', self::getEtatDemande('validated by ops manager'))->whereIn('user_id', self::getAgentIds(''))->pluck('id')->count();
            if (self::isResponsableRH($role_id)) {
                $user_ids = [];
                $user_ids[] = self::getOpsManagersIds('');
                $user_ids[] = self::getWFMCoordinatorIds('');
                $user_ids[] = self::getChargeRHIds('');
                $user_ids = array_merge(...$user_ids);
                $opsmanager_and_wfm_coordinators_created_demands = DemandeConge::where('etat_demande_id', self::getEtatDemande('created'))->whereIn('user_id', $user_ids)->pluck('id')->count();
                $opsmanager_validated_by_director = DemandeConge::where('etat_demande_id', self::getEtatDemande('validated by director'))->whereIn('user_id', self::getOpsManagersIds())->pluck('id')->count();
                $agent_wfm_validated_by_director = DemandeConge::where('etat_demande_id', self::getEtatDemande('validated by director'))->whereIn('user_id', self::getWFMAgentsIds())->pluck('id')->count();
                $wfm_coordinators_validated_by_director = DemandeConge::where('etat_demande_id', self::getEtatDemande('validated by director'))->whereIn('user_id', self::getWFMCoordinatorIds(''))->pluck('id')->count();
                $supervisor_validated_by_coordinators_wfm_demands = DemandeConge::whereIn('etat_demande_id', [self::getEtatDemande('validated by coordinateur cps'), self::getEtatDemande('validated by coordinateur vigie')])->pluck('id')->count();
            } else if (self::isChargeRH($role_id)) {
                $agent_wfm_validated_by_coordinators_wfm = DemandeConge::where('etat_demande_id', self::getEtatDemande('created'))->whereIn('user_id', self::getWFMAgentsIds())->pluck('id')->count();
                $supervisor_validated_by_agent_wfm_demands = DemandeConge::whereIn('etat_demande_id', [self::getEtatDemande('validated by cps'), self::getEtatDemande('validated by vigie')])->whereIn('user_id', self::getSupervisorIds(''))->pluck('id')->count();
            }
            return $opsmanager_and_wfm_coordinators_created_demands + $agent_wfm_validated_by_coordinators_wfm + $agent_wfm_validated_by_director + $wfm_coordinators_validated_by_director + $opsmanager_validated_by_director + $supervisor_validated_by_agent_wfm_demands + $supervisor_validated_by_coordinators_wfm_demands + $agent_validated_by_ops_manager;
        } else if (self::isDirector($role_id)) {
            $supervisor_ids = User::where('role_id', Role::where('name', 'superviseur')->first()->id)->pluck('id')->toArray();
            $supervisor_created_demands_ids = DemandeConge::where('etat_demande_id', EtatDemandeConge::where('etat_demande', 'created')->first()->id)->whereIn('user_id', $supervisor_ids)->pluck('id')->toArray();
            $user_ids[] = self::getOpsManagersIds('');
            $user_ids[] = self::getWFMCoordinatorIds('');
            $user_ids[] = self::getCPSIds();
            $user_ids[] = self::getCCIIds();
            $user_ids[] = self::getVigieIds();
            $user_ids[] = self::getChargeRHIds('');
            $user_ids[] = self::getHeadOfOperationalExcellenceIds('');
            $user_ids[] = self::getSupervisorIds('');
            $user_ids = array_merge(...$user_ids);
            $resprh_created_demands = DemandeConge::whereIn('user_id', User::where('role_id', Role::where('name', 'responsable rh')->first()->id)->pluck('id')->toArray())->where('etat_demande_id', self::getEtatDemande('created'))->count();
            $charge_rh_validated_by_resp_rh = DemandeConge::whereIn('user_id', self::getChargeRHIds(''))->where('etat_demande_id', self::getEtatDemande('validated by resp hr'))->count();
            $agent_wfm_created_demands = DemandeConge::whereIn('user_id', self::getWFMAgentsIds())->where('etat_demande_id', self::getEtatDemande('created'))->count();
            return DemandeConge::whereIn('user_id', $user_ids)->whereIn('etat_demande_id', EtatDemandeConge::whereIn('etat_demande', ['created'])->pluck('id')->toArray())->whereNotNull('user_id')->orWhereIn('id', $supervisor_created_demands_ids)->count() + $resprh_created_demands + $charge_rh_validated_by_resp_rh + $agent_wfm_created_demands;
        }
        return null;
    }

    public static function getLatestDemand($request)
    {
        $user = User::where('matricule', $request['data']['matricule'])->first();
        if ($user) {
            if (!$user->conges->isEmpty()) {
                return $user->conges->toQuery()->whereNotIn('etat_demande_id', EtatDemandeConge::whereIn('etat_demande', ['canceled', 'rejected'])->pluck('id')->toArray())->orderBy('date_retour', 'desc')->first();
            }
        }
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

        if ($type_conge === "conge paye") {
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
        } else {
            $demand_stack_elem->solde_cp = 0;
            $demand_stack_elem->solde_rjf = 0;
        }

        $demand_stack_elem->save();

        Redis::set($request->headers->get('Uuid'), json_encode($user));
        $user->save();

        return $demand;

    }

    public static function searchDemands($request)
    {
        return self::bundleConditionsAndQueries(self::getDemandsData($request['data'], $request), $request);
    }

    public static function exportDemandsFile($request)
    {
        $headerCells = ['A1' => 'Matricule', 'B1' => 'Date demande', 'C1' => 'Date retour', 'D1' => 'Periode', 'E1' => 'Etat demande'];
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
                    $sheet->setCellValue($headerCell[0] . $index, $demand->date_demande);
                } else if ($headerCell[0] === 'C') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->date_retour);
                } else if ($headerCell[0] === 'D') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->periode);
                } else if ($headerCell[0] === 'E') {
                    $sheet->setCellValue($headerCell[0] . $index, $demand->demand->etat_demande);
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
        $principal_user = User::where('matricule', json_decode(Redis::get($request->headers->get('Uuid')))->matricule ?? '')->first();
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

    /**
     * @param $demand
     * @param $user
     * @return mixed
     */
    protected static function resetTheSoldes($demand, $user)
    {
        $demande_conge_stack_element = DemandeCongeStack::where('demande_conge_id', $demand->id)->first();
        $user->solde_cp = $user->solde_cp + $demande_conge_stack_element->solde_cp;
        $user->solde_rjf = $user->solde_rjf + $demande_conge_stack_element->solde_rjf;
        $demand->save();
        $user->save();
        $demande_conge_stack_element->delete();
        $demande_conge_stack_element->save();
        return $user;
    }

    protected static function getCCIIds()
    {
        return User::where('role_id', Role::where('name', 'like', "%incoh%")->first()->id)->pluck('id')->toArray();
    }

    protected static function getEtatDemande(string $string)
    {
        return EtatDemandeConge::where('etat_demande', 'like', $string)->first()->id;
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

}
