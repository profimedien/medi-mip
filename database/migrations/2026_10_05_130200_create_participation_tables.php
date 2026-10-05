<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ein Formular-Kern für Zu-/Absage/Kenntnisnahme und das dynamische Rückmeldetool
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->json('choices')->nullable(); // z. B. ["zusage","absage","kenntnis"]
            $table->boolean('requires_signature')->default(false);
            $table->dateTime('deadline')->nullable();
            $table->boolean('send_reminder')->default(true);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->boolean('audience_all')->default(true);
            $table->json('audience_locations')->nullable();
            $table->json('audience_departments')->nullable();
            $table->json('audience_users')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // boolean, select, multiselect, text, number
            $table->string('label');
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('choice')->nullable();
            $table->json('answers')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
            $table->unique(['form_id', 'user_id']);
        });

        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('topic');
            $table->text('body');
            $table->string('status')->default('new'); // new, in_progress, done
            $table->timestamps();
        });

        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item');
            $table->string('quantity')->nullable();
            $table->date('needed_by')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('candidate_name');
            $table->string('candidate_contact')->nullable();
            $table->string('position')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();
        });

        Schema::create('classifieds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // private, business
            $table->string('title');
            $table->text('body');
            $table->string('price')->nullable();
            $table->timestamp('expires_at')->index();
            $table->boolean('extended')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['classifieds', 'referrals', 'material_requests', 'suggestions', 'form_responses', 'form_fields', 'forms'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
