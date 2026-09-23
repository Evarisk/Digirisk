# [Digirisk] [23.4.0] - Registres géolocalisés et documents réparés

Description : Cette version ajoute un dictionnaire des lieux avec géolocalisation dans les registres, les tableaux RPS du document unique, et la méthode ITAMAMI dans l'analyse d'accident. Elle complète les catégories de risques de la pénibilité et rétablit la génération de documents sur Dolibarr 24, qui était complètement bloquée. Une série de correctifs touche les modèles PDF, la corbeille, les signalisations et la déclaration publique de ticket.

**Cette version demande Saturne 23.2.0 ou supérieur.**

## Nouvelles fonctionnalités et innovations

### Registres

* Un **dictionnaire des lieux** alimente désormais les registres, avec géolocalisation. La liste des lieux se filtre par groupement ou unité de travail.
* Mise en forme des champs du registre adaptée au téléphone.

### Document unique

* **Tableaux RPS** dans le document unique.
* Les quatre catégories de risques de la pénibilité restantes — agents chimiques dangereux, températures extrêmes, bruit, manutention — complètent la liste, avec leurs pictogrammes et une réorganisation de l'ensemble. Leurs quatre clés réglementaires sont traduites dans les sept langues.

### Analyse d'accident

* Intégration de la **méthode ITAMAMI**.
* Pied de page sur chaque page du modèle PDF.

### Rapport d'évolution

* Le rapport d'audit devient le **rapport d'évolution**, dans l'interface comme dans le modèle ODT.

### Configuration

* Les réglages du bloc « Actions » sont illustrés par une capture du document produit : on voit ce que le réglage change avant de le toucher.

## Améliorations & corrections

### Génération de documents

* **Dolibarr 24 : la génération de documents est réparée.** Le cœur de Dolibarr 24 refuse tout modèle livré avec le module et ne transmet plus ses paramètres au générateur : aucune génération n'aboutissait. Corrigé ici et dans Saturne 23.2.0.
* Le document unique n'enchaîne plus sur l'archivage après un échec de génération.
* Correction des avertissements PHP à la génération, sur les clés de risques et la ressource « Responsable ».
* Le listing des risques affiche le responsable de la tâche. Une tâche sans référence n'en écrit plus une vide en ODT et en PDF.
* Séparateur de budget orphelin et date de l'évaluation écrasée dans les modèles ODT.

### Documents de ticket

* Boucle infinie sur les pieds de page d'un document de plusieurs pages.
* Message tronqué, alignements et pagination du modèle PDF ; la hauteur d'une ligne est mesurée avec la police de sa cellule, et une ligne plus haute qu'une page ne casse plus la mise en page.

### Groupements et unités de travail

* La corbeille ne s'affichait plus, et sa constante pouvait se graver à `-1`.
* Le glisser-déposer était perdu quand l'URL n'avait pas de query string.
* La fiche interrogeait la base avec un objet amputé de ses colonnes.
* La liste des risques occupe maintenant toute la largeur disponible.
* Le filtre passé à `selectDigiriskElementList()` était ignoré.

### Risques et signalisations

* La grille des dangers n'est plus rognée par la modale, et son infobulle est enrichie hors des listes.
* `getDangerCategoryName()` redevient un accesseur de nom : il renvoyait le bloc réglementaire dans les PDF, les ODT et les fiches.
* Les signalisations supprimées disparaissent de la liste partagée, dont les avertissements PHP 8 sont corrigés.
* Les tâches créées sans référence par l'endpoint DigiAI sont rattrapées.
* Saisie du pourcentage d'avancement à la modification d'une tâche.

### Tickets

* Une catégorie sans modèle d'e-mail rendait la **déclaration publique fatale**.
* Sujet vide sur la notification de nouveau registre.
* Le bouton des champs personnalisés déjà générés ouvre leur configuration.
* Avertissements PHP 8 sur la configuration de catégorie.

### Divers

* Le menu PAPRIPACT reste affiché sur la vue Kanban du plan d'action.
* Une date d'élection vide était enregistrée en `--` et produisait des avertissements PHP.
* La configuration « Social » est enregistrée avant la création d'un utilisateur.
* Erreur JavaScript `reading 'top'` sur toute page ouverte sans identifiant, et messages d'événement rétablis sur le rapport d'évolution.

### Qualité et intégration continue

* Digirisk a désormais sa propre chaîne qualité : `php -l`, parité des fichiers de langue et PHPStan, annotée sur la diff et bloquante sur les pull requests.
* Les assets sont compilés par la chaîne sass + esbuild du socle, dans un ordre déterministe, et vérifiés sur les pull requests.

## Comparaison des versions [23.3.0](https://github.com/Evarisk/Digirisk/compare/23.3.0...23.4.0) et 23.4.0
