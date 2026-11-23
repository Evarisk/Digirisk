# [Digirisk] [23.9.0] - Diffusion du plan de prévention suivie depuis le téléphone - Permis de feu signé avec la signature du plan - Fiche de poste INRS ED 126 - Nouveau document QQOQCCP

Description : Dans l'application mobile, le plan de prévention montre dès sa création les documents demandés à chaque intervenant, puis qui a signé sa diffusion, avec la feuille d'émargement à télécharger ; les PDF du plan et du permis de feu listent ces signataires, et le permis de feu reprend en un clic la signature que l'entreprise extérieure a donnée au plan. Chaque groupement et unité de travail a sa fiche de poste au modèle INRS ED 126, les instances représentatives du personnel (CSE, CHSCT, F3SCT, délégués) se choisissent dans la configuration, l'analyse QQOQCCP d'un ticket sort dans un document au format du document unique, et cette version corrige la liste des risques psychosociaux, la date des temps passés, une notification de ticket en double et plusieurs libellés.

**Cette version demande Saturne 23.9.1 ou supérieur.** La diffusion des plans de prévention et des permis de feu passe par DoliLetter : sans lui, les blocs et la section qui s'y rapportent n'apparaissent pas, et le reste de Digirisk fonctionne normalement.

## 🎬 La nouveauté en vidéo

<a href="https://github.com/Evarisk/Digirisk/releases/download/23.9.0/digirisk-23.9.0-diffusion.mp4"><img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.9.0/video_poster_23.9.0.jpeg" alt="La diffusion du plan de prévention, de bout en bout, en vidéo (33 s)"></a>

*Cliquez sur l'image pour voir la vidéo (33 s).*

## Nouvelles fonctionnalités et innovations

### Plan de prévention : la diffusion suivie depuis le téléphone

* Le bloc **Personnes de la diffusion** de l'écran du plan s'affiche **dès la création du plan**, et plus seulement une fois que quelqu'un a rejoint la diffusion : on sait tout de suite où regarder.
* Il commence par les **documents demandés à chaque personne** (CACES, habilitations…), avec la pastille **Obligatoire** pour ceux qu'il faut fournir avant de signer. Tant que personne n'a rejoint la diffusion, il l'indique simplement.
* Dès qu'une personne a signé, le bouton **Télécharger la feuille d'émargement** donne le PDF de la diffusion, régénéré au moment du clic avec toutes les signatures reçues. Il est proposé à qui a le droit de lecture de DoliLetter.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.9.0/pwa-diffusion-plan-cree.png" width="32%" alt="Plan de prévention tout juste créé sur téléphone : bloc Personnes de la diffusion avec les documents demandés, CACES R486 obligatoire et Travail en hauteur, et le message Personne n'a encore rejoint la diffusion"> <img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.9.0/pwa-diffusion-signatures.png" width="32%" alt="Plan de prévention diffusé sur téléphone : 2 signatures sur 3, deux personnes signées avec la date, une en attente, et le bouton Télécharger la feuille d'émargement">

### Permis de feu : signer directement avec la signature du plan de prévention

* Dans le formulaire du permis de feu de l'application mobile, une case **Signer directement avec la signature du plan de prévention** apparaît sous le choix du plan, quand le responsable de l'entreprise extérieure a déjà signé ce plan et que c'est la même personne sur le permis. Elle rappelle son nom et la date de sa signature.
* Cochée, sa signature est reprise sur le permis comme une signature donnée à la main : événement d'agenda, verrouillage automatique une fois tout signé, document régénéré. L'e-mail de demande de signature n'est alors pas envoyé.
* Elle fonctionne à la création comme à la modification d'un permis ; si la signature ne peut pas être reprise, un avertissement le signale.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.9.0/pwa-permis-signature-plan.png" width="32%" alt="Formulaire du permis de feu sur téléphone : plan de prévention PP3 choisi et case cochée Signer directement avec la signature du plan de prévention, avec le nom du responsable et Signé le 23/11/26">

### Les signataires de la diffusion dans le PDF du plan de prévention et du permis de feu

* Le document du plan de prévention gagne une section **Personnes ayant signé la diffusion**, après les signatures des responsables : nom, e-mail et téléphone, date et heure, et signature de chacun, dans l'ordre des signatures. Le permis de feu a la même section.
* Le document est **régénéré à chaque signature de la diffusion** avec DoliLetter 23.3.0 ou supérieur : il tient à jour la liste de ceux qui ont pris connaissance du plan. Avec une version antérieure de DoliLetter, la liste apparaît à la génération suivante du document.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.9.0/pdf-diffusion-signataires.png" alt="PDF d'un plan de prévention : signatures des responsables des entreprises utilisatrice et extérieure, puis la nouvelle section Personnes ayant signé la diffusion avec nom, e-mail et téléphone, date et signature de deux personnes">

### Fiche de poste au modèle INRS ED 126

* Nouvel onglet **Fiche de poste** sur chaque groupement et unité de travail. Il prépare la fiche de poste d'une machine d'après la fiche pratique INRS **ED 126** (« Constituer les fiches de poste machines »), à partir de ce que Digirisk sait déjà : le document unique de l'élément, la fiche d'instruction de la machine et les machines liées dans l'onglet Produits | Services. Sans machine liée, la fiche décrit le poste lui-même.
* Le tableau **Appui ED 126** indique, rubrique par rubrique, d'où vient l'information, si elle est remplie, partielle, vide ou à compléter à la main, avec le lien pour la compléter.
* La fiche se génère en **PDF A4**, numérotée et versionnée (« Version 1 du 23/11/2026 - FDP2611-0001 ») : identification et photo de la machine, mise en marche et arrêt d'urgence, EPI obligatoires ou interdits, tâches exposant à un risque avec les risques du document unique et les mesures du plan d'action (celles à venir marquées « prévue »), qualification et numéros de secours. Ce que Digirisk ne connaît pas reste en pointillés, à compléter à la main.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.9.0/fiche-de-poste-onglet.png" width="49%" alt="Onglet Fiche de poste d'une unité de travail : poste sans machine retenu, tableau Appui ED 126 avec l'état de chaque rubrique (remplie, partielle, vide, à la main) et la première version générée"> <img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.9.0/fiche-de-poste-pdf.png" width="49%" alt="Fiche de poste PDF au modèle ED 126 : version, machine et caractéristiques, EPI, tableau des tâches avec les risques du document unique et les mesures du plan d'action, qualification et secours">

### Instances représentatives du personnel

* Dans **Configuration > Social**, on choisit les instances dont l'entreprise est dotée : **CSE, CHSCT, F3SCT, délégués du personnel**. Chacune a sa section : date d'élection ou de désignation, titulaires et suppléants.
* La page **Diffusion d'informations** et son document suivent ce choix, une instance par bloc ; le référent harcèlement du CSE n'apparaît qu'avec le CSE.
* Rien ne se perd : tant que le choix n'est pas enregistré, la page reprend l'existant (le CSE, et les délégués du personnel déjà renseignés), et une instance retirée du choix garde ses données, retrouvées quand on la choisit de nouveau. Les modèles de document personnalisés gardent leurs anciennes balises.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.9.0/social-instances.png" alt="Configuration Social : instances représentatives du personnel choisies, Comité Social et Économique et F3SCT, avec pour chacune la date d'élection ou de désignation, les titulaires et les suppléants">

### Tickets : un document QQOQCCP au format du document unique

* Le document généré depuis l'onglet **QQOQCCP** d'un ticket est entièrement refait aux couleurs des documents du document unique.
* En première page : un bandeau (ticket, sujet, projet), les cartes du ticket (date de création, GP/UT, personne assignée, état) et les chiffres clés (questions renseignées, actions correctives, actions préventives, actions terminées).
* L'**analyse du problème** présente une ligne par question avec sa lettre (Q Q O Q C C P), son aide et la réponse ; « Qui ? » s'affiche en pastilles de noms, une réponse vide en « Non renseigné », et une réponse longue continue sur la page suivante.
* Nouvelles sections **Actions correctives** et **Actions préventives**, reprises de l'onglet Actions du ticket : un compteur d'actions terminées, puis une carte par action avec son état (terminée, en retard, en cours, à faire), sa référence, la personne assignée, ses dates, son budget, ses tags et sa barre d'avancement. Une carte n'est jamais coupée par un saut de page. Sans action ou sans projet sur le ticket, le document l'indique.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.9.0/qqoqccp-page-1.png" width="49%" alt="Document QQOQCCP, page 1 : bandeau du ticket, cartes date de création, GP/UT, assigné et état, chiffres clés et analyse du problème question par question"> <img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.9.0/qqoqccp-page-2.png" width="49%" alt="Document QQOQCCP, page 2 : actions correctives et préventives en cartes, avec leur état en retard, terminée, à faire ou en cours et leur barre d'avancement">

## Améliorations & corrections

### Risques psychosociaux

* Dans la liste des risques d'une unité de travail, **tous les risques psychosociaux saisis avant l'arrivée du bloc RPS sont de nouveau listés** (plusieurs par unité, cotés en méthode standard ou avancée) : seul le premier était affiché, les autres étaient masqués à tort.
* La pagination et le total de la liste ne comptent plus que les lignes affichées.
* L'évaluation des RPS d'une unité n'écrase plus la cotation d'un de ces anciens risques : elle crée son propre risque.

### Tickets

* Une déclaration faite depuis la page publique des registres ne déclenche plus **deux notifications** : quand Digirisk envoie son propre e-mail de déclaration (réglage « Envoyer des emails à la création » avec ses destinataires), le mail « Nouveau ticket créé » du module Ticket ne part plus en double vers l'adresse commune à tous les modules. Sans destinataire Digirisk ou avec cet envoi désactivé, la notification du module Ticket reste la seule, comme avant.

### Plans d'action et temps passés

* La **date d'un temps passé modifiée depuis la tâche d'un risque** est enfin enregistrée : elle restait au jour d'origine et l'heure retombait à minuit.
* Les temps passés saisis depuis Digirisk enregistrent leur heure, et un temps passé ajouté sans date est daté de l'heure exacte, sans décalage de fuseau horaire.

### Diffusion et documents

* Diffusé par DoliLetter, le document unique s'appelle **« Document unique »** sur la page publique de la diffusion, dans la liste des diffusions et dans les e-mails, au lieu de « standard digirisk ».
* Une personne de la diffusion à qui un rôle a été attribué n'est plus oubliée dans le bloc Personnes de la diffusion de l'écran du plan de prévention.

### Accidents

* L'événement d'agenda de la validation d'une enquête accident porte la **référence définitive** de l'enquête, au lieu de sa référence provisoire « (PROV…) ».

### Fichiers et installation

* Activé seul depuis **Accueil > Modules**, Digirisk replace lui aussi sous leur référence définitive les fichiers de ses objets validés restés dans un dossier provisoire « (PROV…) », avant de ranger leurs photos dans la médiathèque. C'est cette reprise qui demande Saturne 23.9.1.
* Dans Configuration > Modules > Déclencheurs, l'icône des déclencheurs de Digirisk s'affiche : l'image demandée n'existait pas.

## Testez les nouveautés maintenant

**Allez tester la diffusion du plan de prévention dans l'application mobile sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.8.0](https://github.com/Evarisk/Digirisk/compare/23.8.0...23.9.0) et 23.9.0

* [#5811] [Trigger] fix: chargement des traductions rétabli (remplacé à tort avec le picto) [`9027e952`](https://github.com/Evarisk/Digirisk/commit/9027e952)
* [#5810] [Mod] fix: fichiers des objets validés restés sous (PROVn) repris à l'activation depuis Accueil > Modules [`b450b226`](https://github.com/Evarisk/Digirisk/commit/b450b226)
* [#5811] [Trigger] fix: picto digiriskdolibarr_color@digiriskdolibarr, l'image digiriskdolibarr@digiriskdolibarr n'existe pas [`9a14ef43`](https://github.com/Evarisk/Digirisk/commit/9a14ef43)
* [#5808] [PWA] fix: notrigger en entier pour update() et setSigned() (PHPStan) [`388c4fca`](https://github.com/Evarisk/Digirisk/commit/388c4fca)
* [#5808] [PWA] add: signer directement le permis de feu avec la signature du plan de prévention [`18f723d5`](https://github.com/Evarisk/Digirisk/commit/18f723d5)
* [#5806] [PWA] add: télécharger la feuille d'émargement de la diffusion depuis l'écran du plan de prévention [`56b0d6ab`](https://github.com/Evarisk/Digirisk/commit/56b0d6ab)
* [#5804] [Document] fix: filtre de array_filter() qui renvoie un booléen (PHPStan) [`5c426052`](https://github.com/Evarisk/Digirisk/commit/5c426052)
* [#5804] [Document] add: personnes ayant signé la diffusion dans le PDF du plan de prévention et du permis de feu [`91bc1fde`](https://github.com/Evarisk/Digirisk/commit/91bc1fde)
* [#5802] [PWA] add: personnes de la diffusion visibles dès la création du plan, avec les documents demandés [`dc459e79`](https://github.com/Evarisk/Digirisk/commit/dc459e79)
* [#5800] [AccidentInvestigation] fix: événement de validation libellé avec la référence définitive, plus « (PROVn) » [`fc898894`](https://github.com/Evarisk/Digirisk/commit/fc898894)
* [#5798] [Ticket] feat: QQOQCCP document in the style of the DU documents, with the corrective and preventive actions [`9b7118fd`](https://github.com/Evarisk/Digirisk/commit/9b7118fd)
* [#5796] [RPS] fix: psychosocial risks filed before the RPS block listed again [`b236f627`](https://github.com/Evarisk/Digirisk/commit/b236f627)
* [#5792] [Task] fix: modifier la date d'un temps passé depuis la tâche d'un risque [`77145707`](https://github.com/Evarisk/Digirisk/commit/77145707)
* [#5791] [DigiriskStandard] fix: document unique nommé comme tel dans les métadonnées des objets [`679e154b`](https://github.com/Evarisk/Digirisk/commit/679e154b)
* [#5756] [Ticket] fix: no mail new ticket of the module Ticket when Digirisk sends its own declaration mail [`6c121573`](https://github.com/Evarisk/Digirisk/commit/6c121573)
* [#5148] [DigiriskElement] feat: job sheet tab with the ED 126 checklist [`f821ffc2`](https://github.com/Evarisk/Digirisk/commit/f821ffc2)
* [#5148] [Document] feat: job sheet PDF after the INRS ED 126 model [`073a0c97`](https://github.com/Evarisk/Digirisk/commit/073a0c97)
* [#5690] [Social] feat: staff representative bodies chosen in the social setup (CSE, CHSCT, F3SCT, DP) [`b6405fc0`](https://github.com/Evarisk/Digirisk/commit/b6405fc0)
