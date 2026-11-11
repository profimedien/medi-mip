<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Verzeichnis: Telefon und Handy als freiwillige Profilangaben. Ansprechpartner: intern (aus den Mitarbeitenden) oder extern
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('phone_extension');
            $table->string('mobile')->nullable()->after('phone');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('type')->default('internal')->after('id'); // internal, external
            $table->foreignId('user_id')->nullable()->after('type')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('type');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['phone', 'mobile']));
    }
};
