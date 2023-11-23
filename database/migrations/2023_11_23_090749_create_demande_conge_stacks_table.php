<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demande_conge_stacks', function (Blueprint $table) {
            $table->id();
            $table->double('solde_cp')->index()->nullable();
            $table->double('solde_rjf')->index()->nullable();
            $table->unsignedBigInteger('demande_conge_id')->index()->nullable();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_conge_stacks');
    }
};
