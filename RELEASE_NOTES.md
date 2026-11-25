# [Digirisk] [23.9.1] - Signature des tickets publics et partage des informations plus sûrs

Description : la déclaration publique d'un ticket n'enregistre plus qu'une vraie signature tracée, et la page de partage des informations ne touche plus à l'utilisateur connecté. **Mise à jour recommandée pour toutes les instances.**

**Cette version demande Saturne 23.9.2 ou supérieur.**

## Améliorations & corrections

### Tickets publics : contrôles renforcés

* La signature de la déclaration publique d'un ticket n'est enregistrée que si c'est une signature tracée sur la page. Sinon, la déclaration est refusée avec un message clair et le formulaire est réaffiché.

### Partage des informations

* L'affichage des référents harcèlement et CSE ne modifie plus l'utilisateur connecté pour la suite de la page.

### Saturne

* Digirisk demande désormais Saturne 23.9.2, qui renforce la page publique de signature utilisée par les tickets, les plans de prévention et les permis de feu.

## Testez les nouveautés maintenant

**Allez tester la déclaration d'un ticket depuis la page publique sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.9.0](https://github.com/Evarisk/Digirisk/compare/23.9.0...23.9.1) et 23.9.1

* [Ticket] fix: signature de la page publique enregistrée seulement si c'est une image [`00676d89`](https://github.com/Evarisk/Digirisk/commit/00676d89)
* [InformationsSharing] fix: référents chargés dans un objet User dédié [`5f03cb88`](https://github.com/Evarisk/Digirisk/commit/5f03cb88)
* [Mod] chore: Saturne 23.9.2 exigé [`f622dc16`](https://github.com/Evarisk/Digirisk/commit/f622dc16)
