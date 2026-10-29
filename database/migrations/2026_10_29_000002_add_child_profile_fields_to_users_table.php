<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Profils enfants (Bloc 3) : un enfant reste un "users" rattaché au parent via belongs_to.
 * On ajoute les réglages gérés par le parent (autoplay, thèmes préférés).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'autoplay_enabled')) {
                $table->boolean('autoplay_enabled')->default(true)->after('parental_code');
            }

            if (!Schema::hasColumn('users', 'preferred_themes')) {
                // ex. ["bible_stories","jesus","prayer"]
                $table->json('preferred_themes')->nullable()->after('autoplay_enabled');
            }
        });

        if (!$this->hasIndex('users', 'users_belongs_to_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('belongs_to');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('users', 'users_belongs_to_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_belongs_to_index');
            });
        }

        $columns = array_filter(['autoplay_enabled', 'preferred_themes'], fn ($column) => Schema::hasColumn('users', $column));

        if (!empty($columns)) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return !empty(DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index]));
    }
};
