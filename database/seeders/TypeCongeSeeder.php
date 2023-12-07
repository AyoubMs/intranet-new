<?php

namespace Database\Seeders;

use App\Models\TypeConge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeCongeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conge_types = ['sans solde', 'evenement special', 'conge paye'];

        foreach ($conge_types as $conge_type) {
            TypeConge::factory()->create([
                'name' => $conge_type
            ]);
        }
    }
}
