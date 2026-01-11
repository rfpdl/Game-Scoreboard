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
            $table->enum('match_type', ['quick', 'booked'])->default('quick')->after('match_code');
            $table->string('name')->nullable()->after('match_type');
            $table->timestamp('scheduled_at')->nullable()->after('name');
            $table->string('share_token', 32)->unique()->nullable()->after('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table): void {
            $table->dropColumn(['match_type', 'name', 'scheduled_at', 'share_token']);
        });
    }
};
