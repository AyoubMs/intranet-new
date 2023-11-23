<?php

namespace App\Http\Controllers;

use App\Models\DemandeConge;
use App\Models\DemandeCongeStack;
use App\Models\EtatDemandeConge;
use App\Models\Role;
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
        if ($role_id === Role::where('name', 'like', '%Superviseur%')->first()->id) {
            return true;
        } else if ($role_id === Role::where('name', 'like', '%Opération%')->first()->id) {
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

    /**
     * @return array
     */
    protected static function WFMIds(): array
    {
        $wfm_ids = [];
        $wfm_ids[] = Role::where('name', 'like', "%statis%")->pluck('id')->toArray();
        $wfm_ids[] = Role::where('name', 'like', "%cps%")->pluck('id')->toArray();
        return $wfm_ids;
    }

    protected static function isWFM($role_id)
    {
        return in_array($role_id, array_merge(...self::WFMIds()));
    }

    protected static function isSupervisor($role_id)
    {
        return $role_id === Role::where('name', 'Superviseur')->first()->id;
    }

    protected static function isOpsManager($role_id)
    {
        return in_array($role_id, Role::where('name', 'like', '%Opération%')->pluck('id')->toArray());
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
                        if (!empty($value) and !self::isHR($input['role']->id)) {
                            $query->whereIn('user_id', ...array_merge($value));
                        } else if (self::isSupervisor($input['role']->id)) {
                            $query->whereIn('user_id', $value);
                        }
                        break;
                    case 'user_id':
                        if (!is_null($value)) {
                            $query->where('user_id', $value);
                        }
                        break;
                    case 'role':
                        if (self::isWFM($value->id) || self::isOpsManager($value->id)) {
                            $query->whereIn('etat_demande_id', EtatDemandeConge::whereNotIn('etat_demande', ['created'])->pluck('id')->toArray());
                        } else if (self::isHR($value->id)) {
                            $query->whereIn('etat_demande_id', EtatDemandeConge::whereNotIn('etat_demande', ['created', 'validated by supervisor', 'validated by wfm'])->pluck('id')->toArray());
                        }
                        break;
                }
            }
        })->orderBy('etat_demande_id', 'asc')->orderBy('date_demande', 'desc')->orderBy('date_retour', 'desc')->with('user')->with('demand');
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
        $user = User::where('matricule', $user->matricule)->first();
        if (self::isSupervisor($user->role_id)) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by sup%")->first()->id;
        } else if (self::isOpsManager($user->role_id)) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by %ops%")->first()->id;
        } else if (self::isWFM($user->role_id)) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by %wfm")->first()->id;
        } else if (self::isHR($user->role_id)) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "rejected by hr")->first()->id;
        }
        $demand->save();
        return $user;
    }

    public static function cancelDemand($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $user = json_decode(Redis::get($request->headers->get('Uuid')));
        $user = User::where('matricule', $user->matricule)->first();
        $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "can%")->first()->id;
        $demande_conge_stack_element = DemandeCongeStack::where('demande_conge_id', $demand->id)->first();
        $user->solde_cp = $user->solde_cp + $demande_conge_stack_element->solde_cp;
        $user->solde_rjf = $user->solde_rjf + $demande_conge_stack_element->solde_rjf;
        $demand->save();
        $user->save();
        return $user;
    }

    public static function acceptDemand($request)
    {
        $demand = DemandeConge::where('id', $request['data']['id'])->first();
        $user = json_decode(Redis::get($request->headers->get('Uuid')));
        $user = User::where('matricule', $user->matricule)->first();
        $wfm_ids = self::WFMIds();
        if ($user->role_id === Role::where('name', 'like', '%Superviseur%')->first()->id) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "%supervisor%")->first()->id;
        } else if (in_array($user->role_id, array_merge(...$wfm_ids))) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "%wfm%")->first()->id;
        } else if (in_array($user->role_id, Role::where('name', 'like', '%Opération%')->pluck('id')->toArray())) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "%ops%")->first()->id;
        } else if (self::isHR($user->role_id)) {
            $demand->etat_demande_id = EtatDemandeConge::where('etat_demande', 'like', "clo%")->first()->id;
        }
        $demand->save();
        return $demand;
    }

    public static function getAffectedDemands($request)
    {
        $user = User::where('matricule', $request['data'])->first();
        $role_id = $user->role_id;
        $hr_ids = self::getHRIds();
        $user_ids_from_manager = [];
        list($user_id, $user_ids) = self::getUserIdsAndUserId($user, $user->matricule);
        if (!$user->users->isEmpty()) {
            foreach ($user->users as $user) {
                $user_ids_from_manager[] = $user->id;
            }
        }
        $wfm_ids = self::WFMIds();
        if ($role_id === Role::where('name', "Superviseur")->first()->id) {
            $user_ids = [];
            $user_ids[] = $user_ids_from_manager;
        }
        if (!empty($user_ids) and $role_id === Role::where('name', "Superviseur")->first()->id) {
            return DemandeConge::whereIn('user_id', ...array_merge($user_ids) ?? [])->where('etat_demande_id', EtatDemandeConge::where('etat_demande', 'created')->first()->id)->count();
        } else if (!empty($user_ids) and in_array($role_id, Role::where('name', 'like', '%Opération%')->pluck('id')->toArray())) {
            return DemandeConge::whereIn('user_id', ...array_merge($user_ids) ?? [])->where('etat_demande_id', EtatDemandeConge::where('etat_demande', 'like', "%wfm%")->first()->id)->count();
        } else if (!empty($user_ids) and in_array($role_id, array_merge(...$wfm_ids))) {
            return DemandeConge::whereIn('user_id', ...array_merge($user_ids) ?? [])->where('etat_demande_id', EtatDemandeConge::where('etat_demande', 'like', "%supervisor")->first()->id)->count();
        } else if (in_array($role_id, array_merge(...$hr_ids))) {
            return DemandeConge::where('etat_demande_id', EtatDemandeConge::where('etat_demande', 'like', "%ops%")->first()->id)->count();
        }
        return null;
    }

    public static function getLatestDemand($request)
    {
        $user = User::where('matricule', $request['data']['matricule'])->first();
        if ($user) {
            if (!$user->conges->isEmpty()) {
                return $user->conges->toQuery()->whereNotIn('etat_demande_id', EtatDemandeConge::whereIn('etat_demande', ['canceled', 'closed', 'rejected'])->pluck('id')->toArray())->orderBy('date_retour', 'desc')->first();
            }
        }
        return null;
    }

    public static function createDemand($request)
    {
        $period = intval(date_diff(date_create($request['data']['date_fin']), date_create($request['data']['date_debut']))->format('%a')) + 1;

        $user = User::where('matricule', $request['data']['matricule'])->first();

        $demand = DemandeConge::factory()->create([
            'date_demande' => today(),
            'date_retour' => $request['data']['date_retour'],
            'date_debut' => $request['data']['date_debut'],
            'date_fin' => $request['data']['date_fin'],
            'periode' => $request['data']['date_debut'] . " - " . $request['data']['date_fin'],
            'etat_demande_id' => EtatDemandeConge::where('etat_demande', 'created')->first()->id,
            'user_id' => $user->id
        ]);

        $solde_rjf = $user->solde_rjf;
        $demand_stack_elem = DemandeCongeStack::factory()->create([
            'demande_conge_id' => $demand->id,
            'user_id' => $user->id
        ]);

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
            info($e);
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
                info((new Collection($user_ids))->count());
        if (is_null($user_id) and !self::getProfileCondition($principal_user->role->id)) {
            $user_id = json_decode(Redis::get($request->headers->get('Uuid')))->id;
        }
        $role = User::where('id', json_decode(Redis::get($request->headers->get('Uuid')))->id)->first()->role;
        return array('date_demande_debut' => $dateDemandeDebut, 'date_demande_fin' => $dateDemandeFin, 'date_debut_conge_debut' => $dateDebutCongeDebut, 'date_debut_conge_fin' => $dateDebutCongeFin, 'date_fin_conge_debut' => $dateFinCongeDebut, 'date_fin_conge_fin' => $dateFinCongeFin, 'user_id' => $user_id, 'user_ids' => $user_ids, 'role' => $role);
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
                    $user_ids[] = $operation->users->pluck('id');
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

}
