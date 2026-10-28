<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // MariaDB legt die erste NOT-NULL-timestamp-Spalte mit "ON UPDATE current_timestamp()" an.
    // Dadurch lief jede Kleinanzeige beim Bearbeiten sofort ab. dateTime hat diese Automatik nicht.
    public function up(): void
    {
        Schema::table('classifieds', function (Blueprint $table) {
            $table->dateTime('expires_at')->change();
        });
        Schema::table('post_reads', function (Blueprint $table) {
            $table->dateTime('read_at')->change();
        });
    }

    public function down(): void
    {
        Schema::table('classifieds', function (Blueprint $table) {
            $table->timestamp('expires_at')->change();
        });
        Schema::table('post_reads', function (Blueprint $table) {
            $table->timestamp('read_at')->change();
        });
    }
};
