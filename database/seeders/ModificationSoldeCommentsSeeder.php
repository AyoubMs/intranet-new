<?php

namespace Database\Seeders;

use App\Models\ModificationSoldeComment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModificationSoldeCommentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $comments = ["Augmentation De Solde", "Consommation par demande de congé", "Modification par RH in editing", "Modification par RH in creation"];

        foreach ($comments as $comment) {
            ModificationSoldeComment::factory()->create([
                'comment_on_solde' => $comment
            ]);
        }
    }
}
