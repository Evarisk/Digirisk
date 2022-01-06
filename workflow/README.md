## **PHPCS :**

pour lancer le code sniffer avec le rule set de wordpress :

pour voir les ruleset existants :
phpcs -i
phpcs --config-show

dans vendor/bin :
git clone https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards.git wpcs
git checkout master dans wpcs
phpcs --config-set installed_paths wpcs
phpcs --config-set default_standard WordPress  
phpcbf ../../class   (ou le dossier qu’on veut)

pour récupérer celui de dolibarr :
copier dev/setup/codesniffer dans vendor/bin/wpcs

phpcbf ../../class   (ou le dossier qu’on veut)
