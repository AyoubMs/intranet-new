<?php

namespace Database\Seeders;

use App\Models\Operation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OperationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operations = ['LEBARA-France', 'ORTEL Allemagne', 'WORLDLINE',
            'LEBARA-Allemagne', 'SCARLET', 'ORTEL Belgique', 'TELENET', 'LFM',
            'BASE', 'LEBARA', 'CORONA DIRECT', 'UNIT-T',
            'BOFROST', 'MKB', 'SIP Communication', 'ENGIE',
            'VHC', 'ENECO', 'DIRECTION', 'BAS', 'De Wilde & Baele', 'SOGEDES',
            'HYTECH', 'Direction', 'TUI FLY'];

        foreach ($operations as $operation) {
            Operation::factory()->create([
                'name' => $operation
            ]);
        }
    }
}
