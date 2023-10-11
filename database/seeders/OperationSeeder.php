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
        $activeOperations = ['LEBARA-France', 'ORTEL Allemagne', 'WORLDLINE',
            'LEBARA-Allemagne', 'SCARLET', 'ORTEL Belgique', 'TELENET', 'LFM',
            'LEBARA', 'UNIT-T', 'BOFROST', 'MKB', 'SIP Communication', 'ENGIE',
            'VHC', 'ENECO', 'DIRECTION', 'BAS', 'De Wilde & Baele', 'SOGEDES',
            'HYTECH', 'Direction', 'TUI FLY'];
        $inactiveOperations = ['BASE', 'CORONA DIRECT'];

        Operation::truncate();

        foreach ($activeOperations as $operation) {
            Operation::factory()->create([
                'name' => $operation,
                'active' => true,
            ]);
        }

        foreach ($inactiveOperations as $inactiveOperation) {
            Operation::factory()->create([
                'name' => $inactiveOperation,
                'active' => false,
            ]);
        }
    }
}
