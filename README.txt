ABOUT

eZUpgrade is stand-alone application (not an eZ Publish extension) automating
the process of upgrading an eZ Publish installation.

Using eZUpgrade is as easy as checking it out on the server where the upgraded
installation will reside, inputting some configuration settings, and running
ezupgrade from the command line.


DISCLAIMER

eZUpgrade makes a point of doing as little as possible to the original (source)
installation. Still, the application is delivered as-is without any warranties
of any kind.

You are strongly urged to backup your entire source installation (files and
database(s)) before making use of this application. 


SUPPORTED VERSIONS

eZUpgrade is built to be flexible when it comes to adding support for new
eZ Publish versions. Depending on the complexity of the change from one eZP
version to another, adding support could be as easy as adding some
configuration settings to settings/ezupgrade.ini. For more complex changes,
one might need to create some custome upgrade functions.


REQUIREMENTS

* eZUpgrade is dependent upon eZCopy (http://projects.ez.no/ezcopy) and its
  requirements.


HOW IT WORKS

The old eZ Publish installation can be located either on the same server as
the upgraded installation, or on a remote server. If the latter is the case,
eZUpgrade will make use of eZCopy to copy the old installation to the new
server before starting the upgrade procedure.

eZUpgrade then performs the following steps:

1. Downloads and unpacks the distro of the version we want to upgrade to.

2. Copies files (var/, settings/ and extension/) from the old to the 
   new installation, prompting when overrides are needed.
   
3. Copies the databases used by the old installation and updates the 
   database connections in the new installation to the db copies.

4. Performs the upgrade steps needed between the version we are upgrading
   from and the version we are upgrading to. This includes sql updates,
   upgrade scripts and any other upgrade actions that can be performed
   automatically. If there are any upgrade actions that need to be performed
   manually, you will be informed.
   
5. Grants the database user access to the new database(s).


USAGE

From the shell prompt, where eZUpgrade is located, run:

php ezupgrade [account_username]



IMPORTANT NOTE ABOUT UPGRADING FROM VERSION 3.x TO 4.x

Since 3-series of eZ Publish is run on php4 the upgrading process is a little more tricky than upgrading from 4.x.y.
In order to fix this we have added paths to php4 and php5 in ezcopy.ini. This means that you need to upgrade on a server/machine that have both this or do it in cycles. 

The way we have upgraded is doing it in cycles. Here is one of our example:

FROM		TO		
3.5.0		3.8.7
3.8.7		3.9.3
3.9.3		3.10.1
3.10.1		4.0.3
4.0.3		4.5.0

Tip! Instead of upgrading on the server, you could upgrade on your computer (if its unix based). Download a webserver that have both php4 and php5 set the paths and run the upgrade.
After upgradring copy the last folder to the webserver and add the database.
