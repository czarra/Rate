# README #

### Repo of Rates ###
* I changed the script to the symfony command, in my opinion this is a more elegant solution
* (it would be possible to add a location where files for processing will be sent and the results will be saved in separate files)

## steps
### to set up in env.local
* EXCHANGE_API_KEY=""
### .env
* copy env.dist to .env

### run command:
* docker-compose up -d --build  or sudo docker-compose up -d --build
* sudo docker-compose exec php /bin/bash
* composer install
* bin/console app:commission input.txt

* input.txt - File with correct data


### run tests 
* sudo docker-compose exec php /bin/bash
* bin/phpunit
