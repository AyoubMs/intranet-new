<?php


use App\Http\Controllers\DataController;
use App\Http\Controllers\InjectionController;
use App\Models\Operation;
use App\Models\Role;
use Database\Seeders\Utils;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use PhpOffice\PhpSpreadsheet\IOFactory;


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

Route::post('/injection', [InjectionController::class, 'loadData']);

require __DIR__.'/auth.php';
