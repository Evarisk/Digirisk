# Images « tuto » des actioncomm du plan d'action — Design

Issue : [Evarisk/Digirisk#4750](https://github.com/Evarisk/Digirisk/issues/4750)
Date : 2026-07-24

## Contexte

La page `admin/config/actionplan.php` (issues #4744 puis #4821) expose neuf bascules
`DIGIRISKDOLIBARR_ACTIONPLAN_LOG_*` dans un tableau *Nom / Description / État*. Le libellé et la
description disent quel événement sera créé, mais pas quelle manipulation du Kanban le déclenche.
L'admin doit deviner à quel endroit de l'interface correspond chaque bascule.

## Objectif

Ajouter une colonne *Image* montrant, pour chaque bascule, une capture de la zone du Kanban plan
d'action qui déclenche l'actioncomm.

## Rendu

La colonne *Image* s'insère entre *Description* et *État*, dans le seul tableau
« Plan d'Action — Historique des modifications ». Le tableau « Affichage Kanban » n'est pas modifié.

Chaque cellule contient une vignette de 150 px de large, `loading="lazy"`, curseur pointeur. Un clic
ouvre l'image à taille réelle dans un overlay plein écran (fond sombre, image limitée à 90 vw/90 vh) ;
un clic n'importe où ou la touche Échap ferme l'overlay. Si le fichier PNG est absent, la cellule
reste vide : pas d'image cassée.

## Images

Emplacement : `img/actionplan_log/<slug>.png`.

| Constante                                          | Slug                  | Zone capturée                                  |
|----------------------------------------------------|-----------------------|------------------------------------------------|
| `DIGIRISKDOLIBARR_ACTIONPLAN_LOG_LABEL`              | `label`               | Édition inline du label d'une carte            |
| `DIGIRISKDOLIBARR_ACTIONPLAN_LOG_WORKLOAD`           | `workload`            | Champ charge prévue                            |
| `DIGIRISKDOLIBARR_ACTIONPLAN_LOG_BUDGET`             | `budget`              | Champ budget                                   |
| `DIGIRISKDOLIBARR_ACTIONPLAN_LOG_CONTRIBUTOR_ADD`    | `contributor_add`     | Sélecteur de contributeurs, bouton d'ajout     |
| `DIGIRISKDOLIBARR_ACTIONPLAN_LOG_CONTRIBUTOR_REMOVE` | `contributor_remove`  | Contributeur affecté, croix de retrait         |
| `DIGIRISKDOLIBARR_ACTIONPLAN_LOG_PROGRESS`           | `progress`            | Carte déplacée entre deux colonnes             |
| `DIGIRISKDOLIBARR_ACTIONPLAN_LOG_TAG`                | `tag`                 | Sélecteur de tags                              |
| `DIGIRISKDOLIBARR_ACTIONPLAN_LOG_DATE_START`         | `date_start`          | Champ date de début                            |
| `DIGIRISKDOLIBARR_ACTIONPLAN_LOG_DATE_END`           | `date_end`            | Champ date de fin                              |

Captures produites via Playwright sur `view/digiriskstandard/actionplan_list.php` en local,
recadrées sur la zone concernée (≈600-700 px de large), au format PNG. L'attribut `alt` de chaque
vignette reprend le libellé traduit de la ligne.

## Code

- `admin/config/actionplan.php` : le tableau `$actionPlanLogs` gagne une troisième valeur, le slug
  de l'image ; ajout du `<th>` et du `<td>`, avec un test `dol_is_file()` avant de rendre la balise.
- Overlay : blocs `<style>` et `<script>` inline en fin de page. Le module n'a pas de JS admin dédié
  et son pipeline gulp régénère les `.min` en entier ; pour ~40 lignes sur une seule page admin,
  l'inline évite un rebuild d'assets.
- `langs/fr_FR/digiriskdolibarr.lang` : clé `ActionPlanLogTuto = Image` (le core Dolibarr n'a pas de
  clé `Image` seule). L'`en_US` du module ne contient aucune clé `ActionPlan*` : on reste aligné sur
  cet existant.

## Hors périmètre

- Upload des images par l'admin depuis la page.
- Factorisation d'un helper générique dans Saturne : à faire le jour où un autre module Saturne en a
  besoin, pour ne pas imposer une version minimale de Saturne à Digirisk.
- Parité `en_US` des clés `ActionPlan*`.

## Vérification

- `php -l` sur `admin/config/actionplan.php`.
- Playwright sur la page admin : les neuf vignettes s'affichent, l'overlay s'ouvre au clic et se
  ferme au clic et à l'Échap ; capture d'écran de la page finale.
