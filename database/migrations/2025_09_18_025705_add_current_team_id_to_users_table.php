<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('current_team_id')->nullable()->after('password');
            $blueprint->foreign('current_team_id')
                ->references('id')
                ->on('teams')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['current_team_id']);
            $blueprint->dropColumn('current_team_id');
        });
    }
};
