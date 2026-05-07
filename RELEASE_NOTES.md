# [Digirisk] [23.0.0] - Robustesse PHP 8 - Schéma SQL nettoyé

Description : Cette version durcit le code face à PHP 8 (sécurisation systématique des paramètres `id` via `GETPOSTINT` et casts en entier), nettoie en profondeur le schéma SQL des extrafields, et apporte plusieurs améliorations sur la liste des risques et les extrafields personnalisés.

## Nouvelles fonctionnalités et innovations

### Liste des risques

* Ajout de la personne assignée lorsqu'on édite une tâche directement depuis la liste des risques — plus besoin d'ouvrir la fiche du risque pour assigner un responsable.

<!-- 📸 Ajouter une screenshot ici -->

### Extrafields personnalisés

* Création rapide d'extrafields directement depuis l'interface, avec gestion des catégories d'extrafields.
* Nouvelles traductions associées à la création rapide d'extrafields.

<!-- 📸 Ajouter une screenshot ici -->

---

## Améliorations & corrections

### Robustesse PHP 8

* Tous les paramètres `id` lus depuis les requêtes utilisent désormais `GETPOSTINT` au lieu d'un cast manuel : élimine les `TypeError` rencontrés sur PHP 8 lors de l'appel à `fetch()`.
* Triggers : les arguments passés à `fetch()` sont systématiquement castés en `int` pour éviter tout `TypeError`.
* Corrigé sur les vues d'organisation, les éléments Digirisk et les accidents.

### Schéma SQL

* Refonte complète du schéma extrafields : suppression de tous les fichiers SQL d'extrafields obsolètes et des références dans `update.sql`.
* Ajout des tables d'extrafields manquantes pour `digiriskresources`, `accident_lesion`, `accidentmetadata` et `accident_investigation`.
* Suppression du surcharge `isextrafieldmanaged` sur toutes les classes — le comportement par défaut de Saturne est désormais utilisé.

### Création de groupements / unités de travail

* Création d'un GP/UT ne provoque plus d'erreur SQL : `isCategoryManaged` est correctement positionné à `0` sur la classe `DigiriskElement`.

### Documents projet

* `ProjectDocument::write_file` accepte maintenant une valeur par défaut pour `moreParam` — corrige les générations de documents projet où ce paramètre n'était pas fourni.

### Class

* Champ `picto` correctement typé en chaîne (string) sur les classes principales.
* Plusieurs passes de nettoyage de code (lisibilité, signatures, suppression de code mort).

### Extrafields

* Le label des extrafields s'enregistre désormais correctement (au lieu d'une chaîne tronquée ou vide dans certains cas).

## Comparaison des versions [22.1.0](https://github.com/Evarisk/Digirisk/compare/22.1.0...23.0.0) et 23.0.0

* [#4727] [ProjectDocument] fix: add default value to moreParam in write_file [`#4728`](https://github.com/Evarisk/Digirisk/pull/4728)
* [#4725] [DigiriskElement] fix: set isCategoryManaged to 0 to prevent invalid SQL on GP/UT creation [`#4726`](https://github.com/Evarisk/Digirisk/pull/4726)
* [#4722] [DigiriskElement] fix: use GETPOSTINT for id in organization view [`#4723`](https://github.com/Evarisk/Digirisk/pull/4723)
* [#4719] [DigiriskElement] fix: use GETPOSTINT for id parameter to avoid TypeError on fetch() [`#4720`](https://github.com/Evarisk/Digirisk/pull/4720)
* [#4718] [Trigger] fix: cast fetch() arguments to int to avoid TypeError [`#4721`](https://github.com/Evarisk/Digirisk/pull/4721)
* [#4716] [Accident] fix: use GETPOSTINT instead of cast to int [`1db00fd4`](https://github.com/Evarisk/Digirisk/commit/1db00fd4) [`ff127e6a`](https://github.com/Evarisk/Digirisk/commit/ff127e6a)
* [#4711] [SQL] rework: remove all extrafields SQL files and update.sql references [`862c89de`](https://github.com/Evarisk/Digirisk/commit/862c89de)
* [#4711] [Class] rework: remove isextrafieldmanaged override from all classes [`4c3a3d2b`](https://github.com/Evarisk/Digirisk/commit/4c3a3d2b)
* [#4711] [SQL] fix: add missing extrafields tables [`b4f4fc09`](https://github.com/Evarisk/Digirisk/commit/b4f4fc09)
* [#4700] [Class] fix: picto type string [`5b3d8045`](https://github.com/Evarisk/Digirisk/commit/5b3d8045) [`e71ebcda`](https://github.com/Evarisk/Digirisk/commit/e71ebcda)
* [#4661] [RiskList] add: assigned person when editing task in risk list [`4b6214a5`](https://github.com/Evarisk/Digirisk/commit/4b6214a5)
* [#4683] [Extrafield] add: quick extra create and categories for extrafield [`f0e25c51`](https://github.com/Evarisk/Digirisk/commit/f0e25c51)
* [#4683] [Extrafield] fix: label not written properly [`6eeb8ea2`](https://github.com/Evarisk/Digirisk/commit/6eeb8ea2)
* [#4695] [Lang] add: lang trans for quick extrafield creation [`856a6a27`](https://github.com/Evarisk/Digirisk/commit/856a6a27)
