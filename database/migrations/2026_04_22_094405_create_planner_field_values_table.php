<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_field_values', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('field_id')->constrained('planner_fields')->cascadeOnDelete();
            $table->ulidMorphs('item');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['field_id', 'item_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_field_values');
    }
};
