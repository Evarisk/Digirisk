# [Digirisk] [23.3.0] - Digirisk parle huit langues

Description : Cette version ouvre Digirisk à l'international : l'anglais est enfin complet et sept nouvelles langues font leur entrée. Elle ajoute aussi les cinq catégories de risques de la pénibilité au sens du Code du travail, un onglet « Produits/Services » sur les groupements et unités de travail, et corrige une erreur fatale sur la liste des risques.

**Cette version demande Saturne 23.1.1 ou supérieur.**

## Nouvelles fonctionnalités et innovations

### Traductions

* Digirisk est désormais disponible en **huit langues** : français, anglais, allemand, espagnol, italien, néerlandais, polonais, portugais et roumain.
* La traduction anglaise, qui ne couvrait que 560 clés sur 1998, est **complète**. Les pages affichées en anglais laissaient jusqu'ici apparaître des libellés en français, ou pire, le nom technique de la clé.

### Pénibilité

* Cinq **catégories de risques liées à la pénibilité** rejoignent la liste des dangers, avec leur pictogramme : postures pénibles, activités exercées en milieu hyperbare, travail de nuit, travail en équipes successives alternantes et travail répétitif.
* Elles sont reconnues par l'analyse d'image : photographier une situation de travail propose directement la bonne catégorie.
* Le survol d'une de ces catégories rappelle son cadre réglementaire : article de référence, éligibilité au C2P, critère et seuil d'exposition.

![Les vingt-sept catégories de dangers, dont les cinq nouvelles liées à la pénibilité](https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/assets/release-23.3.0/.shots/23.3.0-categories-penibilite.png)

### Produits et services par unité de travail

* Un onglet **« Produits/Services »** apparaît sur la fiche d'un groupement ou d'une unité de travail. Il permet d'y rattacher les produits et services du catalogue Dolibarr — machines, équipements, consommables — et de garder la trace de ce qui est réellement présent sur le poste.
* Un produit déjà rattaché n'est pas ajouté deux fois : sa référence est rappelée dans le message d'avertissement.

![L'onglet Produits | Services d'une unité de travail](https://raw.githubusercontent.com/nicolas-eoxia/digiriskdolibarr/assets/release-23.3.0/.shots/23.3.0-produits-services.png)

## Améliorations & corrections

### Liste des risques

* La liste des risques ne tombe plus en erreur fatale quand les catégories de risques sont activées. Le filtre par catégories interrogeait un type de catégorie inexistant ; sous PHP 8, la page devenait inaccessible pour tout utilisateur ayant le droit de lire les catégories.
* Les catégories affichées sur une ligne de risque remontent de nouveau : elles étaient cherchées sous le même mauvais code et ressortaient toujours vides.

### Modale d'évaluation

* Les zones de description du risque et de l'évaluation sont de nouveau **redimensionnables**. Leur hauteur était figée à 48 px, ce qui coupait le texte long sans permettre de l'agrandir.

### Divers

* Suppression d'un script de débogage commité par erreur à la racine du module.
* Les feuilles de style livrées sont de nouveau **minifiées** : une recompilation manuelle avait publié la version non compressée, 55 Ko plus lourde à charger sur chaque page.

## Comparaison des versions [23.2.2](https://github.com/Evarisk/Digirisk/compare/23.2.2...23.3.0) et 23.3.0
