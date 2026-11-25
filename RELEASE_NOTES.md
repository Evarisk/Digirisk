# [Digirisk] [23.10.0] - Bibliothèque des risques de l'entité et son tableau de synthèse - Groupes d'exposition en pourcentage - Actions correctives et préventives sur la fiche ticket

Description : Les risques que l'on retrouve dans plusieurs groupements et unités de travail se décrivent une fois dans une bibliothèque de l'entité, puis s'affectent à chaque GP/UT concerné, avec un tableau qui montre où chacun se trouve et à quelle cotation. Les groupes de personnes qui occupent les mêmes postes se répartissent en pourcentage de leur temps sur les GP/UT, pour connaître l'effectif exposé de chacun. La fiche ticket en mode Digirisk montre et ajoute les actions correctives et préventives, et la fiche ticket native mène à elle d'un clic. Cette version corrige aussi des échéances décalées d'un jour dans les documents, les documents générés en même temps qui se mélangeaient, le droit de modifier la configuration et plusieurs erreurs sur les fiches.

**Cette version demande Saturne 23.10.1 ou supérieur.**

## 🎬 La nouveauté en vidéo

<a href="https://github.com/Evarisk/Digirisk/releases/download/23.10.0/digirisk-23.10.0.mp4"><img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.10.0/video_poster_23.10.0.jpeg" alt="La bibliothèque des risques de l'entité en vidéo (33 s)"></a>

*Cliquez sur l'image pour voir la vidéo (33 s).*

## Nouvelles fonctionnalités et innovations

### Bibliothèque des risques de l'entité

* Nouvel onglet **Bibliothèque des risques** au niveau de l'entité. Un risque que l'on retrouve dans plusieurs GP/UT (travail sur écran, trajets, escaliers…) s'y décrit **une seule fois** : catégorie de danger, libellé, description et **cotation indicative** de la méthode standard.
* La liste des risques de la bibliothèque montre l'affectation de chacun en trois pastilles : les GP/UT concernés, non concernés (NC) et encore à qualifier.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.10.0/bibliotheque-modeles.png" alt="Liste des risques de la bibliothèque : catégorie de danger, libellé, description, cotation indicative et affectation en pastilles (concernés, NC, à qualifier)">

* Chaque GP/UT se qualifie risque par risque, comme les risques psychosociaux : **concerné**, **non concerné** avec son motif (obligatoire), ou **à qualifier**.
* Concerné, le GP/UT reçoit **son propre risque**, créé depuis la bibliothèque avec une première évaluation à la cotation indicative. Il s'évalue et porte ses actions à part, et la liste des risques, le document unique, la synthèse publique et le plan d'action le lisent comme les autres. Non concerné ou à qualifier, ce risque est archivé avec ses évaluations et ses tâches, et retrouvé tel quel s'il redevient concerné.
* Retirer un risque de la bibliothèque garde les risques déjà créés dans les GP/UT.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.10.0/bibliotheque-qualification.png" width="60%" alt="Qualification d'un GP/UT pour un risque de la bibliothèque : Concerné, Non concerné ou À qualifier, avec le motif Pas de déplacement professionnel">

### Le tableau de synthèse : où est chaque risque

* Sous la bibliothèque, une grille **une ligne par GP/UT, une colonne par risque** sert de tableau de synthèse. La case d'un GP/UT concerné montre la **cotation actuelle** de son risque et mène à sa fiche ; « NC » signale un GP/UT non concerné (motif au survol) et « ? » un GP/UT à qualifier.
* Le crayon de chaque case change la qualification sans quitter la page, et le pied de grille compte les GP/UT concernés par chaque risque.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.10.0/bibliotheque-grille.png" alt="Grille Affectation aux GP/UT : une ligne par groupement et unité de travail, une colonne par risque, avec la cotation actuelle, NC ou ?, et le nombre de GP/UT concernés en pied de grille">

### Groupes d'exposition répartis en pourcentage

* Nouvel onglet **Groupes d'exposition** au niveau de l'entité : les groupes de personnes qui occupent les mêmes postes (équipe technique, consultants, agents de collecte…), avec leur **effectif**.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.10.0/groupes-exposition-liste.png" alt="Liste des groupes d'exposition : libellé, description, effectif et répartition totale, à 100 % en vert ou à 80 % en orange">

* Chaque groupe se **répartit en pourcentage de son temps de travail** sur les GP/UT où il travaille. Le total de chaque groupe passe au vert à 100 %, en orange en dessous et en rouge au-dessus, et se recalcule à la saisie, comme l'**effectif exposé** de chaque GP/UT : la somme des effectifs multipliés par leur part.
* Une part donnée à un groupement vaut pour le groupement lui-même, sans se répartir sur ses unités.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.10.0/groupes-exposition-grille.png" alt="Grille de répartition des groupes sur les GP/UT, en pourcentage, avec l'effectif exposé de chaque GP/UT et la répartition de chaque groupe en pied de grille">

* La fiche d'un groupement ou d'une unité de travail gagne une ligne **Groupes d'exposition**, sous les personnes présentes : la part et le nombre de personnes de chaque groupe, et l'effectif exposé.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.10.0/fiche-ut-groupes-exposition.png" alt="Fiche d'une unité de travail : ligne Groupes d'exposition, Équipe technique : 75 %, soit 9 personnes, effectif exposé 9">

### Tickets : les actions correctives et préventives sur la fiche

* Sur la fiche ticket en mode Digirisk, le bloc des tâches devient **Actions correctives / préventives** : une section par type, avec son compteur (terminées / total) et son bouton d'ajout, puis les autres tâches du ticket s'il en reste. Il comptait (0) quand le ticket n'avait que des actions.
* L'ajout se fait dans une fenêtre par type, avec les champs de l'onglet Actions (libellé, dates, budget, responsable, tags) ; l'avancement se modifie sur place et « Tout voir » mène à l'onglet.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.10.0/ticket-actions.png" width="70%" alt="Bloc Actions correctives / préventives de la fiche ticket : actions correctives 1 / 2 et préventives 0 / 2, avec leurs dates, échéance, progression et responsable, et les boutons Ajouter une action corrective et Ajouter une action préventive">

* Sur la fiche ticket native de Dolibarr, un lien **Ouvrir la fiche en mode Digirisk** sous la référence mène à la fiche Digirisk du ticket.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.10.0/ticket-lien-mode-digirisk.png" width="55%" alt="Bandeau de la fiche ticket native : référence, sujet, responsable, projet et le lien Ouvrir la fiche en mode Digirisk">

## Améliorations & corrections

### Documents

* Les échéances des tâches ne sont plus décalées d'un jour dans le document unique en PDF, le listing des risques, la présentation au CSE et l'enquête accident, ni celles des actions dans le PDF QQOQCCP d'un ticket.
* Deux documents générés en même temps ne se prennent plus leur QR code ou leurs signatures : chaque génération a ses propres images temporaires, supprimées à la fin même en cas d'erreur. Les signatures des intervenants du permis de feu et du plan de prévention ne s'effacent plus entre elles.
* Le registre trouve les contrôles de DigiQuali quel que soit le dossier où DigiQuali est installé.

### Fiches et configuration

* Une création ou une modification qui échoue réaffiche le formulaire avec la saisie (permis de feu, plan de prévention, accident), au lieu d'une fiche vide ou d'une erreur affichée trois fois.
* Le droit **Modifier la configuration** est pris en compte : sans lui, les pages de configuration s'affichent en lecture seule et le disent, au lieu d'interrupteurs désactivés sans explication.
* La fiche, les métadonnées et les lésions d'un accident ouvertes sans accident chargé n'affichent plus d'erreur fatale.
* Créer un arrêt de travail sans y joindre de fichier n'écrit plus d'avertissement.
* Sans droit sur Digirisk, les pages d'administration refusent l'accès sans écrire d'avertissements.
* Les onglets d'un GP/UT ne sont plus remplacés par l'onglet fiche de Saturne, ni l'onglet Environnement d'un groupement par l'onglet DigiAI.
* La fenêtre d'édition d'un risque n'affiche plus d'erreur technique sur ses tags : les tags des risques, accidents, plans de prévention et permis de feu lisent les bonnes tables.
* Les catégories de danger 18 à 21 ne sont plus déplacées en 22 à chaque mise à jour.
* Le responsable de l'entreprise extérieure d'un permis de feu n'est plus vidé après le choix du tiers.
* La page Diffusion d'informations affiche correctement le responsable harcèlement et le responsable harcèlement du CSE.

### Tickets et risques

* La signature de la page publique d'un ticket n'est enregistrée que si c'est bien une image.
* Générer ou supprimer un document GMAO depuis la fiche ticket n'écrit plus d'avertissement dans le journal PHP.
* La suppression de risques en masse n'écrit plus d'avertissement quand le dossier des vignettes n'existe pas.
* L'envoi d'un plan du site efface les anciennes vignettes, sans avertissement.
* Quand Saturne est introuvable, le message d'erreur dit où l'installer, au lieu d'un simple « Include of saturne main fails ».

## Testez les nouveautés maintenant

**Allez tester la bibliothèque des risques et les groupes d'exposition sur la démo : https://demodoli.digirisk.com/**

## Comparaison des versions [23.9.1](https://github.com/Evarisk/Digirisk/compare/23.9.1...23.10.0) et 23.10.0

* [#5864] [Admin] fix: droit d'administration lu par hasRight() sur les pages d'administration [`b8102cad`](https://github.com/Evarisk/Digirisk/commit/b8102cad)
* [#5862] [Risk] fix: liste des modèles de la bibliothèque sans débordement, affectation en pastilles [`abd12a90`](https://github.com/Evarisk/Digirisk/commit/abd12a90)
* [#5859] [Accident] fix: création d'un arrêt de travail sans fichier, plus de warning rename sur workstop/temp absent [`a136252c`](https://github.com/Evarisk/Digirisk/commit/a136252c)
* [#5858] [DocumentUnique] fix: envoi d'un plan du site sans warning unlink sur thumbs, anciennes vignettes effacées [`5c7e7ede`](https://github.com/Evarisk/Digirisk/commit/5c7e7ede)
* [#5856] [Accident] fix: no more fatal error fetchFromParent() on the metadata and lesion pages without a loaded accident [`7529531a`](https://github.com/Evarisk/Digirisk/commit/7529531a)
* [#5854] [Risque] fix: suppression de risques en masse sans warning rmdir sur le dossier thumbs absent [`981c8a38`](https://github.com/Evarisk/Digirisk/commit/981c8a38)
* [#5852] [Ticket] fix: droits de documents_action.tpl.php à 0 pour l'action d'un autre module [`40b9f3f0`](https://github.com/Evarisk/Digirisk/commit/40b9f3f0)
* [InformationsSharing] fix: référents chargés dans un objet User dédié [`f20a96f1`](https://github.com/Evarisk/Digirisk/commit/f20a96f1)
* [#5844] [Card] fix: création ou modification en échec, le formulaire est réaffiché [`86c8e3f2`](https://github.com/Evarisk/Digirisk/commit/86c8e3f2)
* [#5845] [Saturne] fix: message explicite quand l'amorçage ne trouve pas Saturne [`e3cfa2bd`](https://github.com/Evarisk/Digirisk/commit/e3cfa2bd)
* [#5842] [Registre] fix: inclure Control de DigiQuali par dol_include_once [`f47222cf`](https://github.com/Evarisk/Digirisk/commit/f47222cf)
* [#5839] [Accident] fix: fiche accident sans accident chargé, plus d'erreur fatale fetchFromParent() [`ad6d936d`](https://github.com/Evarisk/Digirisk/commit/ad6d936d)
* [Ticket] fix: signature de la page publique enregistrée seulement si c'est une image [`ec701910`](https://github.com/Evarisk/Digirisk/commit/ec701910)
* [#5835] [Admin] fix: droit « Modifier la configuration » et pages en lecture seule [`63fab49c`](https://github.com/Evarisk/Digirisk/commit/63fab49c)
* [#5644] [Risk] feat: bibliothèque des risques de l'entité, affectée aux GP/UT avec un tableau de synthèse [`5c48e753`](https://github.com/Evarisk/Digirisk/commit/5c48e753)
* [#5829] [Document] fix: images temporaires des documents sous un nom propre à chaque génération [`fbe7543b`](https://github.com/Evarisk/Digirisk/commit/fbe7543b)
* [#5644] [DigiriskElement] feat: groupes d'exposition répartis en pourcentage sur les GP/UT [`12b37537`](https://github.com/Evarisk/Digirisk/commit/12b37537)
* [#5644] [Ticket] fix: force_entity de select_dolusers() en chaîne dans la modale d'ajout d'action [`1251553f`](https://github.com/Evarisk/Digirisk/commit/1251553f)
* [#5644] [Ticket] feat: les actions correctives et préventives sur la fiche du ticket, comme sur leur onglet [`df595ac4`](https://github.com/Evarisk/Digirisk/commit/df595ac4)
* [#5644] [Ticket] feat: lien de la fiche ticket native vers la fiche en mode Digirisk [`2db0e126`](https://github.com/Evarisk/Digirisk/commit/2db0e126)
* [#5827] [Document] fix: échéances des tâches décalées d'un jour dans le DU PDF, le listing, la présentation CSE et l'enquête accident [`c671fbb4`](https://github.com/Evarisk/Digirisk/commit/c671fbb4)
* [#5824] [Category] fix: tables de liaison des tags déclarées dans le hook constructCategory [`00b5427e`](https://github.com/Evarisk/Digirisk/commit/00b5427e)
* [#5823] [Risk] fix: catégories de danger 18 à 21 plus déplacées en 22 à chaque mise à jour [`8ada6165`](https://github.com/Evarisk/Digirisk/commit/8ada6165)
* [#5821] [FirePermit] fix: responsable de l'entreprise extérieure vide après le choix du tiers [`ceea10c8`](https://github.com/Evarisk/Digirisk/commit/ceea10c8)
* [#5817] [DigiriskElement] fix: onglets d'un GP/UT écrasés par l'onglet fiche de Saturne [`42b6d160`](https://github.com/Evarisk/Digirisk/commit/42b6d160)
* [#5818] [Ticket] fix: échéance des actions du PDF QQOQCCP décalée d'un jour [`76948fa5`](https://github.com/Evarisk/Digirisk/commit/76948fa5)
* [#5817] [DigiriskElement] fix: onglet Environnement d'un groupement écrasé par l'onglet DigiAI [`2f7e5df2`](https://github.com/Evarisk/Digirisk/commit/2f7e5df2)
