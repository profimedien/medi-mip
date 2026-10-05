<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classifieds', fn (Blueprint $t) => $t->string('topic')->nullable()->after('category'));
        Schema::table('forms', fn (Blueprint $t) => $t->unsignedSmallInteger('reminder_hours')->default(48)->after('send_reminder'));
    }

    public function down(): void
    {
        Schema::table('classifieds', fn (Blueprint $t) => $t->dropColumn('topic'));
        Schema::table('forms', fn (Blueprint $t) => $t->dropColumn('reminder_hours'));
    }
};
