<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique de lecture par profil : reprise, contenus terminés, fréquence de visionnage.
 * Une ligne par (profil, média).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('watch_progress')) {
            return;
        }

        Schema::create('watch_progress', function (Blueprint $table) {
            // Types alignés sur users.id / medias.id (BIGINT signé dans le dump)
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->bigInteger('media_id');
            $table->unsignedInteger('position_seconds')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('completed')->default(false);
            $table->unsignedInteger('play_count')->default(0);
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'media_id']);
            $table->index(['user_id', 'last_watched_at']);
            $table->index(['media_id', 'last_watched_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('media_id')->references('id')->on('medias')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_progress');
    }
};
