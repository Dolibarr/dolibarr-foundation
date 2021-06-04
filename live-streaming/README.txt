Tutorial to make streaming for the Dolibarr foundation with both 
Powerpoint + Screen capture + Camera on speaker


= Prerequisites =

Having a Ubuntu desktop station with 2 screens: Screen A (with Camera above, the screen to share) and Screen B (to control the recording and tchat)


= Setup of OBS (to do once) =

1) Install OBS Studio and launch OBS studio on the second screen
2) Create Scene - Camera only + micro
3) Create Scene - Screen + Camera in thumb (so 2 sources) + micro
4) Create Scene - Screen only + micro
5) Activer le mode "Toujours au dessus" (menu Fichier)
6) Tester que la réactivité de l'image sur les écrans est immédiate (les lèvres bougent en même temps que le son), si non,
   cliquez sur les propriétés du périphériques et mettez un autre format Video comme par exemple "YV12 (Emulated)" au lieu de "YUYV".


= Create the Scheduled stream (2 week before each event) =

On second screen B, launch a browser to create a scheduled or live stream on a platform and get the Stream key.
Set the Stream latency to "Low-latency" (for a better ) and Enable DVR to "Yes" (so you can record).
Example with YouTube:
Go on https://studio.youtube.com, click on "Create - Go live" then "Schedule stream" (you can reuse a past stream as template)


= Preparing screen and slides to show (10mn before event) =

On first screen A, launch Chrome browser with 2 tabs only: Dolibarr web app and the Slides to show in Drive. 
Open the tab with the Web app and Click F11 to switch in full screen mode.
Then switch to second tab with CTRL+TAB
Click on start presentation os Slides then Click on the Cross "Exit full screen" (you leave the Fullscreen mode of Google slide 
but keep the full mode of browser).
You can now switch with CTRL+TAB between web application and slides without loosing the full screen.

Launch youtube studio on a tab, launch the stream in "Live control view" (so you will see all questions into the chat).

Click on "GO LIVE" !


= Start session =

Launch OBS on screen B, Click on "Mode Studio", Click on "Start streaming" => This will make the button "Go Live" available on 
youtube live control page.
Click on "Go Live"...

Click on "Scene" + "Transition" to switch between Camera and screen during recording.

Click on "Stop streaming" when recording is finished. 

