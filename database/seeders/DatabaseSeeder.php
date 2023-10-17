<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Department;
use App\Models\FamilySituation;
use App\Models\Language;
use App\Models\MotifDepart;
use App\Models\Operation;
use App\Models\Role;
use App\Models\TeamType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function getDataFromDB($func, $path)
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

    /**
     * Seed the application's database.
     */
    public function run(DepartmentSeeder $departmentSeeder, LanguageSeeder $languageSeeder, MotifDepartSeeder $motifDepartSeeder, OperationSeeder $operationSeeder, RoleSeeder $roleSeeder, TeamTypeSeeder $teamTypeSeeder, IdentityTypeSeeder $identityTypeSeeder, NationalitySeeder $nationalitySeeder, SourcingTypeSeeder $sourcingTypeSeeder, FamilySituationSeeder $familySituationSeeder): void
    {
        $departmentSeeder->run();
        $languageSeeder->run();
        $motifDepartSeeder->run();
        $operationSeeder->run();
        $roleSeeder->run();
        $teamTypeSeeder->run();
        $identityTypeSeeder->run();
        $nationalitySeeder->run();
        $sourcingTypeSeeder->run();
        $familySituationSeeder->run();

        $allUsersPath = storage_path() . '\app\public\users.csv';
        $worldLineUsers = storage_path() . '\app\public\users_wl.csv';
        $soldesPath = storage_path() . '\app\public\solde';

        $fillUsersWithNoRelations = function ($data) {
            $user = User::factory()->create([
                'matricule' => $data[2],
                'first_name' => $data[3],
                'last_name' => $data[4],
                'date_naissance' => $data[5] === "" ? null : $data[5],
                'Sexe' => $data[6],
                'date_entree_formation' => $data[8] === "" ? null : $data[8],
                'date_entree_production' => $data[9] === "" ? null : $data[9],
                'date_depart' => $data[10] === '' ? null : $data[10],
                'active' => ($data[10] === ''),
                'primary_language_id' => Language::where('name', $data[12])->first()->id ?? null,
                'secondary_language_id' => Language::where('name', $data[14])->first()->id ?? null,
                'motif_depart_id' => MotifDepart::where('name', $data[15])->first()->id ?? null,
            ]);
            if (str_contains($data[7], 'reporting')) {
                $user->role_id = Role::where('name', 'like', '%reporting%')->first()->id;
                $user->save();
            } else if (!!Role::where('name', $data[7])->first()) {
                $user->role_id = Role::where('name', 'like', "%$data[7]%")->first()->id;
                $user->save();
            } else {
                $user->role_id = 1;
                $user->save();
            }
        };

        $fillUsersWithManagers = function ($data) {
            $firstName = substr($data[27], 0, strpos($data[27], ' '));
            $lastName = substr($data[27], strpos($data[27], ' ') + 1);
            if (!!$userFromCSV = User::where(['first_name' => $firstName, 'last_name' => $lastName])->first()) {
                $user = User::where('matricule', $data[2])->first();
                $user->manager_id = $userFromCSV->id;
                $user->save();
            }
        };

        $fillUsersWithOperations = function ($data) {
//            dd($data);
            $user = User::where('matricule', $data[2])->first();
            if (in_array($data[26], Operation::all()->pluck('name')->toArray())) {
                $user->operation_id = Operation::where('name', $data[26])->first()->id;
                $user->save();
            } else if (in_array($data[26], Department::all()->pluck('name')->toArray())) {
                $user->department_id = Department::where('name', $data[26])->first()->id;
                $user->save();
            }
        };

        $fillUsersWithTeamTypes = function ($data) {
            $user = User::where('matricule', $data[1])->first();
            if (!TeamType::where('name', $data[9])->first()) {
                dd(TeamType::where('name', $data[9])->first(), $data[9]);
            }
            $user->team_type_id = TeamType::where('name', $data[9])->first()->id;
            $user->role_id = Role::where('name', $data[8])->first()->id;
            $user->save();
        };

        $fillUsersWithSoldes = function ($data) {
            $user = User::where('matricule', $data[2])->first();

            $user->solde_cp = (double) $data[11];
            $user->solde_rjf = (double) $data[15];
            $user->save();
        };

        $this->getDataFromDB($fillUsersWithNoRelations, $allUsersPath);
        $this->getDataFromDB($fillUsersWithOperations, $allUsersPath);
        $this->getDataFromDB($fillUsersWithManagers, $allUsersPath);
        $this->getDataFromDB($fillUsersWithTeamTypes, $worldLineUsers);
        $this->getDataFromDB($fillUsersWithSoldes, $soldesPath);
    }
}
