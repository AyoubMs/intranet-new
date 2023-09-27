<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Language;
use App\Models\MotifDepart;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(DepartmentSeeder $departmentSeeder, LanguageSeeder $languageSeeder, MotifDepartSeeder $motifDepartSeeder, OperationSeeder $operationSeeder, RoleSeeder $roleSeeder, TeamTypeSeeder $teamTypeSeeder): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $departmentSeeder->run();
        $languageSeeder->run();
        $motifDepartSeeder->run();
        $operationSeeder->run();
        $roleSeeder->run();
        $teamTypeSeeder->run();

        $csvData = fopen(storage_path() . '\app\public\users.csv', 'r');
        $transRow = true;

        while (($data = fgetcsv($csvData, 555, ',')) !== false) {
//            dd($data);
            if (!$transRow) {
//                dd($data);
//                if ($data[2] === 'ENNADI' and $data[3] === 'Mohamed') {
//                    dd($data[6], $data, Role::where('name', $data[6])->first()->id);
//                }
                $user = User::factory()->create([
                    'matricule' => $data[1],
                    'first_name' => $data[2],
                    'last_name' => $data[3],
                    'date_naissance' => $data[4] === "" ? null : $data[4],
                    'Sexe' => $data[5],
                    'date_entree_formation' => $data[7] === "" ? null : $data[7],
                    'date_depart' => $data[9] === '' ? null : $data[7],
                    'language_id' => Language::where('name', $data[11])->first()->id ?? null,
                    'motif_depart_id' => MotifDepart::where('name', $data[13])->first()->id ?? null,
                ]);
                if (str_contains($data[6], 'reporting')) {
                    $user->role_id = Role::where('name', 'like', '%reporting%')->first()->id;
                    $user->save();
                } else if (!!Role::where('name', $data[6])->first()) {
                    $user->role_id = Role::where('name', $data[6])->first()->id;
                    $user->save();
                }
            }
            $transRow = false;
        }
        fclose($csvData);

        $csvData = fopen(storage_path() . '\app\public\users.csv', 'r');
        $transRow = true;
        while (($data = fgetcsv($csvData, 555, ',')) !== false) {
            if (!$transRow) {
                $firstName = substr($data[25], 0, strpos($data[25], ' '));
                $lastName = substr($data[25], strpos($data[25], ' ') + 1);
                if (!! $userFromCSV = User::where(['first_name' => $firstName, 'last_name' => $lastName])->first()) {
                    $user = User::where('matricule', $data[1])->first();
                    $user->manager_id = $userFromCSV->id;
                    $user->save();
                }
            }
            $transRow = false;
        }
        fclose($csvData);

    }
}
