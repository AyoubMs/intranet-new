<?php

namespace Database\Seeders;

use App\Models\TeamType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $team_types = ['TECH FR', 'EXPERT TEAM', 'SAVE', 'Onboarding',
            'KIA SERVICE DESK', 'Dispute', 'Customer advocate', 'CANCELATION',
            'ALICE TEAM', 'ADM FR', 'TECH NL', 'Outbound', 'ADM NL', 'Outbound (FND)',
            'BACK OFFICE'];

        foreach ($team_types as $team_type) {
            TeamType::factory()->create([
                'name' => strtoupper($team_type)
            ]);
        }
    }
}
