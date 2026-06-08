# Widget Vigilance Météo-France sur le DigiBoard — Design

- **Issue** : [Evarisk/Digirisk#4767](https://github.com/Evarisk/Digirisk/issues/4767)
- **Date** : 2026-06-08
- **Module** : `digiriskdolibarr` (framework Saturne)
- **Story points** : à estimer (proposition : 5)

## 1. Contexte et objectif

Les entreprises ayant des activités sensibles à la météo (chantiers, travaux
extérieurs, interventions terrain) doivent pouvoir consulter immédiatement
l'état de vigilance météorologique de leur département dès l'arrivée sur le
tableau de bord Digirisk. La nouvelle réglementation 2025 sur les canicules
renforce ce besoin.

> **Précision d'intégration** — Le « DigiBoard » évoqué dans l'issue désigne le
> **tableau de bord de la page d'accueil du module**, rendu par
> `digiriskdolibarrindex.php` (→ `saturne/core/tpl/index/index_view.tpl.php` →
> `SaturneDashboard::show_dashboard()`). Ce n'est **pas** un module séparé : il
> n'existe pas de module `modDigiBoard` côté code. Tous les branchements de
> cette feature se font donc autour de `digiriskdolibarrindex.php`.

Le périmètre couvre :

1. Une **carte** sur le tableau de bord (`digiriskdolibarrindex.php`) affichant
   le niveau de vigilance Météo-France du département de la société
   (couleur + phénomènes actifs).
2. Un **bandeau d'alerte prioritaire** en tête de ce tableau de bord en cas de
   vigilance orange ou rouge.
3. Une **page de configuration admin** pour saisir la clé API Météo-France et,
   optionnellement, forcer un département.

## 2. Décisions validées

| Sujet | Décision |
|---|---|
| Source de données | **API officielle Météo-France DPVigilance** (clé API requise) |
| Portée du bandeau | **Tableau de bord (`digiriskdolibarrindex.php`) uniquement** |
| Département surveillé | **Département de la société (`$mysoc`)** avec override admin |
| Rafraîchissement | **Cache fichier avec TTL** lu au chargement (pas de cron) |

## 3. Source de données — API Météo-France

- **Endpoint** : `GET https://public-api.meteofrance.fr/public/DPVigilance/v1/cartevigilance/encours`
- **Authentification** : clé API applicative (gratuite, créée sur
  `portail-api.meteofrance.fr`). Envoi via l'en-tête `apikey: <clé>`.
  Si l'application n'expose qu'un identifiant OAuth2, une étape d'échange de
  token (`POST /token`, `grant_type=client_credentials`) sera ajoutée ; le
  schéma exact sera validé contre la doc live à l'implémentation, avec gestion
  gracieuse d'un 401 (clé invalide/expirée).
- **Réponse** : JSON. Bloc par département : niveau de couleur maximal et liste
  des phénomènes avec leur niveau.
- **Niveaux** : `1` vert, `2` jaune, `3` orange, `4` rouge.
- **Phénomènes** (id → libellé) : `1` Vent violent, `2` Pluie-inondation,
  `3` Orages, `4` Inondation, `5` Neige-verglas, `6` Canicule,
  `7` Grand-froid, `8` Avalanches, `9` Vagues-submersion.
- **Couverture** : France métropolitaine (codes `01`–`95`, `2A`, `2B`).
- **Mise à jour** : ~2×/jour (≈6h et 16h), plus réactualisations en événement.

## 4. Architecture et composants

Approche retenue : **une classe autonome `MeteoVigilance`** (client API + cache
+ construction du widget + données du bandeau), cohérente avec le pattern « une
classe par concern » du module (cf. `ticketdashboard.class.php`). Rejet d'un
découpage client/builder (indirection inutile, YAGNI) et d'une logique
entièrement dans le hook (violerait « pas de métier hors `class/` »).

| Fichier | Type | Rôle |
|---|---|---|
| `class/meteovigilance.class.php` | **nouveau** | Classe `MeteoVigilance` : `getDepartmentCode()`, `fetchVigilance(code)` (API + cache + parsing), `load_dashboard()` (widget), `getHighestLevel()` (bandeau). |
| `class/digiriskdolibarrdashboard.class.php` | modif | Ajout de l'entrée `['type' => 'MeteoVigilance', 'classPath' => '/meteovigilance.class.php']` dans `$dashboardDatas`. |
| `digiriskdolibarrindex.php` | modif | Ajout de `'LoadMeteoVigilance' => 1` dans `$moreParams`. |
| `class/actions_digiriskdolibarr.class.php` | modif | Hook `saturneIndex` (contexte `digiriskdolibarrindex`) → imprime le bandeau si niveau ≥ orange. |
| `admin/config/meteovigilance.php` | **nouveau** | Page de config : clé API + override département. |
| `lib/digiriskdolibarr.lib.php` | modif | Ajout d'un onglet « Vigilance Météo » dans `digiriskdolibarr_admin_prepare_head()`. |
| `core/tpl/meteovigilance/banner.tpl.php` | **nouveau** | Rendu HTML du bandeau (réutilise `.wpeo-notice`). |
| `langs/fr_FR/digiriskdolibarr.lang`, `langs/en_US/digiriskdolibarr.lang` | modif | Libellés niveaux, 9 phénomènes, messages d'erreur, titres admin. |
| `css/scss/.../_meteovigilance.scss` | nouveau (léger) | Couleurs de vigilance, scopées `.mod-digiriskdolibarr`. |
| `tests/phpunit/...MeteoVigilanceTest.php` | nouveau | Tests de parsing et de résolution du département. |

## 5. Constantes de configuration

| Constante | Type | Rôle |
|---|---|---|
| `DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_API_KEY` | string | Clé API Météo-France (vide = widget en mode « non configuré »). |
| `DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_DEPARTMENT` | string | Override du code département (`''` = déduit de `$mysoc`). |
| `DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_CACHE_TTL` | int | TTL du cache en secondes (défaut `3600`). |

Page admin calquée sur `admin/config/digiai.php` : formulaire `action=update`,
lecture via `GETPOST(..., 'alpha')`, persistance via `dolibarr_set_const(...,
$conf->entity)`. Sécurité : `$user->rights->digiriskdolibarr->adminpage->read`.

## 6. Flux de données

```
DigiBoard chargé (digiriskdolibarrindex.php → index_view.tpl.php)
  ├─ hook saturneIndex (actions_digiriskdolibarr)
  │    └─ MeteoVigilance::getHighestLevel()
  │         └─ niveau ≥ 3 ? → require banner.tpl.php (bandeau orange/rouge)
  └─ SaturneDashboard::show_dashboard()
       └─ DigiriskDolibarrDashboard::load_dashboard()
            └─ MeteoVigilance::load_dashboard()
                 ├─ getDepartmentCode() : override admin sinon
                 │     code_departement via $mysoc->state_id (c_departements)
                 ├─ fetchVigilance(code) :
                 │     ├─ cache temp/meteovigilance_<code>.json frais (< TTL) → lecture
                 │     └─ sinon → appel API → parse → écrit le cache
                 └─ construit le widget (.wpeo-infobox)
```

`getHighestLevel()` et `load_dashboard()` partagent le même
`fetchVigilance()` ; le cache garantit **un seul appel API par TTL** même si
les deux sont sollicités sur le même chargement (résultat mémoïsé en
propriété d'instance + cache disque).

### Cache

- Emplacement : `{multidir_output digiriskdolibarr}/temp/meteovigilance_<code>.json`.
- Fraîcheur : `filemtime` < `now - TTL` → réutilisé ; sinon réappel API.
- Contenu : payload **normalisé minimal** (niveau global + phénomènes), pas la
  réponse brute, pour un rendu rapide.

## 7. Résolution du département

`getDepartmentCode()` :

1. Si `DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_DEPARTMENT` non vide → l'utiliser.
2. Sinon, résoudre `code_departement` depuis `c_departements` via
   `$mysoc->state_id`.
3. Normaliser (2 caractères, gérer `2A`/`2B`).
4. Si non résolu ou hors métropole → état « département indéterminé » (pas
   d'appel API, message explicite).

## 8. Rendu du widget (`.wpeo-infobox`)

Structure produite par `load_dashboard()` (consommée par
`SaturneDashboard::show_dashboard()`) :

- `picto` : icône météo ; `pictoColor` : couleur du niveau maximal.
- `title` : « Vigilance météo ».
- Ligne « Niveau » : pastille couleur + libellé (`Vert`/`Jaune`/`Orange`/`Rouge`).
- Lignes « Phénomènes actifs » : un badge coloré par phénomène à niveau ≥ 2,
  rendu via `customContent` (badges colorés).
- `moreParams.links` : lien « Voir sur vigilance.meteofrance.fr ».
- `widgetName` : « Vigilance météo » (pour le menu d'ajout/masquage de widget).

États particuliers du widget :

- **Niveau vert** : « Aucune vigilance particulière » (carte informative).
- **Non configuré** (pas de clé) : message + lien vers la config admin.
- **Département indéterminé** : message explicite, pas d'appel API.

## 9. Bandeau d'alerte

- Rendu **uniquement si niveau ≥ orange (3)**, via le hook `saturneIndex`, donc
  au-dessus du dashboard (même emplacement que les notices patch-note).
- `core/tpl/meteovigilance/banner.tpl.php` : `.wpeo-notice notice-warning`
  (orange) ou `notice-error` (rouge).
- Texte : « Vigilance {couleur} — {département} : {liste des phénomènes} ».

## 10. Gestion d'erreurs et dégradation

- **Pas de clé API** → widget « non configuré » avec lien admin ; pas de bandeau.
- **Département introuvable / hors métropole** → message explicite ; pas d'appel.
- **Erreur réseau / API / timeout** : appel avec timeout court (~5 s),
  `try/catch`, `dol_syslog(LOG_WARNING)`. On sert le **dernier cache valide** si
  disponible, sinon message neutre « Données indisponibles ». Jamais de fatal,
  jamais de bandeau sur données absentes.
- **Réponse inattendue** (JSON invalide, structure changée) : traitée comme une
  erreur API (idem ci-dessus).

## 11. Performance

- Un appel API au maximum par TTL (mémoïsation instance + cache disque).
- Payload normalisé minimal en cache.
- Aucun JS/CSS chargé inutilement ; pas d'appel synchrone supplémentaire.
- Le bandeau et le widget réutilisent le même fetch (pas de double appel).

## 12. Internationalisation

`fr_FR` et `en_US` : libellés des 4 niveaux, des 9 phénomènes, titres et aide de
la page admin, messages d'erreur/états. Aucun `$langs->load()` manuel (Saturne
charge automatiquement) ; usage de `transnoentities`.

## 13. Tests

PHPUnit (`tests/phpunit/`), bootstrap stub :

- `fetchVigilance` **parsing** : à partir d'une fixture JSON Météo-France
  d'exemple → vérifie le niveau global et la liste des phénomènes extraits.
- `getHighestLevel` : déduction correcte du niveau max (seuil bandeau).
- `getDepartmentCode` : priorité override > `$mysoc`, normalisation `2A`/`2B`,
  cas « indéterminé ».

L'appel HTTP réel n'est pas testé (logique isolée derrière une méthode mockable
via fixture).

## 14. Conventions et garde-fous

- Zéro fichier hors `htdocs/custom/digiriskdolibarr/`.
- Pas de SQL en vue, pas de HTML en classe, pas de logique en TPL, pas de
  JS/CSS inline.
- PSR-12 (PHPCS), JSHint, PHPStan/Phan, conventions Git Saturne
  (`#4767 [MeteoVigilance] feat: ...`).
- `.min.css` / `.min.js` non commités manuellement (CI les génère).

## 15. Hors périmètre (YAGNI)

- Vigilance par site/élément Digirisk (multi-départements) — la société suffit.
- Bandeau global multi-pages — tableau de bord (`digiriskdolibarrindex.php`) uniquement.
- DOM-TOM (non couverts par cet endpoint).
- Historique / notifications push des vigilances.
