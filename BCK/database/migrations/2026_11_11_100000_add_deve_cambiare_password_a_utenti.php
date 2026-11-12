<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utenti', function (Blueprint $table) {
            $table->boolean('deve_cambiare_password')->default(false)->after('attivo');
        });
    }

    public function down(): void
    {
        Schema::table('utenti', function (Blueprint $table) {
            $table->dropColumn('deve_cambiare_password');
        });
    }
};
