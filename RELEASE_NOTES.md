# [Digirisk] [23.8.0] - Causeries sécurité dans l'application mobile - Clôture rapide des tickets

Description : Les **causeries sécurité** arrivent dans l'application mobile : un cycle de causeries pour une équipe, une causerie par date et par thème, la signature de chacun au QR code et un **planning PDF** à afficher sur le chantier. Les tickets des registres se **clôturent en un clic** depuis la liste ou le kanban, la configuration d'une catégorie montre **l'interface publique en direct**, et les **risques psychosociaux** se qualifient depuis la liste des risques d'une unité.

**Cette version demande Saturne 23.8.0 ou supérieur.** Les causeries sont diffusées par **DoliLetter 23.2.0 ou supérieur** ; sans lui, l'application l'indique et le reste de Digirisk fonctionne normalement.

## 🎬 La nouveauté en vidéo

<a href="https://github.com/Evarisk/Digirisk/releases/download/23.8.0/digirisk-23.8.0.mp4"><img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.8.0/video_poster_23.8.0.jpeg" alt="Les causeries sécurité dans l'application mobile, en vidéo (43 s)"></a>

*Cliquez sur l'image pour voir la vidéo (43 s).*

## Nouvelles fonctionnalités et innovations

### Causeries sécurité dans l'application mobile

* Nouvelle rubrique **Causeries** dans l'application mobile. Un **cycle** réunit une équipe : des utilisateurs de Dolibarr ou des personnes sans compte, saisies à la volée (prénom, nom, e-mail facultatif).
* Chaque **causerie** a sa date et son **thème**, choisi parmi les catégories de danger, qui préremplit le titre. Photos, vidéos, documents et liens (vidéo en ligne, page web) s'y ajoutent en quelques touches.
* L'écran de la causerie suit son **avancement** (créée, diffusée, animée, signée) et l'état de la signature de chaque personne. Un **QR code** ouvre la page de signature : chacun signe sur son téléphone, sans compte ni connexion. Une personne présente ce jour-là s'ajoute à cette causerie seulement.
* Ajouter une personne au cycle l'ajoute à toutes ses causeries ; la retirer la sort de celles qu'elle n'a pas encore signées.
* L'accueil de l'application affiche deux nouvelles tuiles : les **causeries à venir** et les **signatures en attente**.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.8.0/causeries-cycle.png" width="32%" alt="Cycle de causeries sur téléphone : trois causeries, leur date, leur thème et leurs signatures"> <img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.8.0/causeries-causerie.png" width="32%" alt="Écran d'une causerie : avancement, thème, date et signatures des personnes"> <img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.8.0/causeries-accueil.png" width="32%" alt="Accueil de l'application mobile : tuiles Causeries à venir et Signatures de causeries en attente">

### Planning PDF des causeries

* Le **planning** d'un cycle se génère en PDF (A4 paysage) : une frise des causeries par mois, l'état de chacune, et pour chacune un **QR code** et un **lien court** vers sa page de signature. Il s'imprime et s'affiche sur le chantier.
* La fiche Dolibarr d'un projet ou d'une tâche de cycle l'indique, avec un bouton **Ouvrir dans l'application Causeries**.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.8.0/causeries-planning.png" alt="Planning PDF d'un cycle de trois causeries : frise par mois, état des signatures, QR codes et liens courts">

### Tickets : clôture rapide et liste des registres

* Un ticket se **clôture en un clic** depuis son état dans la **liste des registres**, ou depuis le pourcentage de sa carte dans le **kanban** : une fenêtre propose l'avancement (à 100 %, le ticket est clôturé) et un commentaire, ajouté au ticket en message privé.
* La **liste des registres** ouverte depuis le menu Digirisk prend le thème Digirisk et toute la largeur de l'écran : plus de menu de gauche, titres de colonnes qui restent en haut au défilement, tags en pastilles.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.8.0/registres-cloture-rapide.png" alt="Fenêtre Clôturer le ticket ouverte depuis la liste des registres : progression à 100 % et commentaire">

### Aperçu en direct de l'interface publique des tickets

* La configuration d'une catégorie de ticket montre, à côté des réglages, **ce que verra le déclarant** : services, catégories, champs et message de succès, mis à jour à chaque réglage, avant même d'enregistrer.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.8.0/categorie-apercu.png" alt="Configuration de la catégorie Santé et sécurité au travail avec l'aperçu de l'interface publique à droite">

### Risques psychosociaux qualifiés depuis la liste des risques

* Dans la liste des risques d'une unité de travail, la pastille **Risques psychosociaux** devient la liste de qualification : *Concernée*, *Non concernée* ou *À qualifier*. La page se met à jour sans rechargement, avec un bouton Annuler.
* Le motif d'une unité non concernée se modifie d'un clic, et la bulle propose les motifs déjà saisis pour les autres unités.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.8.0/rps-qualification.png" alt="Liste des risques d'une unité : la pastille Risques psychosociaux ouverte sur Concernée, Non concernée et À qualifier">

### Personnes présentes par GP/UT

* La configuration des GP/UT règle, dans une seule fenêtre, la **part des personnes présentes** dans chaque groupement et unité de travail.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/refs/heads/assets/release-23.8.0/gpu-pourcentage.png" alt="Fenêtre Personnes présentes par GP/UT : un pourcentage par groupement et unité de travail">

### Et aussi

* **PAPRIPACT** : le document A3 affiche le libellé des tâches, le pictogramme de la catégorie et la description du risque, dans des colonnes plus étroites.
* **Plan de prévention** : l'écran du plan dans l'application mobile montre les personnes de la diffusion et leurs documents. Le téléphone et l'e-mail de la diffusion se règlent séparément.
* **Thème moderne** sur les onglets natifs des tâches et des tickets, et la fiche Digirisk d'une tâche s'ouvre depuis tous les liens vers cette tâche.

## Améliorations & corrections

### Tickets

* Une carte déposée dans une autre colonne du kanban enregistre bien le nouvel état du ticket. Le kanban ne construit plus que le tableau affiché, et les utilisateurs et tags de ses cartes ne sont chargés qu'une fois.
* L'option « Requis » des photos et pièces jointes de l'interface publique est prise en compte, et l'e-mail et les photos ne manquent plus sur l'interface publique des entités créées après coup.
* La fiche d'un ticket s'affiche sans le droit de suppression, et des utilisateurs s'assignent sans groupe par défaut.
* En mode transverse de Multicompany, les utilisateurs de l'entité sont assignables et la page des utilisateurs Digirisk s'affiche.

### Document unique et documents

* Les risques hérités ne sortent plus deux fois avec les modèles « hérités » et « complet », et les risques partagés reviennent dans les fiches GP/UT.
* L'archive ZIP du document unique contient les PDF des fiches GP/UT, et plus de fiches pour les GP/UT d'une autre entité.
* Le spécimen du listing des risques est dessiné sur le document unique actif.

### Risques et médias

* Les photos des fenêtres d'évaluation ne se chargent qu'à leur ouverture : l'onglet Risques s'ouvre plus vite.
* La description du risque suit le changement de danger après modification. La pastille d'évaluation n'est plus cachée par sa photo, et le commentaire d'évaluation se lit mieux.
* Les miniatures des fichiers envoyés sont créées, les photos d'un risque en création restent propres à la session, et le type des médias envoyés est vérifié plus strictement.
* L'onglet Diffusion d'un risque n'est plus vide.

### Risques psychosociaux

* Les moyennes s'affichent avec le séparateur décimal de la langue, et tous les textes des écrans RPS sont traduits.

### Plans de prévention, permis de feu et accidents

* Le clonage d'un plan de prévention ou d'un permis de feu avec une seule ressource liée ne finit plus en erreur fatale, ni le clonage d'un accident.
* Plus d'avertissement PHP à la génération d'un permis de feu, et les intervenants extérieurs ne sont plus écrasés.
* Les pictogrammes des protections s'affichent sur la fiche d'un plan en brouillon.
* Dans l'application mobile, un e-mail de demande de signature qui ne part pas affiche sa cause en clair.

### Outils et divers

* Une archive d'import refusée est arrêtée proprement, et ses fichiers extraits sont supprimés.
* Le menu PAPRIPACT reste sur le kanban quand le projet du document unique change.
* Les règles de DigiAI ne s'appliquent qu'à sa page, et le libellé des catégories de danger y est retrouvé.
* L'affichage légal lit les horaires sans après-midi sans avertissement.
* Le manifeste de l'application mobile n'est servi que sur les pages publiques des tickets.

### Compatibilité

* Digirisk fonctionne dans une racine alternative à `custom/` : liens, chemins sur le disque, synthèse publique, fiche ticket, constantes des modèles ODT et appels JavaScript.

## Testez les nouveautés maintenant

**Allez tester les causeries sécurité dans l'application mobile sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.7.0](https://github.com/Evarisk/Digirisk/compare/23.7.0...23.8.0) et 23.8.0

* [#5784] [SafetyTalk] fix: causeries sans erreur fatale avec DoliLetter absent ou antérieur à 23.2.0 [`c42530c2`](https://github.com/Evarisk/Digirisk/commit/c42530c2)
* [#5779] [PWA] add: personnes de la diffusion et leurs documents sur l'écran du plan de prévention [`62c1716d`](https://github.com/Evarisk/Digirisk/commit/62c1716d)
* [#5778] [PreventionPlan] add: téléphone et email de la diffusion réglés séparément [`ed841dbd`](https://github.com/Evarisk/Digirisk/commit/ed841dbd)
* [#5776] [PWA] fix: message clair quand l'email de demande de signature ne part pas [`0363b670`](https://github.com/Evarisk/Digirisk/commit/0363b670)
* [#5775] [FirePermit] fix: warnings PHP a la generation du document, intervenants exterieurs ecrases [`5bb6793c`](https://github.com/Evarisk/Digirisk/commit/5bb6793c)
* [#5773] [FirePermit][PreventionPlan] fix: clonage en erreur fatale avec une seule ressource liee [`17aa7c28`](https://github.com/Evarisk/Digirisk/commit/17aa7c28)
* [#5771] [Risk] fix: blank Spread tab of a risk, its metadata loads the risk library [`3c485bf5`](https://github.com/Evarisk/Digirisk/commit/3c485bf5)
* [#5769] [Tools] fix: build the temp dir of a rejected import from the always defined upload dir [`a78b9689`](https://github.com/Evarisk/Digirisk/commit/a78b9689)
* [#5769] [Tools] fix: also drop the files extracted from a rejected import archive [`be80cafb`](https://github.com/Evarisk/Digirisk/commit/be80cafb)
* [#5769] [Tools] fix: stop an import whose archive does not hold the expected JSON [`c266580a`](https://github.com/Evarisk/Digirisk/commit/c266580a)
* [#5767] [Medias] fix: create the large and medium thumbs of uploaded files with saturne_vignette [`f3bd4e8b`](https://github.com/Evarisk/Digirisk/commit/f3bd4e8b)
* [#5765] [ActionPlan] fix: keep the PAPRIPACT menu on the Kanban when the DU project changes [`a852d37b`](https://github.com/Evarisk/Digirisk/commit/a852d37b)
* [#5762] [JS] chore: rebuild digiriskdolibarr.min.js [`2bdd1392`](https://github.com/Evarisk/Digirisk/commit/2bdd1392)
* [#5762] [JS] chore: rebuild digiriskdolibarr.min.js [`3bcdb970`](https://github.com/Evarisk/Digirisk/commit/3bcdb970)
* [#5762] [Ticket] fix: only the board shown is built with the kanban page [`78e60eb7`](https://github.com/Evarisk/Digirisk/commit/78e60eb7)
* [#5762] [Ticket] fix: users and tags of the kanban cards printed once for the page [`54073cfd`](https://github.com/Evarisk/Digirisk/commit/54073cfd)
* [#5760] [Ticket] fix: blank ticket card without the delete right (saturne_show_documents got null) [`b164521c`](https://github.com/Evarisk/Digirisk/commit/b164521c)
* [#5758] [Media] fix: renforcement de la validation des types de médias à l'upload [`807fb5de`](https://github.com/Evarisk/Digirisk/commit/807fb5de)
* [#5753] [Risk] fix: les photos des modales d'évaluation se chargent à leur ouverture [`c884257f`](https://github.com/Evarisk/Digirisk/commit/c884257f)
* [#5751] [Ticket] feat: quick close a ticket from its state in the list of the registers [`82403a62`](https://github.com/Evarisk/Digirisk/commit/82403a62)
* [#5747] [Ticket] feat: register list with the Digirisk theme and without the left menu [`17081f80`](https://github.com/Evarisk/Digirisk/commit/17081f80)
* [#5748] [Ticket] feat: quick close a ticket from the percentage of its kanban card [`209f96a2`](https://github.com/Evarisk/Digirisk/commit/209f96a2)
* [#5741] [RiskAssessmentDocument] fix: l'archive ZIP du DU ne génère plus de fiches pour les GP/UT d'une autre entité [`4639f0b9`](https://github.com/Evarisk/Digirisk/commit/4639f0b9)
* [#5740] [RiskAssessmentDocument] fix: les PDF des fiches GP/UT sont copiés dans l'archive ZIP du DU [`4d0dfb34`](https://github.com/Evarisk/Digirisk/commit/4d0dfb34)
* [#5739] [Document] fix: les risques hérités ne sortent plus deux fois avec les modèles hérités et complet [`81fea9e3`](https://github.com/Evarisk/Digirisk/commit/81fea9e3)
* [#5736] [Document] fix: les risques partagés reviennent dans les fiches GP/UT [`01004ca3`](https://github.com/Evarisk/Digirisk/commit/01004ca3)
* [#5708] [ListingRisks] fix: specimen drawn on the active standard instead of an empty object [`d009ab5b`](https://github.com/Evarisk/Digirisk/commit/d009ab5b)
* [#5708] [Documents] fix: trigger description no longer fatal when the parent class is elsewhere or missing [`d59a4c23`](https://github.com/Evarisk/Digirisk/commit/d59a4c23)
* [#5732] [CI] ci: DoliLetter scanned by PHPStan [`5be93f48`](https://github.com/Evarisk/Digirisk/commit/5be93f48)
* [#5732] [Project] feat: open a safety talk cycle or talk in the mobile application from its card [`729a9224`](https://github.com/Evarisk/Digirisk/commit/729a9224)
* [#5732] [SafetyTalk] feat: safety talk cycles and talks diffused by DoliLetter in the mobile application [`4a7de17b`](https://github.com/Evarisk/Digirisk/commit/4a7de17b)
* [#5732] [PDF] feat: planning of a safety talk cycle with QR codes and short links [`f2034aa7`](https://github.com/Evarisk/Digirisk/commit/f2034aa7)
* [#5729] [PWA] fix: ticket manifest only on the public ticket pages [`609475e8`](https://github.com/Evarisk/Digirisk/commit/609475e8)
* [#5727] [RPS] feat: qualify psychosocial risks from the work unit risk list [`055de2ff`](https://github.com/Evarisk/Digirisk/commit/055de2ff)
* [#5407] [Papripact] feat: task label, narrower columns, category picto and risk description in the A3 document [`e9bc7f43`](https://github.com/Evarisk/Digirisk/commit/e9bc7f43)
* [#5689] [DigiAI] fix: danger category label indexed by picto name [`eee2f374`](https://github.com/Evarisk/Digirisk/commit/eee2f374)
* [#5722] [Users] fix: Digirisk users page in the transverse mode of Multicompany [`96479c1a`](https://github.com/Evarisk/Digirisk/commit/96479c1a)
* [#5720] [Ticket] fix: assignable users of the entity in the transverse mode of Multicompany [`569e65b4`](https://github.com/Evarisk/Digirisk/commit/569e65b4)
* [#5717] [Ticket] fix: users can be assigned on the native ticket card without a default user group [`d9f7d162`](https://github.com/Evarisk/Digirisk/commit/d9f7d162)
* [#5716] [Ticket] fix: a card dropped in another status column of the kanban saves the status of that ticket [`754b3054`](https://github.com/Evarisk/Digirisk/commit/754b3054)
* [#5711] [Ticket] feat: live preview of the public interface on the ticket category configuration [`d39fd06b`](https://github.com/Evarisk/Digirisk/commit/d39fd06b)
* [#5710] [Ticket] fix: email and photos missing from the public interface of the entities created later [`a84eefd7`](https://github.com/Evarisk/Digirisk/commit/a84eefd7)
* [#5709] [Ticket] fix: "Required" option for the photos / attachments of the public interface [`b029ef86`](https://github.com/Evarisk/Digirisk/commit/b029ef86)
* [Medias] fix: photos des risques en création propres à la session, dossiers lus par les fiches [`976ef5f4`](https://github.com/Evarisk/Digirisk/commit/976ef5f4)
* [#5705] [RiskList] fix: make the assessment comment easier to read [`ffd01059`](https://github.com/Evarisk/Digirisk/commit/ffd01059)
* [#5704] [Module] fix: classpath de getElementProperties relatif à DOL_DOCUMENT_ROOT [`a3182d7a`](https://github.com/Evarisk/Digirisk/commit/a3182d7a)
* [#5702] [Affichage légal] fix: horaires sans après-midi lus sans avertissement [`d9b07279`](https://github.com/Evarisk/Digirisk/commit/d9b07279)
* [#5700] [Documents] fix: constantes des modèles ODT écrites depuis la vraie racine du module [`2c00897e`](https://github.com/Evarisk/Digirisk/commit/2c00897e)
* [#5682] [PreventionPlan] fix: pictogrammes des protections sur la fiche d'un plan en brouillon [`e9368658`](https://github.com/Evarisk/Digirisk/commit/e9368658)
* [#5682] [JS] fix: URL du module lue côté serveur au lieu de '/custom/digiriskdolibarr' [`03a98660`](https://github.com/Evarisk/Digirisk/commit/03a98660)
* [#5682] [Module] fix: chemins relatifs confiés au cœur sans custom/ [`4594b2fc`](https://github.com/Evarisk/Digirisk/commit/4594b2fc)
* [#5682] [Module] fix: chemins disque construits avec DOL_DOCUMENT_ROOT . '/custom/…' [`cac8ac7a`](https://github.com/Evarisk/Digirisk/commit/cac8ac7a)
* [#5682] [Module] fix: dol_buildpath('/custom/…') sans custom/ [`e1367cea`](https://github.com/Evarisk/Digirisk/commit/e1367cea)
* [#5682] [Module] fix: URL construites avec DOL_URL_ROOT . '/custom/…' [`8ac9b875`](https://github.com/Evarisk/Digirisk/commit/8ac9b875)
* [#5697] [Theme] feat: modern theme on the native tabs of tasks and tickets [`583501c3`](https://github.com/Evarisk/Digirisk/commit/583501c3)
* [#5697] [Task] feat: digirisk task card opened by every task link [`aa8403a3`](https://github.com/Evarisk/Digirisk/commit/aa8403a3)
* [#5695] [Risque] fix: update risk description on danger change after edit [`035395fc`](https://github.com/Evarisk/Digirisk/commit/035395fc)
* [#5693] [Accident] fix: après le clonage, ne retirer que la clé createfromclone de context [`6af36f93`](https://github.com/Evarisk/Digirisk/commit/6af36f93)
* [#5693] [Accident] fix: context de clonage en tableau, comme l'attend Dolibarr [`74f5f9be`](https://github.com/Evarisk/Digirisk/commit/74f5f9be)
* [#5672] [DigiAI] fix: DigiAI rules scoped to the DigiAI page [`b936a693`](https://github.com/Evarisk/Digirisk/commit/b936a693)
* [#5691] [RiskList] fix: assessment badge no longer covered by its photo [`e4879c69`](https://github.com/Evarisk/Digirisk/commit/e4879c69)
* [#5687] [DigiriskElement] feat: people percentage of every GP/UT from the GP/UT setup [`80947b62`](https://github.com/Evarisk/Digirisk/commit/80947b62)
* [#5685] [RPS] fix: moyennes RPS au séparateur décimal de la langue [`2c91fb94`](https://github.com/Evarisk/Digirisk/commit/2c91fb94)
* [#5681] [RPS] fix: textes des écrans RPS passés par les traductions [`22becce3`](https://github.com/Evarisk/Digirisk/commit/22becce3)
* [#5682] [Module] fix: synthèse publique et fiche ticket sans custom/ en dur [`3ddd85ff`](https://github.com/Evarisk/Digirisk/commit/3ddd85ff)
