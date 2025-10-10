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
            $blueprint->string('subdomain')->unique()->nullable()->index();
            $blueprint->unsignedBigInteger('owner_id')->index();
            $blueprint->boolean('online')->default(true);
            $blueprint->string('locale', '6')->default(config('app.locale'));
            ;
            $blueprint->string('currency', 3)->default('EUR');
            $blueprint->string('timezone', 50)->nullable();

            $blueprint->timestamp('archived_at')->nullable()->index();
            $blueprint->softDeletes();
            $blueprint->timestamps();

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
