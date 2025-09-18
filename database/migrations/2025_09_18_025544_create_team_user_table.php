<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_user', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->ulid()->unique();
            $blueprint->unsignedBigInteger('team_id')->index();
            $blueprint->unsignedBigInteger('user_id')->index();
            $blueprint->string('role', 32);
            $blueprint->timestamps();

            $blueprint->unique(['team_id', 'user_id']);

            $blueprint->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->cascadeOnDelete();

            $blueprint->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};
