<?php

declare(strict_types=1);

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
        Schema::table('matches', function (Blueprint $table): void {
            // Match format: 1v1 (default), 2v2, or ffa (free-for-all)
            $table->string('match_format', 10)->default('1v1')->after('match_code');
            // Maximum players allowed in the match
            $table->unsignedTinyInteger('max_players')->default(2)->after('match_format');
        });

        Schema::table('match_players', function (Blueprint $table): void {
            // Team assignment for 2v2 matches (null for 1v1 and FFA)
            $table->string('team', 10)->nullable()->after('user_id');
            // Placement for FFA matches (1 = winner, 2 = second, etc.)
            $table->unsignedTinyInteger('placement')->nullable()->after('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table): void {
            $table->dropColumn(['match_format', 'max_players']);
        });

        Schema::table('match_players', function (Blueprint $table): void {
            $table->dropColumn(['team', 'placement']);
        });
    }
};
