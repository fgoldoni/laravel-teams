<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->ulid()->unique();
            $table->string('name', 255);
            $table->unsignedBigInteger('owner_id')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('owner_id')
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
