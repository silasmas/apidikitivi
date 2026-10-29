# Déploiement Dikitivi v2 (API + back-office)

Lecture directe, reprise, séries, profils enfants sans code, filtres Jeunesse administrables, logs de lecture.

**Ordre à respecter : 1. API (migrations) → 2. back-office admin → 3. nouvelle version de l'app.**
L'app mobile v2 fonctionne aussi avec l'ancienne API (elle se replie sur les anciens endpoints), et l'app actuellement en production n'est pas impactée par la nouvelle API : les changements sont uniquement des ajouts.

---

## 1. Ce qui change en base de données

5 nouvelles migrations dans `apidikitivi/database/migrations/` :

| Migration | Effet |
|---|---|
| `2026_10_29_000001_add_playback_fields_to_medias_table` | `medias` : + `age_range`, `is_autoplay_eligible` (défaut 1), `is_downloadable` (défaut 0), `season_number`, `episode_number`, `faith_step`, `tags` (JSON) ; index sur `belongs_to` et `(is_public, for_youth)` |
| `2026_10_29_000002_add_child_profile_fields_to_users_table` | `users` : + `autoplay_enabled` (défaut 1), `preferred_themes` (JSON) ; index sur `belongs_to` |
| `2026_10_29_000003_create_watch_progress_table` | nouvelle table `watch_progress` (historique de lecture par profil) |
| `2026_10_29_000004_create_playback_events_table` | nouvelle table `playback_events` (logs de lecture / performance) |
| `2026_10_29_000005_create_content_filters_table` | nouvelle table `content_filters` + 8 filtres par défaut |

Aucune colonne existante n'est modifiée ni supprimée. Toutes les migrations sont idempotentes (elles vérifient l'existence des tables / colonnes / index avant d'agir).

> ⚠️ La base de production a été créée à partir d'un dump SQL : elle n'a probablement **pas** de table `migrations`.
> C'est prévu : les 5 migrations Laravel d'origine (`users`, `personal_access_tokens`, …) ont été sécurisées. Elles ne recréent pas une table existante et leur `down()` ne supprime plus rien. Au premier `migrate`, Laravel crée la table `migrations`, enregistre ces 5 migrations sans rien modifier, puis applique les 5 nouvelles. Seules `failed_jobs` et `password_reset_tokens` peuvent être créées si elles n'existent pas (tables vides, sans impact).

Testé sur une copie locale de la base (MySQL 8.0) : migration, rollback des 5 nouvelles, puis ré-application, sans perte de données.

Aucune nouvelle variable d'environnement.

---

## 2. Déployer l'API (`api.dikitivi.com`)

Prérequis : PHP **8.1 ou 8.2** (imposé par le `composer.lock`), MySQL 8.

```bash
# 0. Sauvegarde complète de la base (OBLIGATOIRE)
mysqldump -h <HOST> -u <USER> -p --single-transaction --routines <DB_NAME> > dikitivi_avant_v2_$(date +%F).sql

# 1. Code
cd /chemin/vers/apidikitivi
php artisan down --retry=60          # optionnel, quelques secondes
git pull                             # ou copie des fichiers livrés
composer install --no-dev --optimize-autoloader

# 2. Vérifier ce qui va être exécuté (aucune écriture)
php artisan migrate:status           # "Migration table not found" = normal la première fois
php artisan migrate --pretend        # affiche le SQL des migrations

# 3. Appliquer
php artisan migrate --force

# 4. Caches
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
php artisan up
```

Ne pas lancer `php artisan route:cache` : certaines routes de `routes/web.php` sont des closures.

### Vérifications après déploiement

```bash
API=https://api.dikitivi.com/api

# Filtres Jeunesse (public) → 8 filtres
curl -s -H "Accept: application/json" $API/content_filter

# Épisodes d'une série dans l'ordre (public)
curl -s -H "Accept: application/json" $API/media/223/episodes

# Logs (public) → {"received":1}
curl -s -H "Accept: application/json" -H "Content-Type: application/json" \
  -X POST $API/playback_event/batch -d '{"platform":"test","events":[{"event":"video_play","props":{"id":252}}]}'

# Profils de la famille (avec le token d'un compte parent)
curl -s -H "Accept: application/json" -H "Authorization: Bearer <TOKEN>" $API/profiles
```

Puis supprimer le log de test : `DELETE FROM playback_events WHERE platform = 'test';`

### Retour arrière

```bash
php artisan migrate:rollback --step=5 --force   # retire uniquement les 5 nouvelles migrations
# puis redéployer la version précédente du code
```

En dernier recours : restaurer le dump de l'étape 0.

---

## 3. Déployer le back-office (`admin.dikitivi.com`)

Le back-office lit la même base. Il n'y a **aucune migration à lancer côté admin** : les tables et colonnes sont créées par l'API (étape 2, à faire avant).

```bash
cd /chemin/vers/admindikitivi
git pull
composer install --no-dev --optimize-autoloader
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
```

Nouveautés visibles :
- **Media → étape « Lecture & recommandation »** : tranche d'âge, éligible autoplay, **Téléchargeable Oui/Non**, saison, n° d'épisode, étape de foi, thèmes.
- **Configuration → Filtres Jeunesse** : ajouter, renommer, réordonner ou désactiver les filtres de l'app.
- **Logs de lecture** : liste filtrable et indicateurs sur 7 jours (lectures, vidéos terminées, démarrage moyen, erreurs).

Permissions : ces 2 nouveaux écrans n'ont pas de policy Shield. Ils sont donc visibles par tous les utilisateurs ayant accès au panel. Pour les restreindre :
`php artisan shield:generate --resource=ContentFilterResource,PlaybackEventResource`, puis attribuer les permissions aux rôles dans **Rôles et permissions**.

---

## 4. Application mobile

Nouvelle version à construire avec EAS (`eas build`). Elle contient des modules natifs ajoutés lors de la refonte, donc une mise à jour OTA ne suffit pas.

---

## 5. Exploitation

- `playback_events` grossit avec l'usage (environ 5 à 10 lignes par vidéo regardée). Purge conseillée une fois par mois :
  `DELETE FROM playback_events WHERE occurred_at < NOW() - INTERVAL 90 DAY;`
- Limites de débit : `playback_event/batch` 30 requêtes par minute et par IP, `parental_code/*` et `profiles/{id}/switch` 10 par minute. Elles utilisent le cache Laravel (`CACHE_DRIVER=file` suffit).

---

## 6. Nouveaux endpoints (référence)

| Méthode | Route | Auth | Rôle |
|---|---|---|---|
| GET | `/api/content_filter` | — | Filtres Jeunesse actifs |
| GET | `/api/media/{id}/episodes` | — | Épisodes d'une série (saison, épisode) |
| POST | `/api/playback_event/batch` | optionnelle | Logs de lecture / performance (max 100 par lot) |
| GET | `/api/profiles` | Bearer | Parent + enfants du compte |
| POST | `/api/profiles` | Bearer parent + code | Ajouter un enfant |
| PUT | `/api/profiles/{id}` | Bearer parent + code | Modifier un enfant (autoplay, thèmes…) |
| DELETE | `/api/profiles/{id}` | Bearer parent + code | Supprimer un enfant |
| POST | `/api/profiles/{id}/switch` | Bearer | Ouvrir un profil (enfant : sans code ; retour parent : code) |
| POST | `/api/parental_code/verify` | Bearer | Vérifier le code parental |
| PUT | `/api/parental_code` | Bearer parent | Créer (mot de passe) ou changer (ancien code) le code |
| GET | `/api/history` | Bearer | Historique du profil |
| PUT | `/api/history/{media_id}` | Bearer | Enregistrer une position |
| POST | `/api/history/sync` | Bearer | Fusionner l'historique de l'appareil |
| GET | `/api/recommendation/common` | Bearer | « Commun » : populaire chez les profils du même âge |
| GET | `/api/playback_event/stats` | Bearer Administrateur/Contrôleur | Statistiques de lecture |

Champs ajoutés dans les réponses existantes : `media` gagne `age_range`, `is_autoplay_eligible`, `is_downloadable`, `season_number`, `episode_number`, `faith_step` et `tags` ; `user` gagne `autoplay_enabled` et `preferred_themes`.
