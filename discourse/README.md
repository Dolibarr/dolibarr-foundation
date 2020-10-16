
== To jump into docker env

/var/discourse launcher enter web_only



== Change virtual host names used by apache to redirect to discourse:

Check the proxy into /etc/apache2/sites-enabled 


Change the config file for discourse to manage multi-domains:

 vi /var/discourse/container/web_only.yaml
 
Then take this into account

 cd /var/discourse
 sudo ./launcher stop web_only
 sudo ./launcher rebuild web_only



More info on https://wiki.dolibarr.org/index.php/User:Jtraulle/DiscourseMigration/Implementation

