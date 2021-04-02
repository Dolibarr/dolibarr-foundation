Files used for the Dolibarr Academy MOOC.

To edit a course:
-----------------
* install Opale (3.7 or 3.8) https://download.scenari.software/Opale/
* launch Opale - Atelier Opale.
* If course is not loaded already, import it with menu Import - select *.scar file.
* Edit the course.
* Export/Save the course into a .scar file.
 
To export the course for courses.dolibarr.org:
---------------------------------------------
* Generate the course from menu "Name of course - Course - Course_web.publi".
  - "Generate" format Publication SCORM mono SCO Aurora.
  - then "Generate" again for format SCORM (.zip file). Enter label (use same code than course) and code (use same code than course) !!! Previous "Generate" on must have been done then "Reveal", you will find the .zip file.
* Save the .zip  into the git repository of the Dolibarr foundation or send it to the Dolibarr foundation at contact@dolibarr.org.

To load the course into courses.dolibarr.org:
---------------------------------------------
* Connect with an admin account on https://courses.dolibarr.org
* Find the Course to edit (create one if it doesn't exists) and swith to edit mode wit "Turn Edit on". 
  Find the Topic/Section to edit (create one if it doesn't exist)
* Find the SCORM activity (create one if it doesn't exists, with name to "Accès au cours ....", Uncheck "Appareance - Display activity name" and set "Display course structure in player" to "Disabled" and in "Activity completion", set a minimal score and "Completed").
* Remove the zip file.
* Upload the new zip file.


