<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = ['DSI', 'DRH', 'QF', 'MG', 'WFM'];

        foreach ($departments as $department) {
            Department::factory()->create([
                'name' => $department
            ]);
        }
    }
}
