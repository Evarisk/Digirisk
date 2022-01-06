cd ../../workflow/phpdocumentor/phpdocumentor
composer install
cd ../../bin
./phpdoc -d "..\..\class" -t "..\..\docs"
sleep 4151
