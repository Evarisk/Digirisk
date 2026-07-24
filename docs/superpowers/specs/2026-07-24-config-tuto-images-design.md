# Images « tuto » sur toutes les configurations du module — Design

Issue : [Evarisk/Digirisk#4936](https://github.com/Evarisk/Digirisk/issues/4936)
Suite de : [#4750](https://github.com/Evarisk/Digirisk/issues/4750)
Date : 2026-07-24

## Objectif

Généraliser à toutes les pages de configuration Digirisk la colonne *Image* introduite dans le plan
d'action : pour chaque réglage, une capture de l'endroit de l'interface concerné, agrandissable au
clic.

## Périmètre

Retenu : Kanban tickets, Registre SST, Réglages, Document unique, Organisation GP/UT, Plan de
prévention, Permis de feu, Accident, Vigilance Météo-France, DigiAI — soit une soixantaine de
réglages.

Exclus :

- les 76 triggers de l'onglet Événements : événements Dolibarr automatiques, sans zone d'interface
  dédiée ;
- les blocs de numérotation et de modèles de documents : templates Saturne ;
- les onglets PWA, Documents et À propos : pages Saturne.

## Contenu des images

L'image montre l'endroit de l'interface concerné par le réglage, zone encadrée en orange :

- la zone qui **déclenche** l'événement pour les bascules de logging ;
- la zone où l'**effet** se voit pour les autres réglages.

## Architecture

Deux fonctions dans `lib/digiriskdolibarr.lib.php`, en remplacement du bloc inline de `#4750` :

| Fonction | Rôle |
|----------|------|
| `digiriskdolibarr_tuto_image($page, $slug, $alt)` | Renvoie la balise `<img class="config-tuto">`, chaîne vide si le fichier manque |
| `digiriskdolibarr_tuto_overlay()` | Imprime une fois par page le CSS, le conteneur et le JS de l'agrandissement |

`digiriskdolibarr_tuto_overlay()` est idempotente : les appels suivants ne réimpriment rien, ce qui
permet de l'appeler après chaque tableau sans dupliquer l'overlay.

Images : `img/config_tuto/<page>/<slug>.png`. La classe CSS est `config-tuto` ; l'état ouvert de
l'overlay est porté par `opened` et non `visible`, cette dernière étant déjà définie par le thème
eldy (`div.visible { display: block }`).

Clé de traduction commune : `TutoImage`.

## Livraison

- **Lot 1** — helper, migration du plan d'action, Kanban tickets.
- **Lot 2** — Registre SST, Réglages.
- **Lot 3** — Document unique, Organisation, Plan de prévention, Permis de feu, Accident, Météo,
  DigiAI.

## Production des captures

Script Playwright par page, avec dans tous les cas :

- blocage des requêtes POST de sauvegarde, pour ne modifier aucune donnée ;
- remplacement des données réelles (utilisateurs, sociétés, sujets de tickets) par des valeurs
  fictives, les images étant distribuées publiquement ;
- surlignage de la zone concernée en `box-shadow: 0 0 0 3px #ff9800` — pas de `background`, qui
  écraserait celui des éléments colorés.

## Vérification

`tuto-verify.js` parcourt les pages listées et contrôle, pour chacune : nombre de vignettes, chargement
effectif (`naturalWidth`), présence de `alt`, nombre de colonnes du tableau, unicité de l'overlay dans
le DOM, ouverture au clic et fermeture au clic et à l'Échap.

## Portée retenue au sein d'une page

La colonne *Image* est ajoutée aux **tableaux de réglages** (bascules et valeurs). Les listes de
gestion (catégories de tickets, extrafields), les formulaires de saisie d'URL et les blocs de
numérotation gardent leur structure : ce ne sont pas des réglages à illustrer.

Un réglage sans zone d'interface observable garde une cellule vide plutôt qu'une capture
approximative. C'est le cas des envois d'e-mails (Registre SST) et du captcha, ce dernier
n'apparaissant sur les pages publiques qu'une fois activé.

## Points ouverts

`DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_DEADLINE` est proposée dans la configuration du Kanban tickets
mais n'est déclenchée nulle part dans le code : sa ligne reste sans image tant que le réglage n'a pas
d'effet.
