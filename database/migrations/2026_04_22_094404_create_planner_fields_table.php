<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_fields', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
            $table->string('name');
            $table->string('type');
            $table->json('options')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_fields');
    }
};
