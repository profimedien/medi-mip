<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Zielgruppe: alle, Standorte, Abteilungen, Einzelpersonen (siehe App\Models\Concerns\HasAudience) */
    private function audience(Blueprint $table): void
    {
        $table->boolean('audience_all')->default(true);
        $table->json('audience_locations')->nullable();
        $table->json('audience_departments')->nullable();
        $table->json('audience_users')->nullable();
    }

    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('teaser')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('pinned')->default(false);
            $table->boolean('notify_mail')->default(false);
            $table->boolean('requires_confirmation')->default(false);
            $this->audience($table);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('post_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('place')->nullable();
            $this->audience($table);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question');
            $table->longText('answer');
            $table->boolean('published')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('faq_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->string('status')->default('open'); // open, answered, rejected
            $table->foreignId('faq_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('responsibility');
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();
            $this->audience($table);
            $table->boolean('published')->default(true);
            $table->timestamps();
        });

        Schema::create('org_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->string('name');
            $table->string('head_name')->nullable();
            $table->foreignId('head_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('onboarding_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('icon')->nullable();
            $table->longText('body')->nullable();
            $table->string('video_url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['links', 'onboarding_sections', 'org_units', 'galleries', 'contacts', 'faq_questions', 'faqs', 'faq_categories', 'events', 'post_reads', 'posts'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
