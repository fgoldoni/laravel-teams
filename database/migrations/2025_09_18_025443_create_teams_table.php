<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->ulid()->unique();
            $blueprint->string('avatar', 2048)->nullable();
            $blueprint->string('name', 255);
            $blueprint->unsignedBigInteger('owner_id')->index();
            $blueprint->boolean('online')->default(false);
            $blueprint->timestamps();
            $blueprint->softDeletes();

            $blueprint->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
