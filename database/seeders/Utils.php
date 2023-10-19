<?php

namespace Database\Seeders;

class Utils
{
    public static function getDataFromDB($func, $path)
    {
        $csvData = fopen($path, 'r');
        $transRow = true;

        while (($data = fgetcsv($csvData, 555, ',')) !== false) {
            if (!$transRow) {
                $func($data);
            }
            $transRow = false;
        }
        fclose($csvData);
    }
}
