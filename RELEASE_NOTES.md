# [Digirisk] [23.2.0] - Vigilance météo - Terrain sur mobile - PAPRIPACT par année

Description : Cette version sort du bureau. Les plans de prévention et les permis de feu se créent et se signent depuis un téléphone, une application web progressive les regroupe, et un nouveau module de vigilance météo alerte l'établissement en cas d'épisode orange ou rouge. Côté pilotage, le PAPRIPACT s'organise par année avec report des actions en retard, et la carte ticket gagne un véritable fil de conversation.

## Nouvelles fonctionnalités et innovations

### Vigilance météo

* Nouveau **suivi de la vigilance Météo-France** : lecture du flux DPVigilance, résolution du département de l'établissement et mise en cache avec durée de vie réglable.
* **Carte de vigilance sur le tableau de bord** : panneau coloré, pastille par phénomène, et bouton de rafraîchissement manuel.
* **Bandeau d'alerte orange ou rouge** affiché en haut des pages via un hook, pour que personne ne passe à côté.
* Page de configuration dédiée — clé d'API, département, durée du cache — et activation par un simple interrupteur.

<!-- 📸 Ajouter une screenshot ici -->

### Le terrain sur mobile

* **Création et mise à jour d'un plan de prévention depuis un téléphone**, avec adresse de l'entreprise extérieure, champs obligatoires du responsable, et **signature de l'entreprise extérieure directement sur l'appareil**.
* **Même parcours pour le permis de feu**, aligné sur celui du plan de prévention.
* **Application web progressive** listant et créant les deux objets, avec un écran de réussite offrant le lien de diffusion et son QR code.
* Les droits utilisés sont ceux des objets eux-mêmes (`preventionplan/write`, `firepermit/write`) plutôt que des droits dédiés, et l'en-tête affiche le logo carré, le nom de la société et un avatar cliquable.

<!-- 📸 Ajouter une screenshot ici -->

### PAPRIPACT par année

* **Onglets « année en cours » et « années précédentes »** sur le plan d'action.
* **Report des actions en retard** sur l'année en cours, en option.
* **Barre de progression globale** au-dessus du Kanban, et **exports CSV et A3** depuis la vue.
* **Filtres par GP/UT, niveau de risque et tags**, et **colonnes du Kanban pilotées par un dictionnaire** plutôt que codées en dur.
* Chargement des cartes **colonne par colonne** et réglages d'affichage exposés dans la configuration.

![Le PAPRIPACT par annee, avec ses filtres, sa barre de progression et son Kanban](https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/assets/release-23.2.0/.shots/23.2.0-papripact-annees.png)

### Tickets

* **Fil de conversation sur la carte** : notes et messages publics, envoi d'email, pièces jointes, édition, suppression, citation et mentions `@`.
* **Boîte de fichiers joints** en colonne gauche, fondée sur le widget natif de Dolibarr — génération, liste, aperçu et suppression.
* **Édition en ligne du tiers et du projet** directement dans le bandeau, et sélecteur d'assigné avec recherche sur la carte Kanban.
* **Toute modification faite depuis la carte est journalisée** comme événement d'agenda.
* **Statistiques de pilotage** avec graphes cliquables, catégories de tickets affichées dans les documents, et entrée dédiée dans le menu Digirisk.

### Risques

* **Carte de lecture complète du risque**, et **tableau de bord des risques** sur la page des risques professionnels.
* **Compteurs de cotation dans le bandeau**, description de l'élément éditable en ligne, et élément parent affiché comme lien.
* **Refonte du front de la modale des risques psychosociaux**, et préremplissage de la description activé d'office.
* **Archivage des risques et des GP/UT** plutôt que suppression.

![Le tableau de bord des risques](https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/assets/release-23.2.0/.shots/23.2.0-tableau-bord-risques.png)

### Arborescence GP/UT

* **Refonte du panneau de navigation** : glisser-déposer, renommage en ligne, ajout et suppression rapides.
* **Éditeur enrichi sur la description** des GP/UT.
* Un élément introuvable renvoie désormais vers l'arborescence au lieu d'une erreur.

### Documents

* **Listing des risques en PDF** avec mini document unique, et **fiche « tous les risques »** regroupant les risques propres, hérités et partagés.
* **Fiches des risques hérités et partagés** sur la fiche d'un GP/UT.
* **Rapport de temps passé par utilisateur et par période** en PDF.
* Le modèle du PAPRIPACT se choisit **depuis la configuration des documents** au lieu d'être figé.
* Le rapport d'audit liste les **GP/UT modifiés et l'évolution des risques**.

### Administration et recherche

* **Masquer les entrées de menu objet par objet** depuis la configuration.
* **Images de tutoriel** sur les pages de réglages, conseils sur l'API REST et les modules d'export/import.
* Les objets Digirisk apparaissent dans la **recherche globale** de Dolibarr.

## Améliorations & corrections

### Performance

* Suppression des requêtes N+1 sur le Kanban du plan d'action, et regroupement des requêtes de contacts de tâches.
* Chargement des cartes du Kanban à la demande, colonne par colonne.

### Robustesse et PHP 8

* Plus d'erreur fatale sur la liste des modules quand Saturne est absent.
* Identifiants convertis avec `GETPOSTINT` sur les plans de prévention et les permis de feu, pour éviter les `TypeError` de `fetch()` sur PHP 8.
* Correction des erreurs de type à la création de ticket public, au chargement d'un objet sans identifiant, et des avertissements PHP 8 à la création de tâche.
* Vrais messages d'erreur lors de la génération de l'archive ZIP du document unique.

### Interface

* Jauge d'avancement de la configuration de nouveau visible lorsque l'affichage des erreurs est actif.
* Styles de la modale d'ajout de risque sortis du gabarit et passés en SCSS.
* La carte laisse Saturne traiter le cas de l'enregistrement introuvable, au lieu de dupliquer la garde.

### Socle et compatibilité

* La compatibilité annoncée est resserrée sur **Dolibarr 23**.
* Le module s'appuie sur **Saturne 23.1.0**.
* Les dépendances communes — ECM, Agenda, FCKeditor, Catégories — sont désormais déclarées par Saturne.

## Comparaison des versions [23.1.0](https://github.com/Evarisk/Digirisk/compare/23.1.0...23.2.0) et 23.2.0
