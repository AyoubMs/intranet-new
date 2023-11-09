<?php

namespace App\Http\Controllers;

use App\Models\User;
use Database\Seeders\Utils;
use Illuminate\Http\Request;

class InjectionController extends Controller
{
    public static function injectSolde()
    {
        $pathCsv = storage_path().'\app\public\injection-files\injection_file.csv';
        $injectFunc = function ($data) {
            $user = User::where('matricule', $data[0])->first();
            $user->solde_cp = $user->solde_cp + $data[1];
            $user->solde_rjf = $user->solde_rjf + $data[2];
            $user->save();
        };
        Utils::getDataFromDBOrValidateInjectionFile($injectFunc, $pathCsv);
    }

    public function loadData($request)
    {
        if($request->file('file')->extension() !== 'xlsx') {
            $errors = new StdClass();
            $errors->injectionError = 'Please upload an excel file';
            return $errors;
        } else if ($request->file('file')->getSize() > 1000000) {
            $errors = new StdClass();
            $errors->injectionError = 'Please upload a file less than 1Mo';
            return $errors;
        }
        $request->file('file')->storeAs('public/injection-files', 'injection_file.xlsx');
        $pathXlsx = storage_path().'\app\public\injection-files\injection_file.xlsx';
        $pathCsv = storage_path().'\app\public\injection-files\injection_file.csv';

        try {
            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($pathXlsx);

            $writer = IOFactory::createWriter($spreadsheet, "Csv");
            $writer->setSheetIndex(0);
            $writer->setDelimiter(',');

            $writer->save($pathCsv);
            $emptyFunc = function () {};

            return Utils::getDataFromDBOrValidateInjectionFile($emptyFunc, $pathCsv, true);
        } catch (Exception $e) {
            $errors = new StdClass();
            $errors->injectionError = 'Please upload a new excel file';
            return $errors;
        }
    }
}
