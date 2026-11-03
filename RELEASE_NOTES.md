# [Digirisk] [23.7.0] - Registre des tickets et accidents en Excel - Synthèse publique des risques

Description : Le registre des tickets et les accidents s'exportent en **classeur Excel prêt à analyser** : synthèses par registre, par unité de travail et par mois, taux de fréquence et de gravité. Une **synthèse publique des risques** se partage par un simple lien sécurisé. Les tickets reçoivent leurs **actions correctives et préventives** et une **analyse QQOQCCP**. Les **risques psychosociaux** tiennent en une ligne par unité de travail. Le document unique existe aussi **en PDF**, avec son **mode d'emploi** et une **présentation pour le CSE**.

**Cette version demande Saturne 23.7.0 ou supérieur.** Sur un Saturne plus ancien, Digirisk refuse désormais de s'activer, avec un message clair, au lieu de provoquer une erreur.

## 🎬 La nouveauté en vidéo

<a href="https://github.com/Evarisk/Digirisk/releases/download/23.7.0/digirisk-23.7.0.mp4"><img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/video_poster_23.7.0.jpeg" alt="Registres et accidents dans Excel, synthèse publique des risques, en vidéo (58 s)"></a>

*Cliquez sur l'image pour voir la vidéo (58 s).*

## Nouvelles fonctionnalités et innovations

### Exports Excel

* **Registre des tickets en Excel** : sur la liste des registres, le bouton **« Export Excel »** télécharge tous les tickets de la liste, avec ses filtres et son tri. Le classeur comporte un sommaire avec les chiffres clés, une ligne par ticket, ses actions et son analyse QQOQCCP. Il présente aussi des synthèses par registre, par groupement ou unité de travail, par mois et par responsable, ainsi qu'un tableau croisé **GP/UT × registre** coloré selon le nombre de tickets.
* **Accidents en Excel** : sur la liste des accidents, le même bouton produit un classeur avec les accidents, les arrêts de travail, les lésions, les enquêtes et leurs actions. On y trouve aussi des synthèses par lieu, par mois et par victime, un tableau **siège × nature** des lésions et les indicateurs : indice de fréquence, taux de fréquence et taux de gravité.
* **Uniquement les lignes choisies** : sur la liste des accidents, cochez des lignes puis choisissez l'action **« Export Excel de la sélection »**.
* Avec Saturne 23.7.0, les listes des plans de prévention, des permis de feu et des enquêtes accident s'exportent aussi en Excel, tout comme les tableaux de bord de Digirisk (général, accidents, tickets et onglet d'informations d'un GP/UT).

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/export-registre.png" alt="Liste des registres et son bouton Export Excel dans la barre de filtres">
<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/classeur-registre.png" alt="Onglet « GP-UT x registre » du classeur : nombre de tickets par unité de travail et par registre">
<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/export-accidents-selection.png" alt="Quatre accidents cochés et l’action de masse « Export Excel de la sélection »">

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/classeur-accidents.png" alt="Sommaire du classeur des accidents : chiffres clés, indice de fréquence, taux de fréquence et de gravité">

### Synthèse publique des risques

* **Nouvelle page publique**, ouverte par un lien sécurisé, avec QR code et **sans compte Dolibarr**. Pour chaque groupement et unité de travail, elle donne le nombre de risques de chaque catégorie de danger, sur la couleur du plus grave. Elle convient à un affichage en salle de pause ou au partage avec le CSE.
* **Quatre onglets**, qu'on change sans recharger la page :
  * **Risques** ;
  * **Pénibilité** ;
  * **Risques psychosociaux** : niveau de chaque facteur et moyenne, sur 4 ;
  * **Registres** : nombre de tickets de chaque registre. Seuls les tickets ouverts sont affichés par défaut, et des filtres permettent de choisir le statut et la période.
* **Au survol d'un nombre**, les risques les plus graves qu'il compte s'affichent : description, cotation, photo et commentaire. Un clic ouvre la liste des risques de l'élément, filtrée sur la catégorie (il faut alors un compte Dolibarr).
* Les groupements se replient. Un bouton d'**export Excel** télécharge un onglet par tableau, aux couleurs de la page. La page reste lisible sur un téléphone.
* L'en-tête présente la société de l'entité : logo, SIRET et, en option, le responsable à prévenir avec son téléphone.
* Tout se règle dans la configuration du document unique :
  * la publication, désactivée par défaut ;
  * un nouveau lien, qui coupe les anciens ;
  * un **code PIN** facultatif, de 4 à 8 chiffres ;
  * l'affichage de chaque onglet et du bouton d'export.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/synthese-config.png" alt="Réglages de la synthèse publique des risques dans la configuration du document unique">
<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/synthese-publique.png" alt="Synthèse publique des risques : nombre de risques par catégorie et par unité, infobulle des risques les plus graves">

### Tickets

* **Actions correctives et préventives** : le nouvel onglet « Correctives N / Préventives N » est présent sur la fiche ticket de Digirisk comme sur la fiche native.
  * Une action reçoit un responsable, une date de début, une échéance, un budget et des tags.
  * Un clic sur un champ suffit pour le modifier. La case de clôture et le curseur d'avancement restent disponibles, et le diagramme de Gantt s'affiche en vue détaillée.
  * Chaque création ou modification est tracée sur le ticket. Le ticket doit être rattaché à un projet.
* **Bilan des actions sur la fiche** : sous le statut du ticket, une pastille par type indique « terminées / total ». Elle est grise sans action, orange tant qu'une action reste ouverte et verte quand tout est soldé.
* **Analyse QQOQCCP** : un onglet Quoi, Qui, Où, Quand, Comment, Combien et Pourquoi, toujours modifiable. Un document PDF dédié se génère depuis l'onglet et rejoint les fichiers joints du ticket. Chaque modification est tracée dans l'agenda.
* **Kanban des tickets**, arrivé en 23.6.0 :
  * chaque carte montre l'avancement, le temps écoulé et la date du dernier message ;
  * une barre de filtres combinables et mémorisés trie par affectation, avancement, ancienneté, dernier message, numéro, période de création et GP/UT (un groupement inclut ses unités).
* **Fichiers du ticket dans les réponses** : en message public, on peut joindre les fichiers déjà présents dans le ticket : photos de la déclaration, documents générés ou pièces des messages précédents. Ils partent en pièces jointes ou, au choix, en **liens de téléchargement**. Cela fonctionne sur la fiche Digirisk comme sur la fiche native.
* **Documents Digirisk sur la fiche ticket** : ils s'affichent dans un bloc sous les fichiers joints. Le sélecteur de projet affiche le titre du projet.
* **Code PIN de déclaration** : un code de 4 à 12 chiffres, réglé dans la configuration des tickets, est demandé avant d'afficher le formulaire public. Il permet de réserver la déclaration aux personnes à qui vous l'avez communiqué.
* **Danger grave et imminent** : un modèle d'e-mail « Signalement d'un danger grave et imminent » est livré et proposé par défaut sur la catégorie correspondante. Les e-mails de catégorie reprennent les variables de l'e-mail de déclaration, dont la nouvelle `__TICKET_TAGS__` (les registres du ticket).
* **Tableau de bord des tickets** : il ajoute les tickets ouverts affectés et non affectés, ainsi que les actions correctives et préventives en cours, soldées ou en retard.
* Un ticket déclaré depuis l'interface publique prend pour objet le début de son message. Les anciens tickets sans objet sont complétés à la mise à jour.
* Dans le menu de Digirisk, « Ticket » devient **« Registre »**, avec « Kanban registres » et « Liste des registres ».

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/ticket-actions.png" alt="Actions correctives et préventives d’un ticket, avec leur avancement">
<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/ticket-qqoqccp.png" alt="Analyse QQOQCCP d’un ticket">
<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/kanban-tickets.png" alt="Kanban des tickets : barre de filtres, avancement et ancienneté sur chaque carte">

### Risques psychosociaux

Ces changements font suite à la grille RPS-DU de l'INRS, arrivée en 23.6.0.

* **Une ligne par unité de travail** : les méthodes « Faire le point » et grille RPS-DU ne créent plus un risque par facteur ou par critère, ce qui faisait 26 lignes pour une seule grille. Elles enregistrent désormais une seule évaluation psychosociale par unité de travail.
* Dans la liste des risques d'une unité, la ligne RPS devient un **bandeau** : facteurs, score, date et actions non réalisées. Déplié, il montre le détail par facteur. Il permet aussi d'ajouter une action de prévention sur un facteur, sans refaire l'évaluation.
* **Onglet « Risques psychosociaux » de la société** : il réunit toutes les unités de travail dans un tableau, rangées par groupement. Chaque unité y est à qualifier, concernée, ou non concernée avec son motif.
  * Des onglets avec compteurs et une recherche facilitent le tri.
  * Plusieurs unités se qualifient d'un coup.
  * Chaque changement peut s'annuler depuis sa notification.
* **Compte rendu joint à l'évaluation** : photos et fichiers, par exemple une grille remplie ou un compte rendu de réunion.
* Une unité déjà évaluée critère par critère garde ses risques. Le bouton **« Consolider en une ligne »** les remplace par une seule évaluation et range les originaux dans les archives de l'unité.
* Le document unique (ODT) gagne un tableau **« Cotation des RPS par UT/GP »** : niveau de chaque facteur F1 à F6 et moyenne, en pastilles du vert au rouge foncé.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/rps-societe.png" alt="Onglet Risques psychosociaux : une qualification par unité de travail, rangées par groupement">
<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/rps-unite.png" alt="Bandeau des risques psychosociaux d’une unité de travail : facteurs, score et actions de prévention">

### Risques

* **Ajout d'un risque guidé** : la fenêtre d'ajout affiche un fil d'étapes (Danger, Évaluation, puis Catégories et Tâche, facultatives). Le danger se choisit parmi des tuiles, avec une recherche.
* **Réévaluer après une tâche terminée** : quand vous cochez une tâche ou passez son avancement à 100 %, Digirisk propose « Tâche terminée : réévaluer le risque ? ». Le bouton ouvre directement la nouvelle évaluation.
* **Les listes des risques d'un groupement ou d'une unité adoptent le design de DigiBoard** :
  * une colonne **Protection** montre les pictogrammes EPI et EPC des signalisations de l'élément, avec le détail au survol ;
  * la catégorie de danger s'affiche sous la description ;
  * le suivi des tâches indique le nombre de tâches terminées et les retards.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/risque-ajout.png" alt="Fenêtre d’ajout d’un risque : fil d’étapes et tuiles des dangers filtrées par la recherche « chute »">
<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/risque-reevaluer.png" alt="Toutes les tâches du risque sont terminées : Digirisk propose de le réévaluer">

### Document unique

* **Document unique en PDF** (modèle « DU-A4-PORTRAIT ») : les résultats de l'évaluation, sans passer par LibreOffice. Il couvre l'établissement, l'organisation, les tableaux de bord, les risques hiérarchisés avec le programme de prévention et les registres.
* **Mode d'emploi en PDF** (« DU-MODE-EMPLOI-A4 ») : cadre réglementaire, notions, méthodes de cotation, familles de danger, lecture des tableaux et programme de prévention.
* **Présentation au CSE** (« CSE-DIAPOSITIVES-16-9 ») : des diapositives prêtes à projeter, avec la synthèse, un zoom par famille de danger, les unités les plus exposées, les actions en retard et les prochaines échéances.
* Ces trois documents sortent dans la langue choisie à la génération.
* **À savoir** : le gabarit ODT livré ne contient plus que les données. La partie explicative (réglementation, définitions, méthodes, explication des tableaux, notes de bas de page) passe dans le mode d'emploi. Un gabarit ODT personnalisé n'est pas modifié.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/document-unique-pdf.png" alt="Documents générés : document unique, mode d’emploi et présentation CSE en PDF">
<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/presentation-cse.png" alt="Présentation au CSE : diapositive « Synthèse de l’évaluation »">

### Listing des risques

* Ces changements complètent le listing des risques de la 23.6.0. La couverture du mini document unique ajoute une **« Synthèse du périmètre »** : risques par niveau, actions en cours, en retard et terminées, registres et accidents.
* Registres, accidents et lexique s'enchaînent sans page presque vide, ce qui donne un document plus court.
* La colonne Photo n'apparaît que si une évaluation du périmètre porte une photo.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/listing-synthese.png" alt="Couverture du listing des risques d’un groupement, avec la « Synthèse du périmètre »">

### Accidents et enquêtes

* Les actions d'une enquête, dotées du Gantt et du curseur d'avancement en 23.6.0, partagent désormais le composant des tickets :
  * date de début, budget et tags ;
  * modification sur place ;
  * vue condensée par défaut et vue détaillée au choix, mémorisée.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/enquete-actions.png" alt="Actions d’une enquête accident en vue condensée">

### Plan d'actions (PAPRIPACT)

* **Droits dédiés** : « Consulter le PAPRIPACT » et « Modifier les actions correctives depuis le PAPRIPACT ». Un responsable qui suit les actions sans lire le document unique accède au PAPRIPACT par sa propre entrée de menu. Sans le droit de modification, le kanban est en lecture seule.
* PDF PAPRIPACT A3 : la colonne d'avancement porte un code couleur, et les cellules colorées prennent la hauteur de leur ligne.
* Les réglages des tâches (tâches terminées, dates, avancement calculé, masquages dans les documents) passent de l'onglet Document unique à l'onglet **Plan d'actions** de la configuration.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/papripact-menu.png" width="320" alt="Menu d’un responsable qui n’a que le droit « Consulter le PAPRIPACT »">

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/papripact-lecture.png" alt="Kanban du PAPRIPACT en lecture seule">

### Organisation

* **Part de l'effectif par GP/UT** : chaque groupement ou unité de travail indique le pourcentage de personnes de l'entreprise qui y sont présentes. La fiche affiche cette part et le nombre de personnes correspondant. Le calcul repose sur l'effectif réglé dans la configuration des GP/UT ou, à défaut, sur les salariés actifs. Ce champ est aussi disponible dans l'API.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/unite-effectif.png" alt="Champ « Personnes présentes » d’une unité de travail, en pourcentage de l’effectif">

### Médias et e-mails

* La **bibliothèque de médias** a son entrée dans le menu de gauche de Digirisk. Avec Saturne 23.7.0, on y dépose des fichiers par glisser-déposer.
* Dans l'aperçu des modèles d'e-mail de Saturne, les réglages d'e-mail de Digirisk (plan de prévention, permis de feu, notification de déclaration de ticket) apparaissent par leur nom, avec un lien vers leur page de configuration.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.7.0/menu-registre.png" width="220" alt="Menu de Digirisk : rubrique Registre et bibliothèque de médias">

## Améliorations & corrections

### Tickets et déclaration publique

* Les réponses envoyées depuis la fiche ticket partaient sans leurs pièces jointes et sans la copie (Cc) : elles partent maintenant complètes.
* Les fichiers joints à un message restent rattachés à ce message après rechargement de la page.
* Interface publique fermée : un envoi direct créait encore un ticket. Ce n'est plus possible.
* L'adresse IP du déclarant est conservée sur le ticket. Le nombre de déclarations par adresse IP est plafonné (200 par défaut).
* Téléphone : les numéros de tous les pays sont acceptés, et plus seulement le format français. E-mail : les adresses avec un « + » ou une extension longue (.paris, .digital) sont acceptées.
* Après une déclaration refusée, le GP/UT choisi reste sélectionné.
* Une erreur d'envoi des notifications n'est plus montrée au déclarant : elle est consignée dans l'agenda du ticket.
* `__TICKET_DECLARATION_DATE__` donne aussi l'heure du signalement quand elle a été saisie.
* Fiche ticket :
  * les flèches précédent / suivant fonctionnent de nouveau ;
  * l'onglet « Ticket » ramène à la fiche Digirisk sous Dolibarr 23 ;
  * le crayon de modification du GP/UT est revenu sur la fiche native ;
  * l'affichage du GP/UT ne provoque plus d'erreur fatale.
* Liste des tickets : les catégories et le GP/UT affichés sont désormais protégés contre l'injection de code.
* PDF du ticket : le logo et la photo restent dans leur cadre. Document ODT du ticket : un avertissement PHP a été supprimé.

### Accidents

* L'onglet Actions d'une enquête ne peut plus modifier que les actions de cette enquête.
* Indicateurs des accidents : le calcul des heures travaillées remplaçait l'utilisateur connecté par le dernier salarié lu, avec ses droits, pour la suite de la page. C'est corrigé.

### Risques

* La fiche d'un risque dont la dernière évaluation n'a pas de cotation s'affiche de nouveau.
* Grille de la méthode avancée : les cases choisies redeviennent visibles quand un autre module modifie le style des fenêtres.
* Risques des produits : la date des protections est bien enregistrée.

### Documents

* Les documents ODT générés dans une autre langue que celle de l'utilisateur affichaient des codes techniques : ils sont traduits.
* Listing des risques : le pied de page est homogène, et les statuts des registres s'affichent dans la langue du document.

### Organisation et projets

* La fiche de la société ne plante plus à l'affichage des responsables de la prévention.
* Les indicateurs du plan d'actions (temps, coût, tâches, avancement, budget) ne s'ajoutent plus sous tous les projets, seulement sous ceux de Digirisk.

### Interface

* Le thème moderne de la 23.6.0 repose sur le thème partagé de Saturne, et s'applique aussi aux pages des utilisateurs, à la configuration Social et aux onglets produit.
* Ajustements visuels :
  * coins des tableaux arrondis ;
  * jauge d'avancement de la configuration entière et centrée ;
  * barre de titre des listes qui passe à la ligne sur petit écran ;
  * graphiques du tableau de bord contenus dans la largeur de la page.
* Menu Registre : les sous-entrées affichent leur nouveau libellé et leur icône.

### Installation, sécurité et compatibilité

* Digirisk vérifie la version de Saturne avant de s'activer.
* Saturne est retrouvé quand Digirisk est installé par lien symbolique.
* Avec un Saturne antérieur à 23.3.0, les pages qui lisent le registre des objets, y compris celles d'autres modules, tombaient en erreur depuis la 23.6.0 : c'est corrigé.
* Chaque page demandait une feuille de style inexistante, ce qui produisait une erreur 404 : cette demande est supprimée. Les fichiers de style et de script sont versionnés, le navigateur charge donc la nouvelle version après une mise à jour.
* Création rapide d'utilisateur : l'avertissement PHP a disparu, et le message de confirmation n'affiche plus de mot de passe.

### Qualité et intégration continue

* L'intégration continue refuse les inclusions relatives de Saturne, et PHPStan analyse la bibliothèque Excel du cœur.

## Testez les nouveautés maintenant

**Allez tester l'export Excel du registre des tickets et des accidents sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.6.0](https://github.com/Evarisk/Digirisk/compare/23.6.0...23.7.0) et 23.7.0

* [#5676] [CI] ci: actions GitHub en Node 24 (checkout@v7, cache@v6) [`21a84f01`](https://github.com/Evarisk/Digirisk/commit/21a84f01)
* [#5673] [CI] ci: contrôle « API Saturne publiée » dans la CI qualité [`7fb12161`](https://github.com/Evarisk/Digirisk/commit/7fb12161)
* [#5668] [DigiAI] fix: modal container rule scoped to the DigiAI modal [`bd944aa1`](https://github.com/Evarisk/Digirisk/commit/bd944aa1)
* [#5669] [CI] ci: retrait du workflow Generate Auto Changelog [`781d530e`](https://github.com/Evarisk/Digirisk/commit/781d530e)
* [#5666] [Mod] feat: version minimale de Saturne, activation refusee sur un Saturne trop ancien [`751c51a8`](https://github.com/Evarisk/Digirisk/commit/751c51a8)
* [#5394] [RiskAssessment] fix: selected cells of the advanced grid hidden by an ancestor background [`d09fe595`](https://github.com/Evarisk/Digirisk/commit/d09fe595)
* [#5664] [Product] fix: date des protections d'un risque produit enregistrée dans la bonne table [`bee52159`](https://github.com/Evarisk/Digirisk/commit/bee52159)
* [#5644] [Risk] feat: proposer de réévaluer le risque quand une de ses tâches est terminée [`981bf905`](https://github.com/Evarisk/Digirisk/commit/981bf905)
* [#5644] [Ticket] fix: logo et photo du PDF du ticket ajustés à leur cadre [`1b6419e5`](https://github.com/Evarisk/Digirisk/commit/1b6419e5)
* [#5644] [Ticket] feat: filtres du kanban des tickets sur le numéro, la date de création et le GP/UT [`fe7838bc`](https://github.com/Evarisk/Digirisk/commit/fe7838bc)
* [#5644] [RiskOverview] feat: interrupteur de l'export Excel de l'interface publique [`69bdf32b`](https://github.com/Evarisk/Digirisk/commit/69bdf32b)
* [#5658] [Registre] fix: chemins du registre publies tels qu'installes, lisibles par Saturne < 23.3.0 [`ad05a33b`](https://github.com/Evarisk/Digirisk/commit/ad05a33b)
* [#5654] [Export] fix: lignes cochées lues par GETPOST, visibles de PHPStan [`494d6114`](https://github.com/Evarisk/Digirisk/commit/494d6114)
* [#5654] [Export] feat: export Excel des accidents cochés par l'action de masse [`22bcbf44`](https://github.com/Evarisk/Digirisk/commit/22bcbf44)
* [#5514] [Projet] fix: indicateurs du plan d'action affiches seulement sur les projets Digirisk [`12751624`](https://github.com/Evarisk/Digirisk/commit/12751624)
* [#5652] [Risk] fix: fiche d'un risque dont la dernière évaluation n'a pas de cotation [`36fb003c`](https://github.com/Evarisk/Digirisk/commit/36fb003c)
* [#5650] [Ticket] fix: charger DigiriskElement avant d'afficher le GP/UT de la fiche ticket [`63733413`](https://github.com/Evarisk/Digirisk/commit/63733413)
* [#5648] [SCSS] fix: wrap list title bar on small screens [`6ac1cc73`](https://github.com/Evarisk/Digirisk/commit/6ac1cc73)
* [#5646] [Risk] style: required asterisk of the trail steps in red [`8e3d7a7e`](https://github.com/Evarisk/Digirisk/commit/8e3d7a7e)
* [#5646] [Risk] feat: steps trail in the risk add modal, hazard picked from tiles with a search [`dec689e8`](https://github.com/Evarisk/Digirisk/commit/dec689e8)
* [#5546] [Papripact] feat: dashboard view of the action plan [`32b90c0e`](https://github.com/Evarisk/Digirisk/commit/32b90c0e)
* [#5642] [Menu] fix: register sub-entries labels and icons of left menu sub-entries [`17b6446d`](https://github.com/Evarisk/Digirisk/commit/17b6446d)
* [#5636] [RiskOverview] fix: société de l'entité du lien dans l'en-tête, avec logo, SIRET et responsable en option [`c682f812`](https://github.com/Evarisk/Digirisk/commit/c682f812)
* [#5639] [Menu] rework: rename left menu ticket entry to register [`7a6df572`](https://github.com/Evarisk/Digirisk/commit/7a6df572)
* [#5637] [RPS] feat: qualification block of the psychosocial risks moved to the level 0 [`5c90dd8f`](https://github.com/Evarisk/Digirisk/commit/5c90dd8f)
* [#5634] [Export] fix: un seul bouton Export Excel sur la liste des accidents [`aa9f61fd`](https://github.com/Evarisk/Digirisk/commit/aa9f61fd)
* [#5603] [Export] fix: l'export des accidents répond avant les modèles de liste de Saturne [`51523693`](https://github.com/Evarisk/Digirisk/commit/51523693)
* [#5630] [Email] feat: declare the Digirisk email template settings to the Saturne overview [`50b46727`](https://github.com/Evarisk/Digirisk/commit/50b46727)
* [#5628] [Ticket] fix: heure du signalement dans __TICKET_DECLARATION_DATE__ [`f354cffd`](https://github.com/Evarisk/Digirisk/commit/f354cffd)
* [#5626] [Ticket] fix: keep the posted GP/UT when a refused public declaration shows the form again [`1bfed08a`](https://github.com/Evarisk/Digirisk/commit/1bfed08a)
* [#5623] [Ticket] fix: keep the IP of a public declaration and cap the declarations per IP [`c84ad6d0`](https://github.com/Evarisk/Digirisk/commit/c84ad6d0)
* [#5622] [Ticket] fix: accept the phone numbers of every country on the public interface [`b0b47f88`](https://github.com/Evarisk/Digirisk/commit/b0b47f88)
* [#5615] [DU] fix: finitions des documents PDF du DU et du listing des risques [`2f10f0c1`](https://github.com/Evarisk/Digirisk/commit/2f10f0c1)
* [#5619] [Module] fix: init() removes the dead MAIN_MODULE_DIGIRISKDOLIBARR_CSS constant, patch_preview_icon.css no longer requested [`ffc8be93`](https://github.com/Evarisk/Digirisk/commit/ffc8be93)
* [#5618] [Accident] fix: getWorkedHours() ne remplace plus l'utilisateur connecté [`18587d63`](https://github.com/Evarisk/Digirisk/commit/18587d63)
* [#5610] [Ticket] fix: keep the notification errors of a public declaration off the declarant's page [`e0911af5`](https://github.com/Evarisk/Digirisk/commit/e0911af5)
* [#5609] [Ticket] fix: accept valid email addresses with a "+" or a long extension [`57521bfb`](https://github.com/Evarisk/Digirisk/commit/57521bfb)
* [#5603] [Export] feat: export Excel multi-onglets du registre des tickets et des accidents [`e7350f6e`](https://github.com/Evarisk/Digirisk/commit/e7350f6e)
* [#5282] [Ticket] feat: modèle de mail livré pour les dangers graves et imminents [`8782b88c`](https://github.com/Evarisk/Digirisk/commit/8782b88c)
* [#5611] [Config] rework: move the task settings from the risk assessment document tab to the action plan tab [`fef77066`](https://github.com/Evarisk/Digirisk/commit/fef77066)
* [#5607] [Groupement/UT] fix: fiche sans id (vue Société), plus de fatale sur les responsables de la prévention [`1231397c`](https://github.com/Evarisk/Digirisk/commit/1231397c)
* [#5604] [Ticket] fix: échapper les catégories et le GP/UT injectés dans la liste des tickets [`112d3e76`](https://github.com/Evarisk/Digirisk/commit/112d3e76)
* [#5288] [ListingRisks] feat: synthèse du périmètre sur le mini DU, sections courtes enchaînées [`412473e4`](https://github.com/Evarisk/Digirisk/commit/412473e4)
* [#5599] [RiskOverview] feat: closed tickets hidden by default, filters by status and period on the register tab [`4cc3934c`](https://github.com/Evarisk/Digirisk/commit/4cc3934c)
* [#5596] [Document] fix: les documents ODT dans la langue du document [`0db76f66`](https://github.com/Evarisk/Digirisk/commit/0db76f66)
* [#5598] [Ticket] fix: warning « Undefined array key contacts » à la génération du TicketDocument ODT [`990d9750`](https://github.com/Evarisk/Digirisk/commit/990d9750)
* [#5593] [Ticket] feat: box des documents Digirisk (TicketDocument) sous les fichiers joints de la fiche ticket [`14d3d48c`](https://github.com/Evarisk/Digirisk/commit/14d3d48c)
* [#5590] [Ticket] feat: onglet Actions — tags, date du jour, statut validé, édition en place, vue condensée [`285786f2`](https://github.com/Evarisk/Digirisk/commit/285786f2)
* [#5591] [Ticket] fix: PHPDoc @return lisible par PHPStan [`96e4c641`](https://github.com/Evarisk/Digirisk/commit/96e4c641)
* [#5288] [DU] fix: PHPStan des modèles PDF du document unique [`110c55e1`](https://github.com/Evarisk/Digirisk/commit/110c55e1)
* [#5591] [Ticket] feat: QQOQCCP toujours éditable, tracé dans l'agenda, documents dans les fichiers joints du ticket [`8034085f`](https://github.com/Evarisk/Digirisk/commit/8034085f)
* [#5588] [Ticket] fix: give a subject to tickets declared from the public interface [`78f615a6`](https://github.com/Evarisk/Digirisk/commit/78f615a6)
* [#5288] [DU] fix: DU en PDF, mode d'emploi et présentation CSE dans la langue du document [`6d05eb8b`](https://github.com/Evarisk/Digirisk/commit/6d05eb8b)
* [#5587] [RiskOverview] feat: PIN code, tab switches and register tab on the public overview [`535f232b`](https://github.com/Evarisk/Digirisk/commit/535f232b)
* [#4650] [Ticket] style: rendre visible le sélecteur des fichiers du ticket [`ba922a95`](https://github.com/Evarisk/Digirisk/commit/ba922a95)
* [#4650] [Ticket] feat: envoyer les fichiers du ticket en liens de téléchargement [`789aeb8d`](https://github.com/Evarisk/Digirisk/commit/789aeb8d)
* [DU] feat: DU en PDF, mode d'emploi et présentation CSE, gabarit ODT réduit aux données [`5fb5e12c`](https://github.com/Evarisk/Digirisk/commit/5fb5e12c)
* [#5581] [Ticket] feat: proposer les fichiers du ticket en pièces jointes sur la fiche ticket native [`494c8368`](https://github.com/Evarisk/Digirisk/commit/494c8368)
* [#5582] [Ticket] fix: GP/UT edit pencil on the native ticket card [`f93b19d9`](https://github.com/Evarisk/Digirisk/commit/f93b19d9)
* [#5579] [Assets] fix: version every asset url printed by hand through saturne_asset_full_url [`4bf29cd3`](https://github.com/Evarisk/Digirisk/commit/4bf29cd3)
* [#5549] [Ticket] feat: axe « Combien ? » de l'analyse QQOQCCP du ticket et de son document [`35b6cc7c`](https://github.com/Evarisk/Digirisk/commit/35b6cc7c)
* [#5549] [Ticket] feat: filtres du kanban des tickets sur l'affectation, l'avancement, l'ancienneté et le dernier message [`16f4c43a`](https://github.com/Evarisk/Digirisk/commit/16f4c43a)
* [#5549] [Actions] feat: date de début et budget des actions correctives, préventives et curatives [`74d7c83a`](https://github.com/Evarisk/Digirisk/commit/74d7c83a)
* [#5576] [RiskOverview] feat: public overview readable on a phone [`2ddbab55`](https://github.com/Evarisk/Digirisk/commit/2ddbab55)
* [#5549] [TicketDashboard] feat: tickets ouverts affectés / non affectés et actions correctives et préventives [`1209c29f`](https://github.com/Evarisk/Digirisk/commit/1209c29f)
* [#5549] [Ticket] feat: onglet QQOQCP du ticket et son document PDF [`8b28ad2d`](https://github.com/Evarisk/Digirisk/commit/8b28ad2d)
* [#5549] [Ticket] feat: code PIN demandé par l'interface publique de déclaration [`0d0d4142`](https://github.com/Evarisk/Digirisk/commit/0d0d4142)
* [#5569] [Ticket] fix: the timezone of the browser is stored on a closed public interface too [`0c0d6510`](https://github.com/Evarisk/Digirisk/commit/0c0d6510)
* [#5569] [Ticket] fix: a closed public interface processes no action, a direct POST still created the ticket [`75224a89`](https://github.com/Evarisk/Digirisk/commit/75224a89)
* [#5568] [Ticket] fix: rattacher au message les fichiers uploadés depuis le composer [`3493debb`](https://github.com/Evarisk/Digirisk/commit/3493debb)
* [#4650] [Ticket] feat: proposer les fichiers du ticket en pièces jointes du mail [`eb60a208`](https://github.com/Evarisk/Digirisk/commit/eb60a208)
* [#5564] [RiskList] feat: DigiBoard simplified design on the risk lists of a groupment or a work unit [`fa95f857`](https://github.com/Evarisk/Digirisk/commit/fa95f857)
* [#5564] [RiskSign] feat: protections of elements read in one pass [`caa175e5`](https://github.com/Evarisk/Digirisk/commit/caa175e5)
* [#5562] [Dashboard] fix: keep the graph grid within its parent width [`c949d20f`](https://github.com/Evarisk/Digirisk/commit/c949d20f)
* [#5560] [RiskOverview] feat: risks of a number and psychosocial factors on hover in the public overview [`b1e1709f`](https://github.com/Evarisk/Digirisk/commit/b1e1709f)
* [#5558] [Menu] feat: add a left menu entry to the media library [`dc45ef71`](https://github.com/Evarisk/Digirisk/commit/dc45ef71)
* [#5523] [CI] fix: autoloaders of PhpSpreadsheet and Psr in the core sparse checkout of PHPStan [`899e51b7`](https://github.com/Evarisk/Digirisk/commit/899e51b7)
* [#5523] [CI] fix: PHPStan resolves PhpSpreadsheet for the Excel export of the risk overview [`ebdc80bd`](https://github.com/Evarisk/Digirisk/commit/ebdc80bd)
* [#5439] [RPS] fix: lag of the assessment modal, first click on Evaluate lost while the page loads [`977f95d2`](https://github.com/Evarisk/Digirisk/commit/977f95d2)
* [#4857] [RPS] feat: report (photos and files) attached to the psychosocial assessment [`c9080bf4`](https://github.com/Evarisk/Digirisk/commit/c9080bf4)
* [#5439] [RPS] style: aligned action add row, a picked answer no longer pushes its row [`dfe8b8e5`](https://github.com/Evarisk/Digirisk/commit/dfe8b8e5)
* [#5439] [RPS] fix: psychosocial levels back to their green to dark red scale [`d69b62dd`](https://github.com/Evarisk/Digirisk/commit/d69b62dd)
* [#5549] [AccidentInvestigation] fix: the actions tab only closes or moves the actions of its investigation [`eced038b`](https://github.com/Evarisk/Digirisk/commit/eced038b)
* [#5549] [Ticket] feat: actions correctives et préventives du ticket, avec leur bilan dans le bandeau [`e1125415`](https://github.com/Evarisk/Digirisk/commit/e1125415)
* [#5555] [Ticket] fix: previous / next arrows of the Digirisk ticket card, and its "Ticket" tab under Dolibarr 23 [`789c6906`](https://github.com/Evarisk/Digirisk/commit/789c6906)
* [#5549] [Ticket] feat: avancement, temps écoulé et dernier message sur les cartes du kanban, titre du projet sur la fiche [`e6e9a5a0`](https://github.com/Evarisk/Digirisk/commit/e6e9a5a0)
* [#5541] [DigiriskElement] feat: percentage of the company workforce present in each GP/UT [`a47f8983`](https://github.com/Evarisk/Digirisk/commit/a47f8983)
* [#5439] [RPS] feat: compact table for the psychosocial qualification of the work units [`f4213254`](https://github.com/Evarisk/Digirisk/commit/f4213254)
* [#5550] [Module] fix: drop the patch_preview_icon.css declaration, the file was never committed [`a4ee3c7b`](https://github.com/Evarisk/Digirisk/commit/a4ee3c7b)
* [#5547] [CI] ci: refuser les inclusions relatives de Saturne [`3be0ff5d`](https://github.com/Evarisk/Digirisk/commit/3be0ff5d)
* [#5439] [Document] feat: psychosocial cotation by GP/UT in the risk assessment document [`67a12373`](https://github.com/Evarisk/Digirisk/commit/67a12373)
* [#5544] [User] fix: plus de warning $newpassword à la création rapide d'utilisateur [`bded5ebe`](https://github.com/Evarisk/Digirisk/commit/bded5ebe)
* [#5542] [Papripact] feat: color code on the progress column and colored cells sized to their row [`3ff36a19`](https://github.com/Evarisk/Digirisk/commit/3ff36a19)
* [#5539] [Theme] fix: jauge d'avancement entière et centrée [`055e79ff`](https://github.com/Evarisk/Digirisk/commit/055e79ff)
* [#5439] [RPS] fix: a click outside the psychosocial assessment closes it [`a57430b1`](https://github.com/Evarisk/Digirisk/commit/a57430b1)
* [#5523] [RiskOverview] fix: a number of the public risk overview opens the risks it counts [`a26ac016`](https://github.com/Evarisk/Digirisk/commit/a26ac016)
* [#5523] [RiskOverview] feat: auto-saved switch for the public risk overview setting [`d037b93d`](https://github.com/Evarisk/Digirisk/commit/d037b93d)
* [#5523] [RiskOverview] feat: numbers of the public risk overview open the risk list of the element [`a90ee4f6`](https://github.com/Evarisk/Digirisk/commit/a90ee4f6)
* [#5523] [RiskOverview] style: one decimal on every psychosocial value of the public risk overview [`a8948be4`](https://github.com/Evarisk/Digirisk/commit/a8948be4)
* [#5523] [RiskOverview] fix: white icon on the export button of the public risk overview [`db684333`](https://github.com/Evarisk/Digirisk/commit/db684333)
* [#5523] [RiskOverview] feat: Excel export and folding of the groupments on the public risk overview [`9ddbe1c3`](https://github.com/Evarisk/Digirisk/commit/9ddbe1c3)
* [#5523] [RiskOverview] feat: switch the tabs of the public risk overview without reloading [`22dc9515`](https://github.com/Evarisk/Digirisk/commit/22dc9515)
* [#5523] [RiskOverview] style: lay the public risk overview out as the other public interfaces [`97123ae5`](https://github.com/Evarisk/Digirisk/commit/97123ae5)
* [#5523] [RiskOverview] fix: show NC in every empty cell of the public risk overview [`8cfe4aed`](https://github.com/Evarisk/Digirisk/commit/8cfe4aed)
* [#5523] [RiskOverview] feat: public overview of the risks of the groupments and work units [`c52d2cfa`](https://github.com/Evarisk/Digirisk/commit/c52d2cfa)
* [#5439] [RPS] style: align the prevention action fields of the psychosocial assessment [`f3a10733`](https://github.com/Evarisk/Digirisk/commit/f3a10733)
* [#5439] [RPS] style: align the psychosocial risk block with the Digirisk theme [`bc607bf6`](https://github.com/Evarisk/Digirisk/commit/bc607bf6)
* feat: consolidate psychosocial risks into one risk per work unit [`ce8550b9`](https://github.com/Evarisk/Digirisk/commit/ce8550b9)
* [#5502] [Theme] fix: square corners over the rounded tables [`e584ffd0`](https://github.com/Evarisk/Digirisk/commit/e584ffd0)
* [#5447] [Header] feat: thème partagé sur les pages en llxHeader [`23f507a6`](https://github.com/Evarisk/Digirisk/commit/23f507a6)
* [#5447] [SCSS] rework: thème générique repris du thème partagé de Saturne [`28daac34`](https://github.com/Evarisk/Digirisk/commit/28daac34)
* [#4829] [ActionPlan] feat: droits dédiés pour consulter et modifier le PAPRIPACT [`a70a6705`](https://github.com/Evarisk/Digirisk/commit/a70a6705)
* [#5391] [Saturne] fix: inclure Saturne par dol_include_once / dol_buildpath [`344f07f8`](https://github.com/Evarisk/Digirisk/commit/344f07f8)

