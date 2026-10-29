<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Filtres "Jeunesse" par type de contenu Dikitivi (Bloc 2), administrables depuis le dashboard.
 * Un média correspond à un filtre s'il a l'un des types OU l'une des catégories listés.
 * Le filtre "Tous" n'est pas stocké : l'app l'affiche toujours en premier.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('content_filters')) {
            Schema::create('content_filters', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('filter_key', 40)->unique();
                $table->json('filter_name');
                $table->json('type_ids')->nullable();
                $table->json('category_ids')->nullable();
                $table->unsignedSmallInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (DB::table('content_filters')->count() > 0) {
            return;
        }

        // Valeurs initiales = filtres v1 de l'application mobile (src/constants/contentFilters.ts)
        $defaults = [
            ['cartoons', ['fr' => 'Dessins animés', 'en' => 'Cartoons', 'ln' => 'Bililingi ya bana'], [19], [44, 6]],
            ['bible', ['fr' => 'Histoires bibliques', 'en' => 'Bible stories', 'ln' => 'Masolo ya Biblia'], [], [40]],
            ['series', ['fr' => 'Séries', 'en' => 'Series', 'ln' => 'Ba série'], [4, 5], []],
            ['films', ['fr' => 'Films', 'en' => 'Movies', 'ln' => 'Ba film'], [3], []],
            ['music', ['fr' => 'Musique & Louange', 'en' => 'Music & Worship', 'ln' => 'Nzembo & Lokumu'], [8, 7], [42, 21]],
            ['teachings', ['fr' => 'Enseignements', 'en' => 'Teachings', 'ln' => 'Mateya'], [], [14]],
            ['shows', ['fr' => 'Émissions', 'en' => 'Shows', 'ln' => 'Ba émission'], [6], [13]],
            ['teens', ['fr' => 'Adolescents', 'en' => 'Teens', 'ln' => 'Bilenge'], [], [50]],
        ];

        foreach ($defaults as $position => [$key, $name, $types, $categories]) {
            DB::table('content_filters')->insert([
                'filter_key' => $key,
                'filter_name' => json_encode($name, JSON_UNESCAPED_UNICODE),
                'type_ids' => json_encode($types),
                'category_ids' => json_encode($categories),
                'position' => $position + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_filters');
    }
};
