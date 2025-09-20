<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->string('language_code', 10);
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['post_id', 'language_code']);
            $table->index('language_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_translations');
    }
};