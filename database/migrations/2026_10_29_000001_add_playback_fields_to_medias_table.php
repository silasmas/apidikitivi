<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin Media (slide 9) : tranche d'âge, autoplay, téléchargement,
 * saisons/épisodes et tags du moteur de recommandation.
 * Idempotente : chaque colonne n'est ajoutée que si elle manque.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medias', function (Blueprint $table) {
            if (!Schema::hasColumn('medias', 'age_range')) {
                // "3-5", "6-8", "9-11", "12+", "all"
                $table->string('age_range', 20)->nullable()->after('for_youth');
            }

            if (!Schema::hasColumn('medias', 'is_autoplay_eligible')) {
                $table->boolean('is_autoplay_eligible')->default(true)->after('age_range');
            }

            if (!Schema::hasColumn('medias', 'is_downloadable')) {
                $table->boolean('is_downloadable')->default(false)->after('is_autoplay_eligible');
            }

            if (!Schema::hasColumn('medias', 'season_number')) {
                $table->unsignedSmallInteger('season_number')->nullable()->after('belongs_to');
            }

            if (!Schema::hasColumn('medias', 'episode_number')) {
                $table->unsignedSmallInteger('episode_number')->nullable()->after('season_number');
            }

            if (!Schema::hasColumn('medias', 'faith_step')) {
                // jesus, bible_stories, prayer, faith_character, worship_music, christian_life, series
                $table->string('faith_step', 40)->nullable()->after('episode_number');
            }

            if (!Schema::hasColumn('medias', 'tags')) {
                $table->json('tags')->nullable()->after('faith_step');
            }
        });

        // Les listes d'épisodes filtrent sur belongs_to, qui n'avait pas d'index.
        if (!$this->hasIndex('medias', 'medias_belongs_to_index')) {
            Schema::table('medias', function (Blueprint $table) {
                $table->index('belongs_to');
            });
        }

        if (!$this->hasIndex('medias', 'medias_is_public_for_youth_index')) {
            Schema::table('medias', function (Blueprint $table) {
                $table->index(['is_public', 'for_youth']);
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('medias', 'medias_belongs_to_index')) {
            Schema::table('medias', function (Blueprint $table) {
                $table->dropIndex('medias_belongs_to_index');
            });
        }

        if ($this->hasIndex('medias', 'medias_is_public_for_youth_index')) {
            Schema::table('medias', function (Blueprint $table) {
                $table->dropIndex('medias_is_public_for_youth_index');
            });
        }

        $columns = array_filter(
            ['age_range', 'is_autoplay_eligible', 'is_downloadable', 'season_number', 'episode_number', 'faith_step', 'tags'],
            fn ($column) => Schema::hasColumn('medias', $column)
        );

        if (!empty($columns)) {
            Schema::table('medias', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return !empty(DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index]));
    }
};
