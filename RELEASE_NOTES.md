# [Digirisk] [23.5.0] - Listing des risques enrichi - Dolibarr 24 - Conformité Dolistore

Description : Cette version enrichit le **listing des risques**, qui reprend désormais les accidents et les registres santé et sécurité, ajoute la **cotation et le pictogramme du type de risque** sur les cartes du plan d'action, et un **modèle d'email** pour la notification de déclaration de ticket. Elle déclare le module compatible **Dolibarr 24** et corrige les deux motifs pour lesquels le contrôle de paquet du Dolistore refusait le zip.

**Cette version demande Saturne 23.2.1 ou supérieur.**

> **Reprise de données à la réactivation du module.** Les unités de travail encore numérotées `WU` sont renommées `UT`, et leurs documents `WUD` en `UTD` : les références en base **et** les dossiers médias correspondants sur disque. La reprise ne touche qu'aux références qui n'ont pas déjà leur jumelle `UT`, ne réécrit pas le contenu des dossiers, et ne s'exécute qu'une fois. Elle reste une modification de données clientes : **sauvegarder la base et `documents/` avant de réactiver le module**. Sur une base auditée de taille moyenne, elle porte sur 131 éléments et 487 documents.

## Nouvelles fonctionnalités et innovations

### Listing des risques

* Les **accidents** apparaissent dans le listing des risques.
* Les **registres santé et sécurité** y figurent également.

### Plan d'action

* Les cartes du kanban portent la **cotation** et le **pictogramme du type de risque**.

### Tickets

* Un **modèle d'email** est disponible pour la notification de déclaration.

## Améliorations & corrections

### Registres et unités de travail

* Le groupement et l'unité de travail d'un registre ne pouvaient plus être modifiés.
* Des unités de travail étaient encore numérotées `WU` au lieu de `UT`.
* L'état des registres s'affichait « N/A » dans les documents générés.

### Standards

* Le rapport d'évolution retombe sur le standard actif quand aucun identifiant n'est passé, comme ses deux pages sœurs. Sans cela la page répondait bien, mais ne rendait que son en-tête.

### Compatibilité

* Le module déclare **Dolibarr 23 au minimum et 24 au maximum**.

### Conformité du paquet Dolistore

* Le contrôle de paquet refusait le zip : quatre points d'entrée — les deux manifestes de l'application web et les deux onglets produit — chargeaient l'environnement par un `require` unique, alors que la règle demande **au moins deux tentatives**, une pour le module à la racine de Dolibarr, une pour le module dans `custom`.
* Second motif : les classes et libs d'un module custom étaient incluses par un chemin `/custom` en dur, qui ne résout pas si les modules sont installés à la racine. Elles passent par `dol_include_once`, sauf le template du modal photo, qui garde son `dol_buildpath` — `dol_include_once` inclut dans son propre scope et le viderait sans erreur.

### Qualité et intégration continue

* La chaîne qualité ne se déclenchait sur **aucune** branche de maintenance : une pull request visant `23.0` ne lançait rien. Elles sont couvertes par un motif.
* PHPStan ne scanne plus les classes bouchons des tests de Saturne, qui masquaient les vraies signatures en CI et y laissaient passer des erreurs invisibles en local.
* La baseline PHPStan a été élaguée des entrées héritées de ces bouchons : 1703 → 1394.

## Comparaison des versions [23.4.0](https://github.com/Evarisk/Digirisk/compare/23.4.0...23.5.0) et 23.5.0

* [#5269] [CI] fix: élagage de la baseline PHPStan des entrées héritées des stubs [`931f711a`](https://github.com/Evarisk/Digirisk/commit/931f711a)
* [#5267] [Module] fix: inclure classes et libs custom par dol_include_once [`db38fb25`](https://github.com/Evarisk/Digirisk/commit/db38fb25)
* [#5265] [CI] fix: PHPStan ne scanne plus les stubs de test de Saturne [`3f30ee7f`](https://github.com/Evarisk/Digirisk/commit/3f30ee7f)
* [#5235] [Ticket] feat: modèle d'email pour la notification de déclaration [`0320cb4a`](https://github.com/Evarisk/Digirisk/commit/0320cb4a)
* [#5262] [Module] fix: bootstrap main.inc.php à deux tentatives, exigé par le Dolistore [`a8cf4638`](https://github.com/Evarisk/Digirisk/commit/a8cf4638)
* [#5235] [ListingRisks] feat: accidents dans le listing des risques [`786b4380`](https://github.com/Evarisk/Digirisk/commit/786b4380)
* [#5235] [ListingRisks] feat: registres santé sécurité dans le listing des risques [`ad06a494`](https://github.com/Evarisk/Digirisk/commit/ad06a494)
* [#5235] [ActionPlan] feat: cotation et picto du type de risque sur les cartes du kanban [`55a9f410`](https://github.com/Evarisk/Digirisk/commit/55a9f410)
* [#5235] [Ticket] fix: GP/UT impossible à modifier sur un registre [`0f455729`](https://github.com/Evarisk/Digirisk/commit/0f455729)
* [#5235] [DigiriskElement] fix: unités de travail encore numérotées WU au lieu de UT [`e53f7349`](https://github.com/Evarisk/Digirisk/commit/e53f7349)
* [#5255] [Module] rework: bornes de version Dolibarr 23 minimum, 24 maximum [`49b28ad9`](https://github.com/Evarisk/Digirisk/commit/49b28ad9)
* [#5235] [Document] fix: état des registres affiché « N/A » dans les documents [`3f50efcb`](https://github.com/Evarisk/Digirisk/commit/3f50efcb)
* [#5251] [CI] fix: déclencher les contrôles sur les branches de maintenance [`ea077688`](https://github.com/Evarisk/Digirisk/commit/ea077688)
* [#5249] [Standard] fix: le rapport d'évolution retombe sur le standard actif [`c03df802`](https://github.com/Evarisk/Digirisk/commit/c03df802)
