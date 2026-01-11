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
        Schema::table('stored_events', function (Blueprint $table): void {
            $table->string('event_hash', 64)->nullable()->after('meta_data');
            $table->string('previous_hash', 64)->nullable()->after('event_hash');
            $table->index('event_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stored_events', function (Blueprint $table): void {
            $table->dropIndex(['event_hash']);
            $table->dropColumn(['event_hash', 'previous_hash']);
        });
    }
};
