<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('estimated_value', 14, 2)->default(0);
            $table->unsignedTinyInteger('probability')->default(0);
            $table->date('expected_close_date')->nullable();
            $table->enum('stage', ['prospecting', 'proposal', 'negotiation', 'closed_won', 'closed_lost'])->default('prospecting');
            $table->timestamps();

            $table->index(['stage', 'expected_close_date']);
            $table->index(['assigned_user_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
