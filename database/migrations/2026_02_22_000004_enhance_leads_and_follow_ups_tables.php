<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE leads MODIFY status ENUM('new','contacted','qualified','interested','converted','lost') NOT NULL DEFAULT 'new'");

        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedTinyInteger('score')->default(0)->after('follow_up_date');
            $table->string('score_band', 20)->default('cold')->after('score');
            $table->unsignedTinyInteger('conversion_probability')->default(0)->after('score_band');
            $table->timestamp('best_follow_up_at')->nullable()->after('conversion_probability');

            $table->index('score');
            $table->index('score_band');
            $table->index('conversion_probability');
        });

        Schema::table('follow_ups', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'missed'])->default('pending')->after('notes');
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'completed_at']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['score']);
            $table->dropIndex(['score_band']);
            $table->dropIndex(['conversion_probability']);
            $table->dropColumn(['score', 'score_band', 'conversion_probability', 'best_follow_up_at']);
        });

        DB::statement("ALTER TABLE leads MODIFY status ENUM('new','contacted','interested','converted','lost') NOT NULL DEFAULT 'new'");
    }
};
