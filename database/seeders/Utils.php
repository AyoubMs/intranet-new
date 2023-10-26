<?php

namespace Database\Seeders;

use stdClass;

class Utils
{
    public static function getDataFromDBOrValidateInjectionFile($func, $path, $injection = false)
    {
        $csvData = fopen($path, 'r');
        $transRow = true;

        while (($data = fgetcsv($csvData, 555, ',')) !== false) {
            if ($injection and $transRow) {
                info($data);
                if (count($data) !== 3) {
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
        fclose($csvData);
    }
}
