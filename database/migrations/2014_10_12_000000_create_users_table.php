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
        Schema::create('users', function (Blueprint $table) {
            $table->id()->index();
            $table->string('matricule')->index()->unique();
            $table->string('first_name')->index();
            $table->string('last_name')->index();
            $table->date('date_naissance')->index()->nullable();
            $table->string('Sexe')->index();
            $table->string('phone')->index()->nullable();
            $table->string('situation_familiale')->index()->nullable();
            $table->string('phone_1')->index()->nullable();
            $table->string('phone_2')->index()->nullable();
            $table->string('photo')->index()->nullable();
            $table->string('nombre_enfants')->index()->nullable();
            $table->string('address')->index()->nullable();
            $table->double('solde_cp')->index()->nullable();
            $table->double('solde_rjf')->index()->nullable();
            $table->unsignedBigInteger('role_id')->nullable()->index();
            $table->date('date_entree_formation')->index()->nullable();
            $table->date('date_entree_production')->index()->nullable();
            $table->date('date_depart')->index()->nullable();
            $table->boolean('active')->index()->nullable();
            $table->unsignedBigInteger('primary_language_id')->nullable()->index();
            $table->unsignedBigInteger('sourcing_type_id')->nullable()->index();
            $table->unsignedBigInteger('family_situation_id')->nullable()->index();
            $table->unsignedBigInteger('nationality_id')->nullable()->index();
            $table->unsignedBigInteger('secondary_language_id')->nullable()->index();
            $table->unsignedBigInteger('motif_depart_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('team_type_id')->nullable()->index();
            $table->unsignedBigInteger('comment_id')->nullable()->index();
            $table->unsignedBigInteger('creator_id')->nullable()->index();
            $table->string('cnss_number')->index()->nullable();
            $table->string('email_1')->unique()->index()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('provider_id')->nullable()->index();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
