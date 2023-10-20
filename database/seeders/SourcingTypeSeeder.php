<?php

namespace Database\Seeders;

use App\Models\SourcingType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SourcingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $free_sourcing = ["Dutchies at work", "Dutch employees", "Moncallcenter.ma", "LinkedIn", "Réintégration", "CANDIDATURE SPONTANEE", "TIK TOK", "GEDMA", "Transfert NCC BARCELONE"];
        $paid_sourcing = ["Multicibles", "Facebook", "Parrainage", "Menara.ma", "LinkedIn", "Ihssane House", "ANAPEC", "Recruit4Work", "AARRAS Fouad", "INO", "SITE WEB", "TIK TOK", "ExpatWork", "ELJ"];

        foreach ($free_sourcing as $sourcing) {
            SourcingType::factory()->create([
                'type' => 'Sourcing gratuit',
                'name' => $sourcing
            ]);
        }

        foreach ($paid_sourcing as $sourcing) {
            SourcingType::factory()->create([
                'type' => 'Sourcing payant',
                'name' => $sourcing
            ]);
        }
    }
}
