# [Digirisk] [23.2.2] - Pages de configuration réparées

Description : Version corrective. Elle rétablit l'accès aux sept pages de configuration du module, inaccessibles depuis la 23.2.0, et supprime les avertissements PHP 8 restants à la génération du document unique.

**Cette version demande Saturne 23.1.1 ou supérieur.**

## Améliorations & corrections

### Configuration

* Les sept pages de configuration — configuration générale, événements, tickets, plan d'action, vigilance météo, plan de prévention et kanban des tickets — sont de nouveau accessibles. Elles appelaient deux fonctions d'aide qui n'avaient jamais été ajoutées à Saturne, et se terminaient toutes par une erreur fatale.
* Éteindre un réglage écrit désormais un `0` au lieu de supprimer la constante. Une constante supprimée était recréée à sa valeur par défaut à la prochaine activation ou mise à jour du module : le réglage se rallumait tout seul, sans que personne n'y touche.

### Document unique

* Correction des avertissements PHP 8 à la génération du document unique.

## Comparaison des versions [23.2.1](https://github.com/Evarisk/Digirisk/compare/23.2.1...23.2.2) et 23.2.2
