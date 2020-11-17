
mkdir /home/workadventure
cd /home/workadventure
git clone https://github.com/thecodingmachine/workadventure.git app

To change ports of server, edit section ports into file *docker-compose.yaml*:
   - "81:80"
   - "444:443"
   

To launch workadventure servers:

docker-compose up


To access application:

vi /etc/hosts to add

IP.OF.SERVER.WORKADVENTURE workadventure.localhost play.workadventure.localhost api.workadventure.localhost maps.workadventure.localhost
IP.OF.SERVER.WORKADVENTURE workadventure.dolibarr.org play.workadventure.dolibarr.org api.workadventure.dolibarr.org maps.workadventure.dolibarr.org


WARNING: The DEBUG_MODE variable is not set. Defaulting to a blank string.
WARNING: The JITSI_URL variable is not set. Defaulting to a blank string.
WARNING: The JITSI_PRIVATE_MODE variable is not set. Defaulting to a blank string.
WARNING: The SECRET_JITSI_KEY variable is not set. Defaulting to a blank string.
WARNING: The ADMIN_API_TOKEN variable is not set. Defaulting to a blank string.
WARNING: The JITSI_ISS variable is not set. Defaulting to a blank string.

Then call URL:

http://play.workadventure.localhost
http://play.workadventure.localhost:8080

and to use your own map. Copy it into a directory */home/workadventure/app/maps/mymap*

http://play.workadventure.localhost/_/global/maps.workadventure.localhost/mymap/map.json
or
http://play.workadventure.dolibarr.org/_/global/maps.workadventure.dolibarr.org/mymap/map.json







   