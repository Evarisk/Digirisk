# [Digirisk] [23.1.0] - Nouvelle carte ticket & Kanban du plan d'action

Description : Cette version apporte deux gros chantiers d'interface — une nouvelle carte ticket entièrement éditable en ligne (style « tap-to-edit ») avec fil de discussion, et un Kanban / Gantt pour le plan d'action PAPRIPACT — ainsi qu'une refonte de la page d'organisation et un suivi d'activité (ActionComm) sur les tâches.

## Nouvelles fonctionnalités et innovations

### Nouvelle carte ticket

* Carte ticket repensée avec édition en ligne « tap-to-edit » : sujet, sévérité, statut, GP/UT, projet, tags… modifiables directement sans recharger la page.
* Fil de discussion des messages intégré (réponse en ligne, citation, édition/suppression, envoi par mail, Ctrl+Entrée pour envoyer) avec éditeur WYSIWYG Dolibarr (CKEditor).
* Section fichiers compacte avec aperçu en miniatures et lightbox, suppression intégrée.
* Personnalisation du layout par utilisateur : densité (compact / cozy / spacious), largeurs des blocs, masquer/déplacer/redimensionner les sections, le tout persisté.
* Picker Kanban à 4 onglets et drawer de création rapide d'un ticket depuis le Kanban.

<!-- 📸 Ajouter une screenshot ici -->

### Kanban & Gantt du plan d'action (PAPRIPACT)

* Vues Kanban et Gantt du plan d'action du Document Unique, le Kanban devenant la vue par défaut.
* Cartes enrichies : responsables et contributeurs (avatars/initiales), tags avec gestion en ligne, dates de début/fin éditables, charge et budget, badge de cotation du risque avec tooltip détaillé.
* Édition en ligne (libellé, progression avec slider, dates) et drag & drop entre colonnes.
* Page d'administration dédiée pour régler la largeur/l'espacement des colonnes et activer les options de journalisation.

<!-- 📸 Ajouter une screenshot ici -->

### Refonte de la page d'organisation

* Hiérarchie GP/UT modernisée : enregistrement automatique, suppression en ligne via dialogue, badges de risques et boutons d'ajout rapide.

<!-- 📸 Ajouter une screenshot ici -->

---

## Améliorations & corrections

### Suivi d'activité (ActionComm)

* Journalisation ActionComm sur les modifications de tâches du plan d'action, avec 9 réglages activables et journalisation des erreurs via `dol_syslog`.
* Audit ActionComm sur les tickets, gestion des statuts 7/9 et limite du nombre de tickets fermés affichés.

### Tickets

* Affichage du ref + label pour les GP/UT, projet (`fk_project`) dans la section Identification, badge d'historique client.
* Corrections : dates affichées dans le fuseau de l'utilisateur, valeurs d'extrafields masquées à cause d'un préfixe de clé, entités HTML rendues littéralement dans les réponses AJAX et les libellés de listes déroulantes.
* `printFieldListValue` ne provoque plus de fatale lorsque le service est vide (#4729).

### ODT

* Suppression des tirets de profondeur en trop dans le libellé d'élément lors des générations ODT (#4763).

### Documentation

* Adaptation des instructions IA Saturne et de la philosophie d'architecture (#4733).

## Comparaison des versions [23.0.0](https://github.com/Evarisk/Digirisk/compare/23.0.0...23.1.0) et 23.1.0

* [#4443] [Ticket] feat: nouvelle carte ticket éditable en ligne, fil de discussion et personnalisation du layout [`#4755`](https://github.com/Evarisk/Digirisk/pull/4755) [`#4756`](https://github.com/Evarisk/Digirisk/pull/4756) [`#4760`](https://github.com/Evarisk/Digirisk/pull/4760)
* [#4738] [ActionPlan] feat: Kanban & Gantt du plan d'action PAPRIPACT
* [#4744] [ActionPlan] feat: ActionComm sur les tâches + page admin de configuration
* [#4753] [Ticket] feat: Kanban ticket (picker, audit, limite tickets fermés, page admin) [`#4758`](https://github.com/Evarisk/Digirisk/pull/4758) [`#4759`](https://github.com/Evarisk/Digirisk/pull/4759)
* [#4735] [Organization] rework: refonte de la hiérarchie GP/UT (auto-save, suppression en ligne, badges risques, ajout rapide)
* [#4763] [ODT] fix: remove extra depth hyphens in element label [`#4764`](https://github.com/Evarisk/Digirisk/pull/4764)
* [#4733] [Docs] feat: adapt Saturne AI instructions and architecture philosophy
* [#4729] [Ticket] fix: prevent fatal in printFieldListValue when service is empty [`#4730`](https://github.com/Evarisk/Digirisk/pull/4730)
