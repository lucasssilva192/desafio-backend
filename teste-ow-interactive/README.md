para podermos rodar o projeto, é essencial seguir os passos abaixo de forma linear:
1 - criar um banco de dados nomeado de 'teste_ow_interactive';
2 - abrir o caminho do projeto e executar o comando 'composer install';
3 - executar o comando 'php artisan generate:key';
4 - executar o comando 'php artisan migrate';
5 - executar o comando 'php artisan db:seed --class=UserSeeder';
6 - executar o comando 'php artisan db:seed --class=MovementSeeder ';
7 - executar o comando 'php artisan serve'.
