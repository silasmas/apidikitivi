<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logs de lecture et de performance envoyés par les applications (slides 9 et 10) :
 * lectures, fin de vidéo, abandon, erreurs, buffering, clics sur recommandations,
 * thumbnail_load_ms, video_startup_ms, api_error...
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('playback_events')) {
            return;
        }

        Schema::create('playback_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('event', 40);
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('media_id')->nullable();
            // durée mesurée (ms) pour les métriques de performance
            $table->unsignedInteger('value_ms')->nullable();
            $table->string('platform', 20)->nullable();
            $table->string('device_class', 10)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->string('device_id', 64)->nullable();
            $table->json('props')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'occurred_at']);
            $table->index(['media_id', 'event']);
            $table->index('user_id');
            // pas de clé étrangère : un log doit survivre à la suppression d'un média ou d'un compte
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playback_events');
    }
};
