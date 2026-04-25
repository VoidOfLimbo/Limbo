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
        Schema::create('events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
            $table->char('parent_event_id', 26)->nullable()->index(); // self-referencing FK added below
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('event');
            $table->string('status')->default('upcoming');
            $table->string('priority')->default('medium');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->boolean('is_milestone_marker')->default(false);
            $table->json('recurrence_rule')->nullable();
            $table->timestamp('recurrence_ends_at')->nullable();
            $table->unsignedInteger('recurrence_count')->nullable();
            $table->string('visibility')->default('private');
            $table->string('color', 7)->nullable();
            $table->string('location')->nullable();
            $table->timestamp('snoozed_until')->nullable();
            $table->unsignedSmallInteger('snooze_count')->default(0);
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'start_at']);
            $table->index(['milestone_id', 'start_at']);
            $table->index(['user_id', 'status']);
            $table->index('snoozed_until');
        });

        // Add the self-referencing FK after the table exists so PostgreSQL can validate it
        Schema::table('events', function (Blueprint $table) {
            $table->foreign('parent_event_id')->references('id')->on('events')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
