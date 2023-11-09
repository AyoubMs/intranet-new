<?php

namespace App\Http\Controllers;

use App\Models\DemandeConge;
use App\Models\User;
//use Illuminate\Database\Query\Builder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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


//        return response()->file($demandsPath);
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
                    case 'user_id':
                        if (!is_null($value)) {
                            $query->where('user_id', $value);
                        }
                        break;
                }
            }
        })->with('user')->with('demand');
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
        $user_id = User::where('matricule', $request['data']['matricule'])->first()->id ?? null;
        $input = array('date_demande_debut' => $dateDemandeDebut, 'date_demande_fin' => $dateDemandeFin, 'date_debut_conge_debut' => $dateDebutCongeDebut, 'date_debut_conge_fin' => $dateDebutCongeFin, 'date_fin_conge_debut' => $dateFinCongeDebut, 'date_fin_conge_fin' => $dateFinCongeFin, 'user_id' => $user_id);
        return $input;
    }

}
