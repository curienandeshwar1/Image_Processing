#!/bin/bash
## This file is used for initializing the launched AWS instance

sudo apt-get -y update
sudo apt-get -y install apache2 php php-gd php7.2-xml php-curl python3-pip python3-dev python3-setuptools unzip zip
sudo pip install boto3
sudo pip install Pillow

#sed 's/;extension=gd2/extension=gd2/' -i /etc/php/7.2/apache2/php.ini

sudo git clone git@github.com:illinoistech-itm/cnandeshwar.git
sudo cp /cnandeshwar/itmo-544/mp2/index.php /var/www/html
sudo cp /cnandeshwar/itmo-544/mp2/submit.php /var/www/html
sudo cp /cnandeshwar/itmo-544/mp2/gallery.php /var/www/html


sudo systemctl enable apache2
sudo systemctl start apache2

# installing imagick for thumbnail
#https://gist.github.com/jackmu95/e17c225b7eb4baa9485ecec91b15477e
#sudo apt-get -y install php7.2-dev pkg-config libmagickwand-dev 
#cd /tmp
#wget https://pecl.php.net/get/imagick-3.4.3.tgz
#tar xvzf imagick-3.4.3.tgz
#cd imagick-3.4.3
#phpize
#./configure
#make install
#rm -rf /tmp/imagick-3.4.3*
#echo extension=imagick.so >> /etc/php/7.2/cli/php.ini
#echo extension=imagick.so >> /etc/php/7.2/apache2/php.ini
#sudo systemctl restart apache2


cd /home/ubuntu

sudo -u ubuntu php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

sudo -u ubuntu php -r "if (hash_file('sha384', 'composer-setup.php') === 'a5c698ffe4b8e849a443b120cd5ba38043260d5c4023dbf93e1558871f1f07f58274fc6f4c93bcfd858c6bd0775cd8d1') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"

sudo -u ubuntu php composer-setup.php

sudo -u ubuntu php -r "unlink('composer-setup.php');"

sudo -u ubuntu php -d memory_limit=-1 composer.phar require aws/aws-sdk-php

sudo -u ubuntu mysql --host can-database.cjucmibmmgiu.us-east-1.rds.amazonaws.com -u master < create-schema.sql

