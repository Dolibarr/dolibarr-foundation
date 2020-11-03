
mkdir /home/workadventure
cd /home/workadventure
git clone https://github.com/thecodingmachine/workadventure.git app

Edit section ports into:
   - "81:80"
   - "444:443"
   
docker-compose up

vi /etc/hosts to add workadventure.localhost with IP of server

https://play.workadventure.localhost/_/global/eldy.github.io/dolibarr-adventure/map.json




   