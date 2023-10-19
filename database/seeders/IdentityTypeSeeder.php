<?php

namespace Database\Seeders;

use App\Models\FamilySituation;
use App\Models\IdentityType;
use App\Models\Nationality;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IdentityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usersPath = storage_path() . '\app\public\effectif.csv';

        $fillUsersWithMissingInfo = function ($data) {
            $user = User::where('matricule', $data[1])->first();
            IdentityType::factory()->create([
                'user_id' => $user->id,
                'name' => 'CIN',
                'identity_number' => $data[8]
            ]);
            IdentityType::factory()->create([
                'user_id' => $user->id,
                'name' => 'Carte sejour',
                'identity_number' => $data[9]
            ]);
            IdentityType::factory()->create([
                'user_id' => $user->id,
                'name' => 'Passeport',
                'identity_number' => $data[10]
            ]);
            $user->nationality_id = Nationality::where('name', 'like', "%$data[11]%")->first()->id;
            $user->cnss_number = $data[12];
            $familySituation = substr($data[15], 0, 4);
            $user->family_situation_id = FamilySituation::where('name', 'like', "%$familySituation%")->first()->id;
            $user->nombre_enfants = $data[16];
            $user->address = $data[17];
            $user->email_1 = $data[22];
            $user->save();
        };

        Utils::getDataFromDB($fillUsersWithMissingInfo, $usersPath);
//        foreach ($identity_types as $identity_type) {
//            IdentityType::factory()->create([
//                'name' => $identity_type
//            ]);
//        }
    }
}
