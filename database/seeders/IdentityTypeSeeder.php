<?php

namespace Database\Seeders;

use App\Models\IdentityType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IdentityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $identity_types = [
            'CIN', 'Passport', 'Carte séjour'
        ];

        foreach ($identity_types as $identity_type) {
            IdentityType::factory()->create([
                'name' => $identity_type
            ]);
        }
    }
}
