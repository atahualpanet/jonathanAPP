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
    Schema::table('camere', function (Blueprint $table) {
        $table->string('foto_path')->nullable()->after('note');
        $table->string('piantina_path')->nullable()->after('foto_path');
    });
}

public function down(): void
{
    Schema::table('camere', function (Blueprint $table) {
        $table->dropColumn(['foto_path', 'piantina_path']);
    });
}
};
