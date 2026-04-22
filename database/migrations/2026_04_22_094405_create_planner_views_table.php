<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_views', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('list');
            $table->boolean('is_default')->default(false);
            $table->json('layout')->nullable();
            $table->json('filters')->nullable();
            $table->json('sorts')->nullable();
            $table->json('group_by')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_views');
    }
};
