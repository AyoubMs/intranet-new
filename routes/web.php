<?php


use App\Http\Controllers\DataController;
use App\Models\Operation;
use App\Models\Role;
use Database\Seeders\Utils;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::post('/data', [DataController::class, 'getData']);

Route::post('/injection', function (Request $request) {
    info($request->file('file')->extension());
    if($request->file('file')->extension() !== 'xlsx') {
        $errors = new StdClass();
        $errors->injectionError = 'Please upload an excel file';
        return $errors;
    }
    $request->file('file')->storeAs('public/injection-files', 'injection_file.xlsx');
    $pathXlsx = storage_path().'\app\public\injection-files\injection_file.xlsx';
    $pathCsv = storage_path().'\app\public\injection-files\injection_file.csv';

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
    $spreadsheet = $reader->load($pathXlsx);

    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Csv");
    $writer->setSheetIndex(0);
    $writer->setDelimiter(',');

    $writer->save($pathCsv);

    $emptyFunc = function () {};

    return Utils::getDataFromDBOrValidateInjectionFile($emptyFunc, $pathCsv, true);
//    $injectFunc = function() {};
//
//    Utils::getDataFromDB($injectFunc, $pathCsv);

});

require __DIR__.'/auth.php';
