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
        Schema::create('demande_conges', function (Blueprint $table) {
            $table->id();
            $table->date('date_demande')->index()->nullable();
            $table->date('date_retour')->index()->nullable();
            $table->date('date_debut')->index()->nullable();
            $table->date('date_fin')->index()->nullable();
            $table->date('date_validation_niveau_1')->index()->nullable();
            $table->date('date_validation_niveau_2')->index()->nullable();
            $table->date('date_validation_niveau_3')->index()->nullable();
            $table->date('date_validation_niveau_4')->index()->nullable();
            $table->date('date_modification_par_rh')->index()->nullable();
            $table->string('periode')->index()->nullable();
            $table->double('nombre_jours')->index()->nullable();
            $table->unsignedBigInteger('etat_demande_id')->index()->nullable();
            $table->unsignedBigInteger('modification_solde_comment_id')->index()->nullable();
            $table->unsignedBigInteger('type_conge_id')->index()->nullable();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_conges');
    }
};
