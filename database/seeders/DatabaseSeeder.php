<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Comment;
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
    /**
     * Seed the application's database.
     */
    public function run(DepartmentSeeder $departmentSeeder, LanguageSeeder $languageSeeder, MotifDepartSeeder $motifDepartSeeder, OperationSeeder $operationSeeder, RoleSeeder $roleSeeder, TeamTypeSeeder $teamTypeSeeder, IdentityTypeSeeder $identityTypeSeeder, NationalitySeeder $nationalitySeeder, SourcingTypeSeeder $sourcingTypeSeeder, FamilySituationSeeder $familySituationSeeder, DemandeCongeSeeder $demandeCongeSeeder, EtatDemandeCongeSeeder $etatDemandeCongeSeeder): void
    {
        $departmentSeeder->run();
        $languageSeeder->run();
        $motifDepartSeeder->run();
        $operationSeeder->run();
        $roleSeeder->run();
        $teamTypeSeeder->run();
        $nationalitySeeder->run();
        $sourcingTypeSeeder->run();
        $familySituationSeeder->run();

        $allUsersPath = storage_path() . '\app\public\users.csv';
        $worldLineUsers = storage_path() . '\app\public\users_wl.csv';
        $soldesPath = storage_path() . '\app\public\solde';
        $wfmUsersPath = storage_path() . '\app\public\wfm_operations.csv';

        $fillUsersWithNoRelations = function ($data) {
//            if (str_contains('10490', $data[2])) {
//                dd($data);
//            }
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
                'solde_cp' => 0,
                'solde_rjf' => 0
            ]);
            $comment = Comment::factory()->create([
                'user_id' => $user->id,
            ]);
            $comment->save();
            if (str_contains($data[7], 'reporting')) {
                $user->role_id = Role::where('name', 'like', '%reporting%')->first()->id;
            } else if (!!Role::where('name', $data[7])->first()) {
                if (!Role::where('name', 'like', "%".trim($data[7])."%")->first()) {
                    dd($data);
                }
                $user->role_id = Role::where('name', 'like', "%".trim($data[7])."%")->first()->id;
            } else if (str_contains($data[3], 'superviseur')) {
                $user->role_id = Role::where('name', 'like', "%Superviseur%")->first()->id;
            } else if (str_contains($data[3], 'opsmanager')) {
                $user->role_id = Role::where('name', 'like', "%Opérations%")->first()->id;
            } else if (str_contains($data[3], 'rh')) {
                $user->role_id = Role::where('name', 'like', "%Responsable RH%")->first()->id;
            } else if (str_contains($data[3], 'wfm')) {
                $user->role_id = Role::where('name', 'like', "%planification%")->first()->id;
            } else {
                $user->role_id = 1;
            }
            $user->save();
        };

        $fillUsersWithManagers = function ($data) {
            $lastName = substr($data[27], 0, strpos($data[27], ' '));
            $firstName = substr($data[27], strpos($data[27], ' ') + 1);
            $userFromCSV = User::where('first_name', $firstName)->where('last_name', $lastName)->first() ?? User::where('first_name', $lastName)->where('last_name', $firstName)->first();
            if (!is_null($userFromCSV)) {
                $user = User::where('matricule', $data[2])->first();
                $user->managers()->attach($userFromCSV->id);
                $user->save();
            }
        };

        $fillUsersWithOperations = function ($data) {
//            dd($data);
            $user = User::where('matricule', $data[2])->first();
            if (in_array($data[26], Operation::all()->pluck('name')->toArray())) {
                $user->operations()->attach(Operation::where('name', $data[26])->first()->id);
                $user->operation_id = Operation::where('name', $data[26])->first()->id;
            } else if (in_array($data[26], Department::all()->pluck('name')->toArray())) {
                $user->department_id = Department::where('name', $data[26])->first()->id;
            }
//            $creator_id = User::where('first_name', 'like', "%chahir%")->where('last_name', 'like', "%ayoub%")->first()->id;
//            $user->creator_id = $creator_id;
            $user->save();
        };

        $fillWFMUsersWithOperationsPartial2 = function ($data) {
            $user = User::where('matricule', 'like', "%$data[1]%")->first();
            $user->department_id = 5;
            if ($data[4] !== "" and $data[4]) {
                $operation = Operation::where('name', 'like', "%$data[4]%")->first();
                if ($operation) {
                    $operation_id = $operation->id;
                    $user->operations()->attach($operation_id);
                }
            }

            $user->save();
        };

        $fillWFMUsersWithOperationsPartial1 = function ($data) {
            $user = User::where('matricule', 'like', "%$data[1]%")->first();
            $user->department_id = Department::where('name', 'like', '%wfm%')->first()->id;
            if ($data[2] !== "ALL") {
                $operation = Operation::where('name', 'like', "%$data[2]%")->first();
                if ($operation) {
                    $operation_id = $operation->id;
                    $user->operations()->attach($operation_id);
                }
            }
            if ($data[3] !== "") {
                $operation_id = Operation::where('name', 'like', "%$data[3]%")->first()->id;
                $user->operations()->attach($operation_id);
            }

            $user->save();
        };

        $fillWFMUsersWithOperationsWhenAll = function ($data) {
            if (!User::where('matricule', 'like', "%".$data[1]."%")->first()) {
                dd($data);
            }
            $user = User::where('matricule', 'like', "%".$data[1]."%")->first();
            $user->department_id = 5;
            if ($data[2] === "ALL") {
                $operation_ids = Operation::pluck('id')->toArray();
                foreach ($operation_ids as $operation_id) {
                    $user->operations()->attach($operation_id);
                }
            }

            $user->save();
        };

        $fillUsersWithTeamTypes = function ($data) {
            $user = User::where('matricule', $data[1])->first();
            $user->team_type_id = TeamType::where('name', $data[9])->first()->id;
            $user->role_id = Role::where('name', $data[8])->first()->id;
            $user->save();
        };

        $fillUsersWithSoldes = function ($data) {
            $user = User::where('matricule', $data[2])->first();

            $user->solde_cp = (double)$data[11];
            $user->solde_rjf = (double)$data[15];
            $user->save();
        };

        Utils::getDataFromDBOrValidateInjectionFile($fillUsersWithNoRelations, $allUsersPath);
        Utils::getDataFromDBOrValidateInjectionFile($fillUsersWithOperations, $allUsersPath);
        Utils::getDataFromDBOrValidateInjectionFile($fillWFMUsersWithOperationsWhenAll, $wfmUsersPath);
        Utils::getDataFromDBOrValidateInjectionFile($fillWFMUsersWithOperationsPartial1, $wfmUsersPath);
        Utils::getDataFromDBOrValidateInjectionFile($fillWFMUsersWithOperationsPartial2, $wfmUsersPath);
        Utils::getDataFromDBOrValidateInjectionFile($fillUsersWithManagers, $allUsersPath);
        Utils::getDataFromDBOrValidateInjectionFile($fillUsersWithTeamTypes, $worldLineUsers);
        Utils::getDataFromDBOrValidateInjectionFile($fillUsersWithSoldes, $soldesPath);

        $etatDemandeCongeSeeder->run();
        $identityTypeSeeder->run();
        $demandeCongeSeeder->run();
    }
}
