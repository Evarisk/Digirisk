# [Digirisk] [23.6.0] - Rondes du permis de feu - Grille RPS-DU de l'INRS - Thème moderne

Description : Cette version ajoute au permis de feu la **fin des travaux et les rondes du surveillant de sécurité**, avec un document **PDF natif**, une seconde méthode d'évaluation des risques psychosociaux, la **grille RPS-DU de l'INRS**, et des **trames de plan de prévention** dans l'application mobile. Le back-office et les pages publiques passent sur un **thème moderne**, et la fiche d'enquête accident dessine le **schéma ITAMAMI**.

**Cette version demande Saturne 23.6.0 ou supérieur.** Si DigiQuali ou DoliCar sont installés : **DigiQuali 23.2.1** et **DoliCar 23.1.2** au minimum.

## Nouvelles fonctionnalités et innovations

### Permis de feu : fin des travaux et rondes

* Après des travaux par point chaud, le **surveillant de sécurité** repasse sur la zone (INRS ED 6030). La **fin des travaux** se déclare sur le permis verrouillé, avec le nom du surveillant, et planifie les **rondes**.
* Chaque ronde s'enregistre une fois son heure venue : **commentaire, photos, position et signature**, chacun désactivé, facultatif ou obligatoire selon la configuration.
* Le surveillant fait ses rondes **sans compte Dolibarr**, depuis une page publique ouverte par un lien sécurisé et renouvelable. Les rondes apparaissent aussi sur la fiche du permis et dans l'application mobile.
* Le **nombre de rondes** et leur **intervalle** se règlent en configuration, la première pouvant avoir lieu dès la fin des travaux. Par défaut : 3 rondes, une par heure, la première à la fin des travaux.
* Un permis ne s'archive pas tant qu'une ronde reste à faire. Une fois la surveillance terminée, le bouton **« Clôturer le permis »** de l'application mobile le clôture.
* Le document du permis de feu est un **PDF natif**, comme celui du plan de prévention : il ne demande plus LibreOffice sur le serveur. Il présente un bloc par type de travaux (pictogramme, description, matériel, protections, photos) et reprend les risques du plan de prévention rattaché.

### Plan de prévention

* **Trames** : dans l'application mobile, un plan s'enregistre comme trame (motif, tags, risques, protections, certifications, horaires) pour pré-remplir le suivant. L'entreprise extérieure, les dates, les signatures et les photos n'en font pas partie.
* La configuration règle le **texte de chaque partie du PDF** (moyens généraux, consignes générales, premiers secours, numéros d'urgence) et lui joint des **images**, imprimées à la fin de la partie. Le permis de feu reprend les mêmes textes.
* Un **modèle d'email « Demande de signature du plan de prévention »** est livré, et le modèle choisi en configuration est désormais utilisé.
* Les tags **« 400h »** et **« Danger grave et imminent »** sont créés par défaut.
* Dans les listes des plans et des permis, un réglage fait trouver à la **recherche par référence** le numéro exact : « 1 » donne PP1, et non plus PP11 ou PP21. Désactivé par défaut.
* Les listes des plans de prévention et des permis de feu prennent la présentation commune aux autres listes du module.

### Application mobile

* Bandeau **« Installer l'application »** sur l'accueil, avec une aide pas à pas sur iPhone et Firefox.
* **Cartes des listes plus complètes** : ville et responsable de l'entreprise extérieure, période à venir, en cours ou terminée, état de la signature, nombre d'intervenants signés, pictogrammes des risques ou des travaux, date de création et auteur.
* **Recherche par entreprise extérieure et par date**, mot par mot : « Dupont 09/2026 ».
* **Archiver un plan de prévention** depuis sa fiche, une fois verrouillé.
* Un **bandeau de confirmation** reste affiché après l'enregistrement d'un plan.
* Les risques, protections et certifications déjà ajoutés sont **grisés** dans les sélecteurs.
* **Infobulles d'aide** sur les cases EU / EE des risques et sur la carte « Tags » (anciennement « Catégories »).

### Risques

* **Grille RPS-DU de l'INRS** (ED 6403) : seconde méthode d'évaluation des risques psychosociaux, 26 critères en 7 familles cotés en 4 couleurs, en complément de l'outil « Faire le point ».
* Les modales de risque utilisent le **bloc média**, ouvert sur la **bibliothèque de médias** ; un média affecté à un risque depuis la bibliothèque est rangé dans sa dernière évaluation.
* Le sélecteur de risque d'une tâche affiche la **catégorie de danger et l'élément parent**.

### Listing des risques

* **Arborescence du périmètre** et **lexique** en tête du document.
* **Grille des critères de la méthode avancée** et **roue des neuf principes de prévention**.
* **Photo de l'élément en pleine page**, en option : un plan ou une carte du site, par exemple.
* Les **masquages du plan d'actions** (référence, responsable, date, budget) se choisissent à la génération, pré-cochés selon la configuration.

### Accidents et enquêtes

* La fiche d'enquête dessine le **schéma ITAMAMI** : l'individu, la tâche, le matériel et le milieu autour de l'activité réelle, jusqu'à l'accident.
* Ses champs se répartissent sur **deux colonnes** : l'identification à gauche, les constats à droite.
* Les actions de l'enquête ont un **diagramme de Gantt**, un **curseur d'avancement** et leur bouton d'ajout dans l'en-tête de leur section.
* La liste des accidents prend la présentation commune aux autres listes du module.

### Tickets

* Fiche ticket : **message initial modifiable sur place**, boutons **« Nouvelle tâche »** et **« Nouvel accident »** (l'accident est pré-rempli avec le ticket et son GP/UT), panneau de droite réordonné.
* **Kanban des tickets** dans le menu de gauche de Digirisk, avec un **filtre par arborescence de tags**.
* Fiche du ticket public plus large sur ordinateur.
* Le **QR code du ticket** des documents est dessiné à la génération, aux couleurs Digirisk.

### Tableau de bord

* Widget des **3 derniers risques ajoutés** : pictogramme, photo, cotation.
* Les **indicateurs de risque** d'un groupement ou d'une unité de travail figurent dans son onglet d'informations.

### Médias

* **Bloc média** sur les fiches des groupements et unités de travail, des accidents, des enquêtes et sur le ticket public (photo et note vocale).
* Les photos de l'application mobile, des rondes et des tickets rejoignent la **bibliothèque de médias**, y compris celles prises avant la mise à jour.

### Interface

* **Thème moderne** du back-office et des pages publiques : cartes arrondies, pastilles teintées, palette de l'application mobile.
* La feuille de style Digirisk s'applique aux écrans qui ne la chargeaient pas : utilisateurs, configuration Social, onglets de la fiche produit, horaires des tiers.
* Les **informations de la société** se modifient directement depuis les pages Affichage légal et Partage d'informations.
* **Création rapide d'utilisateur** sous la liste des utilisateurs Digirisk, et depuis la modale d'ajout d'un évaluateur.
* Les pages du document unique et du rapport d'audit n'affichent plus la liste des événements.

### API REST

* Création, lecture, modification et suppression des **risques et de leurs évaluations**, envoi de photos, routes des groupements et unités de travail hors corbeille, envoi de plusieurs médias et transfert de cotation.
* `GET /risks/creation-options` renvoie le nom et le logo de l'entité.

## Améliorations & corrections

### Risques

* Les compteurs de la liste suivent ses filtres ; le tri par cotation ne retire plus les risques non évalués ; le tri et la pagination gardent le type de risque.
* Risques hérités et partagés : les risques supprimés ou archivés sortent des listes, et un risque partagé sans évaluation validée ne met plus la page en erreur.
* Les risques d'un groupement mis à la corbeille ne restent plus dans les listes.
* Le niveau 0 de gravité et d'exposition, enregistré vide sous PHP 7, est rendu aux évaluations concernées.
* L'échéance d'une tâche saisie sans heure est enregistrée ; chaque modale d'ajout de tâche a son propre sélecteur de responsable.

### Plan de prévention et permis de feu

* Le PDF du plan de prévention imprime le commentaire du risque, dans son cadre, et les commentaires des protections.
* La signature de l'entreprise utilisatrice manquait parfois au document alors qu'elle était annoncée signée.
* Le brouillon lit et range les photos des risques dans le dossier du risque.
* La recherche par référence dans la liste des plans renvoyait une liste vide.
* Le document du permis de feu reprenait les lignes de tous les permis et les risques de tous les plans de prévention.
* Les projets du permis de feu et des tickets n'avaient pas leur tag sur une nouvelle installation : les tags sont créés et rattachés.

### Application mobile

* Les actions exigent le jeton anti-CSRF ; le bouton « Verrouiller » est traduit.
* Les heures s'affichent dans le fuseau de l'utilisateur.
* Un tag ou un tiers créé depuis le formulaire se retrouve dans la liste au retour.
* Chrome ne propose plus d'enregistrer l'adresse de l'entreprise extérieure comme la sienne.
* Bouton « Diffusion » en double sur la fiche retiré ; nom du risque dans la confirmation de suppression ; curseur placé dans la description après un ajout ; accents du bandeau de renvoi du mail.
* Ajustements visuels : astérisques des champs obligatoires, loupe de la recherche, pictogramme de l'entreprise extérieure, fenêtre d'installation en portrait sur téléphone.

### Accidents

* Les jours sans accident se comptent depuis le dernier accident, et non plus depuis le dernier saisi.
* Le tableau de bord ne plante plus sur un arrêt de travail sans date de fin.
* Une enquête enregistrée sans accident ne provoque plus d'erreur ; l'onglet lésions non plus.
* La liste et le registre des accidents restent dans la largeur de la page.

### Listing des risques

* Colonnes des risques, des actions, des accidents et des registres revues, en-tête « Date de déclaration » complet, graphique en secteurs réparé.

### Tickets

* La première phrase n'est plus doublée dans le mail de réponse.

### Interface

* Le thème Digirisk s'applique aux onglets que d'autres modules ajoutent sur les objets Digirisk.
* Graphiques du tableau de bord deux par ligne, texte blanc sur les cotations noires, infobulles lisibles, fenêtre d'aperçu de document redimensionnable, boutons de titre centrés.
* Signalétique : le lien mène à l'onglet de l'élément où elle a été créée.
* Organisation : un groupement se déplie depuis toute la colonne de la flèche.
* Le menu « App Mobile » est placé juste sous « Tableau de bord ».

### API

* Les routes des accidents renvoyaient toujours 0 ou N/A (jours sans accident, nombre d'accidents, jours d'arrêt, taux).
* `GET risk/getRisksByCotation` partait en erreur fatale.

### Installation, sécurité et multi-entité

* Sur une nouvelle instance, les dépendances sont activées avant l'initialisation : les tables de Saturne existent.
* Les liens et appels AJAX portent le jeton anti-CSRF.
* La signature réutilisable de l'application mobile est gérée par Saturne et partagée entre entités.
* Le module ne suppose plus d'être installé dans `custom/`.
* Avertissements PHP 8 retirés des écrans (liste des risques, évaluateurs, multi-entité, outils d'import et d'export).

### Qualité et intégration continue

* Scrutinizer est retiré ; PHPStan ne scanne de Saturne que les dossiers qui portent ses classes.

## Testez les nouveautés maintenant

**Allez tester les rondes du permis de feu sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.5.0](https://github.com/Evarisk/Digirisk/compare/23.5.0...23.6.0) et 23.6.0

* [#5533] [List] fix: column titles wrap and filter bar joined to the table [`6834a44e`](https://github.com/Evarisk/Digirisk/commit/6834a44e)
* [#5533] [Accident] rework: accident list on the Saturne generic list [`936ae4e2`](https://github.com/Evarisk/Digirisk/commit/936ae4e2)
* [#5530] [Ticket] feat: ticket card feedback - add buttons, right panel order, tabs kept in Digirisk, initial message edited on the spot [`fca18686`](https://github.com/Evarisk/Digirisk/commit/fca18686)
* [#5528] [AccidentInvestigation] feat: gantt of the actions, bars from start or creation to deadline [`9fe79e91`](https://github.com/Evarisk/Digirisk/commit/9fe79e91)
* [#5526] [AccidentInvestigation] feat: rounded progress slider of the actions, saved on release [`e2aa191c`](https://github.com/Evarisk/Digirisk/commit/e2aa191c)
* [#5524] [AccidentInvestigation] rework: add button of the actions in the header of their section [`bc1aa4ef`](https://github.com/Evarisk/Digirisk/commit/bc1aa4ef)
* [#5521] [Projet] fix: create FP and TS project tags on fresh installs and backfill them [`7aefb0ff`](https://github.com/Evarisk/Digirisk/commit/7aefb0ff)
* [#5517] [Module] fix: activate dependencies before init so Saturne tables exist on a fresh instance [`da37cc8b`](https://github.com/Evarisk/Digirisk/commit/da37cc8b)
* [#5518] [Module] fix: read MULTICOMPANY_EXTERNAL_MODULES_SHARING without warning [`d60c3dfb`](https://github.com/Evarisk/Digirisk/commit/d60c3dfb)
* [#5515] [AccidentInvestigation] fix: pass the accident id as a string to showOutputField [`c9386a50`](https://github.com/Evarisk/Digirisk/commit/c9386a50)
* [#5515] [AccidentInvestigation] rework: show the card fields on two columns [`fbb9360f`](https://github.com/Evarisk/Digirisk/commit/fbb9360f)
* [#5515] [AccidentInvestigation] feat: draw the ITAMAMI schema on the investigation card [`92fd0fbe`](https://github.com/Evarisk/Digirisk/commit/92fd0fbe)
* [#5512] [User] rework: quick user creation form under the Digirisk user list [`a5196699`](https://github.com/Evarisk/Digirisk/commit/a5196699)
* [#5510] [Ticket] add: tag tree select2 filter on ticket kanban [`223053ca`](https://github.com/Evarisk/Digirisk/commit/223053ca)
* [#5508] [Menu] add: ticket kanban entry under Ticket in the Digirisk left menu [`31c5ae73`](https://github.com/Evarisk/Digirisk/commit/31c5ae73)
* [#5506] [Ticket] feat: wider public ticket card on desktop [`4cdb983a`](https://github.com/Evarisk/Digirisk/commit/4cdb983a)
* [#5504] [Product] fix: remove the UTF-8 BOM that sent output before the headers [`76fd2805`](https://github.com/Evarisk/Digirisk/commit/76fd2805)
* [#5504] [Accident] fix: no TypeError when an investigation is saved without accident [`6c2cee1b`](https://github.com/Evarisk/Digirisk/commit/6c2cee1b)
* [#5500] [Accident] fix: widen description column in accident list [`c48d63ad`](https://github.com/Evarisk/Digirisk/commit/c48d63ad)
* [#5498] [Task] fix: save the deadline typed without a time [`b13acb64`](https://github.com/Evarisk/Digirisk/commit/b13acb64)
* [#5495] [Document] feat: draw the ticket QR code of the documents at generation, in the Digirisk colours [`7df79854`](https://github.com/Evarisk/Digirisk/commit/7df79854)
* [#5494] [Theme] fix: load the Digirisk theme on the tabs other modules add on the Digirisk objects [`969b6e96`](https://github.com/Evarisk/Digirisk/commit/969b6e96)
* [#5492] [Agenda] fix: plain Events title on the standard and element agenda pages [`a5f23c29`](https://github.com/Evarisk/Digirisk/commit/a5f23c29)
* [#5490] [SCSS] fix: center the title buttons and keep them still on hover [`8b0ce6ac`](https://github.com/Evarisk/Digirisk/commit/8b0ce6ac)
* [#5488] [DigiriskStandard] remove: events list on the risk assessment and audit report document pages [`e547aa09`](https://github.com/Evarisk/Digirisk/commit/e547aa09)
* [#5486] [SCSS] feat: keep the company data notice link inline, without accent border [`7dde4082`](https://github.com/Evarisk/Digirisk/commit/7dde4082)
* [#5486] [CompanyData] feat: edit the company data on the fly from the legal display and information sharing pages [`26e09729`](https://github.com/Evarisk/Digirisk/commit/26e09729)
* [#5484] [SCSS] fix: let the document preview dialog be resized [`19cd6282`](https://github.com/Evarisk/Digirisk/commit/19cd6282)
* [#5482] [DigiriskElement] fix: the card block takes the full width [`73855f06`](https://github.com/Evarisk/Digirisk/commit/73855f06)
* [#5480] [Dashboard] feat: risk indicators of a GP/UT in its information tab [`1771ed4d`](https://github.com/Evarisk/Digirisk/commit/1771ed4d)
* [#5478] [Accident] fix: keep the accident list and register tabs within the page width [`06f03f52`](https://github.com/Evarisk/Digirisk/commit/06f03f52)
* [#5476] [RiskSign] fix: link to the risk signs tab of the element it was created on [`a5489de6`](https://github.com/Evarisk/Digirisk/commit/a5489de6)
* [#5474] [SCSS] fix: readable page size select of the second list [`0426e91e`](https://github.com/Evarisk/Digirisk/commit/0426e91e)
* [#5474] [SCSS] fix: centre the icon of the square buttons [`08f97fe5`](https://github.com/Evarisk/Digirisk/commit/08f97fe5)
* [#5474] [RiskSign] fix: modals hug their content, pictogram grid over the modal [`a32fedc6`](https://github.com/Evarisk/Digirisk/commit/a32fedc6)
* [#5472] [SCSS] fix: readable light tooltips in the modern theme [`78b9c3b9`](https://github.com/Evarisk/Digirisk/commit/78b9c3b9)
* [#5472] [Evaluator] fix: add modal layout and user creation from it [`dc50b914`](https://github.com/Evarisk/Digirisk/commit/dc50b914)
* [#5470] [SCSS] fix: keep the room of the task date icon and budget currency [`556e4c31`](https://github.com/Evarisk/Digirisk/commit/556e4c31)
* [#5470] [Risk] fix: one responsible select id per task add modal [`797bccc6`](https://github.com/Evarisk/Digirisk/commit/797bccc6)
* [#5468] [Organization] fix: toggle a groupment from the whole arrow column [`9a634a31`](https://github.com/Evarisk/Digirisk/commit/9a634a31)
* [#5466] [Theme] fix: show dashboard graphs two per row [`a7efef1c`](https://github.com/Evarisk/Digirisk/commit/a7efef1c)
* [#5464] [CI] ci: retirer la configuration Scrutinizer [`1fb03f60`](https://github.com/Evarisk/Digirisk/commit/1fb03f60)
* [#5462] [Object] rework: retirer le prefixe custom/ des entrees du registre d'objets [`e3c91721`](https://github.com/Evarisk/Digirisk/commit/e3c91721)
* [#5458] [Accident] fix: days without accident counted from the latest accident [`01d81941`](https://github.com/Evarisk/Digirisk/commit/01d81941)
* [#5457] [Accident] fix: dashboard TypeError on work stops without end date [`02c99ddb`](https://github.com/Evarisk/Digirisk/commit/02c99ddb)
* [#5455] [API] fix: accident routes always return 0 or N/A [`1e095bd8`](https://github.com/Evarisk/Digirisk/commit/1e095bd8)
* [#5453] [Dashboard] fix: undefined layout warning in graphs without User-Agent [`3a139010`](https://github.com/Evarisk/Digirisk/commit/3a139010)
* [#5451] [API] fix: undefined layout warning on getRisksByCotation without User-Agent [`20605263`](https://github.com/Evarisk/Digirisk/commit/20605263)
* [#5451] [API] fix: GET risk/getRisksByCotation fatal ArgumentCountError [`89b0278f`](https://github.com/Evarisk/Digirisk/commit/89b0278f)
* [#5449] [SCSS] style: couleurs Digirisk du bloc média et rangée photo + vocal du ticket public [`1f9298f6`](https://github.com/Evarisk/Digirisk/commit/1f9298f6)
* [#5449] [Media] feat: bloc média Saturne sur les fiches GP/UT, accident, enquête et le ticket public [`ba674147`](https://github.com/Evarisk/Digirisk/commit/ba674147)
* [#5445] [Risk] fix: le tri et la pagination des listes de risques gardent le type de risque [`35ec773c`](https://github.com/Evarisk/Digirisk/commit/35ec773c)
* [#5443] [Theme] fix: keep white text on black cotation cells in theme tables [`d59f4df7`](https://github.com/Evarisk/Digirisk/commit/d59f4df7)
* [#5340] [PreventionPlan] fix: draft card reads and stores risk photos in the risk folder [`a5888ce6`](https://github.com/Evarisk/Digirisk/commit/a5888ce6)
* [#5440] [API] feat: add entity_name and entity_logo to GET /risks/creation-options [`1e61d76d`](https://github.com/Evarisk/Digirisk/commit/1e61d76d)
* [#5437] [PreventionPlan] feat: create the default 400h and serious imminent danger tags [`3f29680c`](https://github.com/Evarisk/Digirisk/commit/3f29680c)
* [#5435] [Dashboard] fix: use assessment photo or nophoto, add date and RAxx prefix, omit category label [`7c07383b`](https://github.com/Evarisk/Digirisk/commit/7c07383b)
* [#5435] [Dashboard] feat: display category picto, photo, 3-line info and square cotation badge [`f2030066`](https://github.com/Evarisk/Digirisk/commit/f2030066)
* [#5435] [Dashboard] feat: add last 3 added risks widget on dashboard [`6be9a447`](https://github.com/Evarisk/Digirisk/commit/6be9a447)
* QUAL: #5433 Instantiate RiskAssessment directly in updateRiskAssessment for PHPStan [`b8ff1c3a`](https://github.com/Evarisk/Digirisk/commit/b8ff1c3a)
* NEW: #5433 [API] Add non-trashed DigiriskElement REST routes, multi-media upload & cotation transfer [`3769ce15`](https://github.com/Evarisk/Digirisk/commit/3769ce15)
* QUAL: #5431 Remove redundant null-coalesce on narrowed image type map [`312cbf74`](https://github.com/Evarisk/Digirisk/commit/312cbf74)
* NEW: #5431 Add CRUD REST API routes for risks and temp photos [`a86b6657`](https://github.com/Evarisk/Digirisk/commit/a86b6657)
* [#5428] [PreventionPlan] fix: label the risk comment and draw it inside the risk frame in the PDF [`a431b6a2`](https://github.com/Evarisk/Digirisk/commit/a431b6a2)
* [#5428] [PreventionPlan] fix: print the protection comments in the PDF [`dc790104`](https://github.com/Evarisk/Digirisk/commit/dc790104)
* [#5426] [Ticket] fix: cle ErrorRecordNotFound au lieu de RecordNotFound [`cc174650`](https://github.com/Evarisk/Digirisk/commit/cc174650)
* [#5424] [PHP] fix: fatale de l'onglet lésions et warnings PHP 8 affichés sur les écrans [`d186c89d`](https://github.com/Evarisk/Digirisk/commit/d186c89d)
* [#5424] [Front] feat: feuille de style Digirisk sur les écrans qui ne la chargeaient pas [`2774c5d5`](https://github.com/Evarisk/Digirisk/commit/2774c5d5)
* [#5424] [SCSS] feat: thème moderne du back-office et des pages publiques [`dee0b98b`](https://github.com/Evarisk/Digirisk/commit/dee0b98b)
* [#5422] [Medias] fix: photos des tickets dans la bibliothèque, exclusions transmises à Saturne et rattrapage après l'activation [`c65d1a61`](https://github.com/Evarisk/Digirisk/commit/c65d1a61)
* [#5416] [FirePermit] fix: retrait du texte d'introduction des rondes dans la configuration [`5d61cc3a`](https://github.com/Evarisk/Digirisk/commit/5d61cc3a)
* [#5416] [FirePermit] feat: nombre de rondes et intervalle configurables, première ronde possible à la fin des travaux [`655adbbe`](https://github.com/Evarisk/Digirisk/commit/655adbbe)
* [#5415] [FirePermit] feat: bouton « Clôturer le permis » dans la PWA une fois toutes les rondes effectuées [`5ae9f1bf`](https://github.com/Evarisk/Digirisk/commit/5ae9f1bf)
* [#5414] [PWA] fix: jeton anti-CSRF exigé sur toutes les actions, bouton Verrouiller traduit [`a66a3c35`](https://github.com/Evarisk/Digirisk/commit/a66a3c35)
* [#5417] [Medias] feat: photos des enregistrements dans la bibliothèque et rattrapage à l'activation [`f0e9bbc8`](https://github.com/Evarisk/Digirisk/commit/f0e9bbc8)
* [#5412] [Risk] fix: setValueFrom attend un id null, pas une chaine vide [`c3c3c09f`](https://github.com/Evarisk/Digirisk/commit/c3c3c09f)
* [#5412] [CI] chore: build assets [`41d566f9`](https://github.com/Evarisk/Digirisk/commit/41d566f9)
* [#5412] [Risk] feat: médias de la bibliothèque affectés à un risque rangés dans sa dernière évaluation [`3fbf3319`](https://github.com/Evarisk/Digirisk/commit/3fbf3319)
* [#5412] [Risk] feat: modales de risque sur le bloc média Saturne, ouvert sur la bibliothèque de médias [`5531ed3a`](https://github.com/Evarisk/Digirisk/commit/5531ed3a)
* [#5410] [Risk] fix: les risques supprimés et archivés sortent des listes héritée et partagée [`4782d71c`](https://github.com/Evarisk/Digirisk/commit/4782d71c)
* [#5408] [Risk] fix: fatale sur la page des risques avec un risque partagé sans évaluation validée [`6f6b1b66`](https://github.com/Evarisk/Digirisk/commit/6f6b1b66)
* [#5408] [Risk] fix: listes des risques hérités et partagés, warnings PHP 8 et risques non évalués perdus au tri par cotation [`ed018cbd`](https://github.com/Evarisk/Digirisk/commit/ed018cbd)
* [#5405] [Risk] fix: le tri par cotation ne retire plus les risques non évalués de la liste [`8e3bc905`](https://github.com/Evarisk/Digirisk/commit/8e3bc905)
* [#5403] [Risk] fix: warnings PHP 8 à l'affichage de la liste des risques [`ed6c4ca2`](https://github.com/Evarisk/Digirisk/commit/ed6c4ca2)
* [#5401] [Risk] fix: les compteurs de la liste des risques suivent les filtres de la liste [`33da6de9`](https://github.com/Evarisk/Digirisk/commit/33da6de9)
* [#5399] [Evaluator] fix: warnings PHP 8 sur l'onglet Évaluateurs [`95c04e2b`](https://github.com/Evarisk/Digirisk/commit/95c04e2b)
* [#5397] [DigiriskElement] fix: les risques d'un GP mis à la corbeille ne restent plus dans les listes [`98677a9c`](https://github.com/Evarisk/Digirisk/commit/98677a9c)
* [#5395] [CSRF] fix: ajouter le token CSRF aux liens et appels AJAX [`1c7d5b9f`](https://github.com/Evarisk/Digirisk/commit/1c7d5b9f)
* [#4193] [Task] feat: label risk selector with danger category and parent element [`4dfe2f65`](https://github.com/Evarisk/Digirisk/commit/4dfe2f65)
* [#5386] [PreventionPlan] feat: contenu et images de chaque partie du PDF dans la configuration du plan de prévention [`ab2151eb`](https://github.com/Evarisk/Digirisk/commit/ab2151eb)
* [#5384] [RiskAssessment] fix: niveau 0 de la gravité et de l'exposition rendu aux évaluations sans cotation [`454c7858`](https://github.com/Evarisk/Digirisk/commit/454c7858)
* [#5381] [Document] fix: put the date range checkbox before the dates [`308c1de1`](https://github.com/Evarisk/Digirisk/commit/308c1de1)
* [#5378] [CI] fix: declare export download variables for PHPStan [`cc646fff`](https://github.com/Evarisk/Digirisk/commit/cc646fff)
* [#5378] [Tools] fix: defer export download script to View section to avoid premature output before headers [`375111d2`](https://github.com/Evarisk/Digirisk/commit/375111d2)
* [#5378] [Tools] fix: undefined property and array keys during JSON global import [`feacbf6e`](https://github.com/Evarisk/Digirisk/commit/feacbf6e)
* [#5376] [DigiriskElement] fix: undefined array key in multidir_output on creation in multi-entity [`86c1e959`](https://github.com/Evarisk/Digirisk/commit/86c1e959)
* [#5372] [CI] chore: rerun PHPStan against Saturne develop with #1693 [`ebb2d6b0`](https://github.com/Evarisk/Digirisk/commit/ebb2d6b0)
* [#5309] [PWA] fix: tiers créé depuis le formulaire mobile ajouté à la liste au retour, comme les tags [`5f8c2380`](https://github.com/Evarisk/Digirisk/commit/5f8c2380)
* [#5309] [PreventionPlan][FirePermit] fix: signature de l'EU parfois absente du document alors qu'annoncée signée [`fb96b7ab`](https://github.com/Evarisk/Digirisk/commit/fb96b7ab)
* [#5372] [Signature] fix: mobile reusable signature delegated to Saturne, shared across entities [`ee6ee039`](https://github.com/Evarisk/Digirisk/commit/ee6ee039)
* [#5370] [SCSS] fix: keep the export toolbar off the action plan tabs [`d71e2cc4`](https://github.com/Evarisk/Digirisk/commit/d71e2cc4)
* [#5309] [PWA] fix: Chrome ne propose plus d'enregistrer l'adresse de l'entreprise extérieure [`24178d65`](https://github.com/Evarisk/Digirisk/commit/24178d65)
* [#5309] [FirePermit] fix: tags du formulaire mobile rafraîchis comme ceux du plan de prévention [`566dc2de`](https://github.com/Evarisk/Digirisk/commit/566dc2de)
* [#5309] [PWA] fix: placeholder au lieu de l'option « Sélectionner » dans la liste des certifications [`9ae5ed5d`](https://github.com/Evarisk/Digirisk/commit/9ae5ed5d)
* [#5309] [PWA] fix: bouton de création de tiers à la taille du select, croix blanche [`317087c9`](https://github.com/Evarisk/Digirisk/commit/317087c9)
* [#5309] [PWA] fix: picto immeuble au lieu de l'usine pour l'entreprise extérieure [`0bbf9109`](https://github.com/Evarisk/Digirisk/commit/0bbf9109)
* [#5309] [PWA] fix: manifeste déclaré par le hook hookSetManifest au lieu d'un script [`8b97a800`](https://github.com/Evarisk/Digirisk/commit/8b97a800)
* [#5362] [PreventionPlan][FirePermit] feat: recherche par numéro de référence dans les listes, réglable [`08356896`](https://github.com/Evarisk/Digirisk/commit/08356896)
* [#5360] [FirePermit] rework: liste des permis de feu migrée vers la liste générique saturne [`45fce368`](https://github.com/Evarisk/Digirisk/commit/45fce368)
* [#5358] [PreventionPlan] feat: trames de plan de prévention dans la PWA [`6c841913`](https://github.com/Evarisk/Digirisk/commit/6c841913)
* [#5356] [FirePermit] feat: document du permis de feu en PDF natif, comme le plan de prévention [`aa7824b0`](https://github.com/Evarisk/Digirisk/commit/aa7824b0)
* [#5354] [CI] chore: FirePermitRound::$fields dans la baseline PHPStan, comme les autres classes [`80684160`](https://github.com/Evarisk/Digirisk/commit/80684160)
* [#5354] [FirePermit] feat: rondier, fin des travaux et rondes du surveillant de sécurité [`00788353`](https://github.com/Evarisk/Digirisk/commit/00788353)
* [#5352] [PWA] feat: carte des tags intitulée « Tags » avec une infobulle « i » sur leur usage [`258f4968`](https://github.com/Evarisk/Digirisk/commit/258f4968)
* [#5344] [PWA] feat: cartes des listes plus complètes (responsable, état de la période, signature, pictos, création) [`cc721499`](https://github.com/Evarisk/Digirisk/commit/cc721499)
* [#5345] [PWA] fix: visuels bleus de la fenêtre d'installation, en portrait sur téléphone [`70ca51c6`](https://github.com/Evarisk/Digirisk/commit/70ca51c6)
* [#5345] [CSS] chore: rebuild des assets [`a8e86ee8`](https://github.com/Evarisk/Digirisk/commit/a8e86ee8)
* [#5345] [PWA] feat: bouton d'installation de l'application sur l'accueil [`9aee78aa`](https://github.com/Evarisk/Digirisk/commit/9aee78aa)
* [#5338] [CSS] chore: rebuild des assets [`1c015a06`](https://github.com/Evarisk/Digirisk/commit/1c015a06)
* [#5338] [PWA] feat: bandeau de confirmation après l'enregistrement d'un plan [`8656d137`](https://github.com/Evarisk/Digirisk/commit/8656d137)
* [#5336] [PWA] fix: retirer le bouton « Diffusion » en doublon sur la fiche [`c7c48eb9`](https://github.com/Evarisk/Digirisk/commit/c7c48eb9)
* [#5334] [CSS] chore: rebuild des assets [`146c26bc`](https://github.com/Evarisk/Digirisk/commit/146c26bc)
* [#5334] [PWA] feat: griser dans les sélecteurs ce qui est déjà ajouté [`a7d77850`](https://github.com/Evarisk/Digirisk/commit/a7d77850)
* [#5332] [PWA] feat: rechercher aussi par entreprise extérieure et par date [`1673066b`](https://github.com/Evarisk/Digirisk/commit/1673066b)
* [#5330] [PWA] fix: nom du risque dans la confirmation de suppression [`11e0c48b`](https://github.com/Evarisk/Digirisk/commit/11e0c48b)
* [#5328] [PWA] fix: heures affichées dans le fuseau de l'utilisateur [`387c09d1`](https://github.com/Evarisk/Digirisk/commit/387c09d1)
* [#5326] [CSS] chore: rebuild des assets [`20f29c2d`](https://github.com/Evarisk/Digirisk/commit/20f29c2d)
* [#5326] [PWA] fix: loupe au lieu de la flèche sur le bouton de recherche [`a2c80216`](https://github.com/Evarisk/Digirisk/commit/a2c80216)
* [#5289] [ListingRisks] feat: masquages du plan d'actions choisissables a la generation [`1b03f672`](https://github.com/Evarisk/Digirisk/commit/1b03f672)
* [#5322] [PWA] fix: accents en entités HTML dans le bandeau de renvoi du mail [`1ea19b81`](https://github.com/Evarisk/Digirisk/commit/1ea19b81)
* [#5320] [PreventionPlan] feat: modèle d'email de la demande de signature [`39dae739`](https://github.com/Evarisk/Digirisk/commit/39dae739)
* [#5318] [JS] chore: rebuild des assets [`38fb157a`](https://github.com/Evarisk/Digirisk/commit/38fb157a)
* [#5318] [PWA] fix: placer le curseur dans la description après un ajout [`9256c72e`](https://github.com/Evarisk/Digirisk/commit/9256c72e)
* [#5316] [CSS] chore: rebuild des assets [`6dd1495c`](https://github.com/Evarisk/Digirisk/commit/6dd1495c)
* [#5316] [PWA] fix: créer un tag depuis le formulaire et le retrouver au retour [`d325a951`](https://github.com/Evarisk/Digirisk/commit/d325a951)
* [#5314] [CSS] chore: rebuild des assets [`e87a39f9`](https://github.com/Evarisk/Digirisk/commit/e87a39f9)
* [#5314] [PWA] feat: infobulle d'aide sur les cases EU / EE des risques [`82690888`](https://github.com/Evarisk/Digirisk/commit/82690888)
* [#5312] [CSS] chore: rebuild des assets [`f31b7550`](https://github.com/Evarisk/Digirisk/commit/f31b7550)
* [#5312] [PWA] feat: archiver un plan de prévention depuis sa fiche [`1ea1c127`](https://github.com/Evarisk/Digirisk/commit/1ea1c127)
* [#5310] [CSS] chore: rebuild des assets [`111d1aa4`](https://github.com/Evarisk/Digirisk/commit/111d1aa4)
* [#5310] [PWA] fix: astérisques des champs obligatoires en rouge [`486960e1`](https://github.com/Evarisk/Digirisk/commit/486960e1)
* [#5307] [ListingRisks] fix: PieSector() bute sur le typage de TCPDF [`4a69bbb0`](https://github.com/Evarisk/Digirisk/commit/4a69bbb0)
* [#5307] [ListingRisks] feat: roue des neuf principes de prevention [`e284b4e9`](https://github.com/Evarisk/Digirisk/commit/e284b4e9)
* [#5307] [ListingRisks] feat: photo de l'element en pleine page, en option [`3a6fe82d`](https://github.com/Evarisk/Digirisk/commit/3a6fe82d)
* [#5302] [Mod] fix: renommer le changelog en ChangeLog.md [`ef074609`](https://github.com/Evarisk/Digirisk/commit/ef074609)
* [#5303] [ListingRisks] feat: grille des criteres de la methode avancee [`7df93c52`](https://github.com/Evarisk/Digirisk/commit/7df93c52)
* [#5302] [Mod] fix: renommer le changelog en ChangeLog.md [`75c73367`](https://github.com/Evarisk/Digirisk/commit/75c73367)
* [#5287] [ListingRisks] feat: arborescence du perimetre et lexique [`0a1e304b`](https://github.com/Evarisk/Digirisk/commit/0a1e304b)
* [#5299] [ListingRisks] fix: en-tete « Date de declaration » tronque [`3a5fae89`](https://github.com/Evarisk/Digirisk/commit/3a5fae89)
* [#5299] [ListingRisks] fix: risques, en-tetes SICTOM et actions scindees [`0c2245b2`](https://github.com/Evarisk/Digirisk/commit/0c2245b2)
* [#5299] [ListingRisks] fix: accidents, colonnes de la maquette SICTOM [`879b42c5`](https://github.com/Evarisk/Digirisk/commit/879b42c5)
* [#5299] [ListingRisks] fix: registres, 8 colonnes ramenees a 6 [`1936a81f`](https://github.com/Evarisk/Digirisk/commit/1936a81f)
* [#5293] [Menu] fix: placer « App Mobile » juste sous « Tableau de bord » [`e3dd09bc`](https://github.com/Evarisk/Digirisk/commit/e3dd09bc)
* [#5281] [Ticket] fix: doublon de la premiere phrase dans le mail de reponse [`bfb9069d`](https://github.com/Evarisk/Digirisk/commit/bfb9069d)
* [#5294] [CI] fix: ne scanner de Saturne que les dossiers qui portent ses classes [`af3ebfe1`](https://github.com/Evarisk/Digirisk/commit/af3ebfe1)
* [#5274] [Doc] add: avertir de la reprise WU vers UT dans les notes de la 23.5.0 [`6d3d2394`](https://github.com/Evarisk/Digirisk/commit/6d3d2394)
* [#4830] [CI] chore: recompiler les assets de la grille RPS-DU [`bf014cae`](https://github.com/Evarisk/Digirisk/commit/bf014cae)
* [#4830] [Risk] feat: grille RPS-DU de l'INRS (ED 6403) comme 2e methode RPS [`1a371f5a`](https://github.com/Evarisk/Digirisk/commit/1a371f5a)
* [#5071] [PreventionPlan] rework: liste migrée vers la liste générique saturne [`c0942317`](https://github.com/Evarisk/Digirisk/commit/c0942317)
* fix(module): fix undefined property stdClass::enabled warnings in PHP 8.2 [`4558538e`](https://github.com/Evarisk/Digirisk/commit/4558538e)
