<?php

namespace Database\Seeders;

use Exception;
use stdClass;

class Utils
{
    public static function getDataFromDBOrValidateInjectionFile($func, $path, $injection = false)
    {
        try {
            $csvData = fopen($path, 'r');
            $transRow = true;
            while (($data = fgetcsv($csvData, 555, ',')) !== false) {
                $congesPath = storage_path().'\app\public\conges.csv';
//                if ($path === $congesPath) {
//                    dd($data);
//                }
                if ($injection and $transRow) {
                    if (count($data) !== 3 and $data[3] !== '') {
                        $errors = new StdClass();
                        $errors->injectionError = 'Please upload an excel file with 3 columns, matricule, solde cp, and solde rjf';
                        return $errors;
                    } else {
                        return 'done';
                    }
                }
                if (!$transRow && !$injection) {
                    $func($data);
                }
                $transRow = false;
            }
        } catch (Exception $e) {
            info($e);
            $errors = new StdClass();
            $errors->injectionError = 'Please upload a new excel file';
            return $errors;
        }
        fclose($csvData);
    }
}
