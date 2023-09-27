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
            $table->unsignedBigInteger('role_id')->nullable()->index();
            $table->date('date_entree_formation')->index()->nullable();
            $table->date('date_depart')->index()->nullable();
            $table->unsignedBigInteger('language_id')->nullable()->index();
            $table->unsignedBigInteger('motif_depart_id')->nullable()->index();
            $table->unsignedBigInteger('manager_id')->nullable()->index();
            $table->unsignedBigInteger('operation_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->string('cnss_number')->index()->nullable();
            $table->string('email')->unique()->index()->nullable();
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
