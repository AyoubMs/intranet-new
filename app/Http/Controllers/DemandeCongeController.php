<?php

namespace App\Http\Controllers;

use App\Models\DemandeConge;
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

class DemandeCongeController extends Controller
{

    protected static function bundleConditionsAndQueries($input)
    {
        return self::getDemands($input);
    }

    protected static function getProfileCondition($role_id)
    {
        if ($role_id === Role::where('name', 'like', '%Superviseur%')->first()->id) {
            return true;
        } else if ($role_id === Role::where('name', 'like', '%Opération%')->first()->id) {
            return true;
        } else if ($role_id === Role::where('name', 'like', '%Chargé de planification et statistiques%')->first()->id) {
            return true;
        } else {
            return false;
        }
    }

    public static function getAffectedDemands($request)
    {
        $user = json_decode(Redis::get($request->headers->get('Uuid')));
        $user = User::where('matricule', $user->matricule)->first();
        list($user_id, $user_ids) = self::getUserIdsAndUserId($user, $user->matricule);
        if (!empty($user_ids)) {
            return DemandeConge::whereIn('user_id', ...array_merge($user_ids) ?? [])->where('etat_demande_id', EtatDemandeConge::where('etat_demande', 'created')->first()->id)->get();
        }
        return null;
    }

    public static function getLatestDemand($request)
    {
        $user = User::where('matricule', $request['data']['matricule'])->first();
        if ($user) {
            if (!$user->conges->isEmpty()) {
                return $user->conges->toQuery()->orderBy('date_retour', 'desc')->first();
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
            'periode' => $period,
            'etat_demande_id' => EtatDemandeConge::where('etat_demande', 'created')->first()->id,
            'user_id' => $user->id
        ]);

        $solde_rjf = $user->solde_rjf;
        if ($period >= $solde_rjf) {
            $period = $period - $solde_rjf;
            $user->solde_rjf = 0;
            $user->solde_cp = $user->solde_cp - $period;
        } else {
            $user->solde_rjf = $solde_rjf - $period;
        }

        Redis::set($request->headers->get('Uuid'), json_encode($user));

        $user->save();

        return $demand;

    }

    public static function searchDemands($request)
    {
        return self::bundleConditionsAndQueries(self::getDemandsData($request['data'], $request));
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
            foreach (self::getDemands($input, true) as $demand) {
                if ($headerCell[0] === 'A') {
                    $sheet->setCellValue($headerCell[0].$index, $demand->user->matricule);
                } else if ($headerCell[0] === 'B') {
                    $sheet->setCellValue($headerCell[0].$index, $demand->date_demande);
                } else if ($headerCell[0] === 'C') {
                    $sheet->setCellValue($headerCell[0].$index, $demand->date_retour);
                } else if ($headerCell[0] === 'D') {
                    $sheet->setCellValue($headerCell[0].$index, $demand->periode);
                } else if ($headerCell[0] === 'E') {
                    $sheet->setCellValue($headerCell[0].$index, $demand->demand->etat_demande);
                }
                $index++;
            }
        }
        $demandsPath = storage_path().'\app\public\export-demands-files\demands.xlsx';

        try {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($demandsPath);
        } catch (Exception $e) {
            info($e);
        }


        return response()->file($demandsPath);
    }

    /**
     * @param array $input
     * @return mixed
     */
    public static function getDemands(array $input, $export = false)
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
                        if (!empty($value)) {
                            $query->whereIn('user_id', ...array_merge($value));
                        }
                        break;
                    case 'user_id':
                        if (!is_null($value)) {
                            $query->where('user_id', $value);
                        }
                        break;
                }
            }
        })->orderBy('etat_demande_id', 'asc')->with('user')->with('demand');
        if (!$export) {
            return $query->paginate();
        } else {
            return $query->get();
        }
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

        //        info($user_ids->count());
        return array('date_demande_debut' => $dateDemandeDebut, 'date_demande_fin' => $dateDemandeFin, 'date_debut_conge_debut' => $dateDebutCongeDebut, 'date_debut_conge_fin' => $dateDebutCongeFin, 'date_fin_conge_debut' => $dateFinCongeDebut, 'date_fin_conge_fin' => $dateFinCongeFin, 'user_id' => $user_id, 'user_ids' => $user_ids);
    }

    /**
     * @param $principal_user
     * @param $matricule
     * @return array
     */
    public static function getUserIdsAndUserId($principal_user, $matricule): array
    {
        $user_id = null;
        $user_ids = [];
        if (!is_null($principal_user)) {
            if (self::getProfileCondition($principal_user->role_id)) {
                foreach ($principal_user->operations as $operation) {
                    $user_ids[] = $operation->users->pluck('id');
                }
            } else {
                $user_id = User::where('matricule', $matricule ?? $principal_user->matricule)->first()->id ?? null;
            }
        }
        return array($user_id, $user_ids);
    }

}
