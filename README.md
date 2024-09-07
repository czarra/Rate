#steps 

#to set up in env.local
EXCHANGE_API_KEY=""
copy env.dist to .env

#run command:
docker-compose up -d --build  or sudo docker-compose up -d --build
sudo docker-compose exec php /bin/bash
composer install
bin/console app:commission input.txt

input.txt - File with correct data


#run tests 
bin/phpunit
