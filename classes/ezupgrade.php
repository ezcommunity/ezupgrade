<?php

/**
* File containing the eZUpgrade class
*
* @copyright Copyright (C) 2009-2011 Netmaking AS. All rights reserved.
* @license //autogentag//
* @version //autogentag//
*/

define('EZCOPY_APP_PATH', 'lib/ezcopy/');

include_once( "classes/upgradefunctions.php" );
include_once( "classes/requirements.php" );
include_once( "lib/ezcopy/classes/ezcopy.php" );

class eZUpgrade extends eZCopy
{
	var $upgradeData;
	var $upgradeVersionSettings;
	var $upgradeFromVersion;
	var $upgradeToVersion;
	var $versionList;
	var $manualAttentionNotificationList;
	
	// The full path to where the old installation is located.
	var $oldInstallationPath;
	
	function eZUpgrade()
	{
		// run constructor for parent class
		$this->eZCopy();
		
		$this->versionList 		= false;
		$this->manualAttentionNotificationList	= array( 'Remember to set up the cronjobs for the upgraded site.');
	}
	
	function run()
	{
		$this->log("\nINITIATING UPGRADE\n", 'heading');
		
		// prepare existing installation
		$this->prepareExistingInstallation();
		
		// fetch the version number of the current version from the database
		$this->fetchUpgradeFromVersion();
		
		// fetch the most recent version which holds upgrades further back than the version
		// we are upgrading from, and set it as the version we are upgrading to
		$this->fetchUpgradeToVersion();
		
		// fetch the settings of the version we are upgrading to
		$this->upgradeVersionSettings = $this->fetchVersionSettings($this->upgradeToVersion);
		
		// check requirements
		$this->checkRequirements();
		
		// check if the version we are upgrading to have upgrade containers down to the version we are upgrading from
		$this->checkUpgradeContainer();
		
		// perform pre-upgrade checks
		$this->preUpgradeChecks();
		
		// download and unpack distro
		//$this->downloadAndUnpackDistro();
		
		$this->prepareAndUnpackDistro();
		
		// copy files from old installation to new distro
		$this->copyFiles();
		
		// copy the database(s) of the old distro
		$this->copyDatabases();
		
		// alter db settings in new distro to point to the db copies
		$this->updateDBConnections();
		
		// perform upgrades
		$this->performUpgrades();
		
		// grant db users access to the new database(s)
		$this->grantAccessToNewDatabases();
		
		// perform post upgrade tasks
		$this->postUpgradeTasks();
		
		// warn if we need to do more upgrades
		$this->checkForFinalUpgrade();
		
		// print work which requires manual attention
		$this->manualAttentionNotice();
		
		$this->log("Upgrade from v. " . $this->upgradeFromVersion . " to v. " . $this->upgradeToVersion . " complete\n\n", 'ok');
	}
	
	function prepareExistingInstallation()
	{
		// if the existing installation is located at a remote location
		if($this->upgradeData['existing_install'] == 'remote')
		{
			$this->log("The existing installation is located remotely. We need to copy it to a local location.\n");
			$this->checkpoint( 'Copy installation from remote location' );
			
			// copy existing distro from current location (use /ezcopy)
			$this->ezcopy->actionDownloadAll($this->data['account_name']);
			
			// set the location of the locally downloaded copy
			$this->setOldInstallationPath($this->getCopyLocation());
		}
		
		// if the existing installation is located locally
		else
		{
			$this->log("The existing installation is located locally at " . $this->upgradeData['existing_install'] . "\n");
			
			// do a checkpoint
			$this->checkpoint( 'Use existing installation: ' . $this->upgradeData['existing_install'] );
			
			// set the location of the old installation
			$this->setOldInstallationPath($this->upgradeData['existing_install']);
			
			// We need to fetch the settings here. If the old installation was located remotely,
			// this would have been done as a part of the process of copying the
			// installation
			$this->selectAccount($this->upgradeData['account_name']);
			
			// tell ezcopy that we want the following actions to be performed locally, and where the base path is
			$this->setIsLocal(true, $this->upgradeData['existing_install']);
			
			// dump the database of the existing installation
			$this->dumpDatabase();
		}
	}
	
	function setOldInstallationPath($path)
	{
		$this->oldInstallationPath = $path;
	}
	
	function getOldInstallationPath()
	{
		return $this->oldInstallationPath;
	}
	function oldDatabaseCleanup()
	{
		$this->log("Deleted database dump from the old site ");
		$cmd = 'rm -rf ' . $this->upgradeData['existing_install']. $this->dbDumpDir;
		exec($cmd);
		$this->log("OK\n", 'ok');
	}
	function postUpgradeTasks()
	{
		$this->fixEzInstall($this->getNewDistroPathName());
		$this->oldDatabaseCleanup();
	}
	
	function getNewDistroPathName()
	{
		return $this->getNewDistroFolderName();
	}
	
	function checkRecommendedVersion()
	{
		// check if the version we are upgrading to is not recommended.
		if ( $this->cfg->hasSetting('ezupgrade', 'Upgrade_' . $this->upgradeToVersion, 'NotRecommendedVersion') )
		{
			if ( $this->cfg->getSetting('ezupgrade', 'Upgrade_' . $this->upgradeToVersion, 'NotRecommendedVersion') == 'true' )
			{
				$this->log( "This version is not recommende to upgrade to, please upgrade to the next version\n", 'critical' );
			}
		}
	}
	function preUpgradeChecks()
	{
		$this->log("Performing pre-upgrade checks ", 'heading');
		$this->checkRecommendedVersion();
		$this->checkForDBDumps();
		$this->log("OK\n", 'ok');
	
	}
	function checkUpgradeContainer()
	{
		$updateContainer = $this->cfg->getSetting('ezupgrade', 'Upgrade_' . $this->upgradeToVersion, 'UpgradeContainerSinceVersion');
		if ( $this->upgradeFromVersion < $updateContainer )
		{
			$this->log("Can not upgrade from " . $this->upgradeFromVersion . " to " . $this->upgradeToVersion  . ". Version "  . $this->upgradeToVersion . " holds upgrade files only down to version " . $updateContainer, 'critical');
		}
	}
	function checkForDBDumps()
	{
		if(!file_exists($this->getDBDumpLocation()))
		{
			$this->log("No database dump directory exists. The upgrade process expects a directory named " . $this->getDBDumpLocation() . " which contains the DB dumps as SQL files.", 'critical');
		}
	}
	
	function getNewDistroFolderName()
	{
		if(isset($this->data['new_distro_folder_name']))
		{
			return $this->data['new_distro_folder_name'];
		}
		else
		{
			$this->data['new_distro_folder_name'] = $this->upgradeData['upgrade_base_path'] . 'ezpublish-' . $this->upgradeToVersion . '/';
			
			$this->log('The folder name for the new distro is not specified. Guessing ' . $this->data['new_distro_folder_name'] . "\n", 'warning');
			
			return $this->data['new_distro_folder_name'];
		}
	}
	function isLocalInstallation()
	{
		$isLocal = $this->cfg->getSetting('account', 'Accounts', 'IsLocal');
		if ( $isLocal == trim('true'))
		{
			return true;
		}
		return false;
		
	}
	function validDatabaseConnectionDetails($access)
	{
		
		
		if( ( $access['User'] == 'root' OR $access['Password'] == '' ) and !$this->isLocalInstallation() )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	function grantAccessToNewDatabases()
	{
		$databaseList = $this->fetchDbList();
		
		// for each existing access
		foreach($databaseList as $access)
		{
			
			if(!$this->validDatabaseConnectionDetails($access))
			{
				ob_start();
				var_dump( $databaseList);
				$buffer = ob_get_contents();
				ob_end_clean();
				$this->log( $buffer . "\n" );
				$this->log("A DB user was not granted access because the password was empty, or the username was root.\n", 'critical');
			}
			else
			{
				// grant access to the user for the new database
				$this->grantDBUserAccess($access['User'], $access['Password'], $this->createNewDBName($access['Database']));
			}
		}
	}
	
	function checkForFinalUpgrade()
	{
		// if the version we are upgrading to is less recent than the upgrade version requested
		if($this->getVersionPosition($this->upgradeToVersion) > $this->getVersionPosition($this->upgradeData['upgrade_to_version']))
		{
			$this->log('You wanted to upgrade to version ' . $this->upgradeData['upgrade_to_version'] . ' but this iteration was only able to upgrade to ' . $this->upgradeToVersion . '. You need to run the script again with updated version parameters.', 'warning');
		}
	}
	
	function getVersionPosition($versionNo)
	{
		// find the position of the provided version number in the list of eZ versions
		$position = array_search($versionNo, $this->fetchAllVersions());
		if(is_int($position))
		{
			return $position;
		}
		else
		{
			$this->log("Unable to get version position for version $versionNo. The version is not specified in the INI files.\n", 'critical');
		}
	}
	
	function setUpgradeData($upgradeData)
	{
		// store upgrade data as a property
		$this->upgradeData = $upgradeData;
	}
	
	function canUpgradeTo($versionNo)
	{
		return $this->cfg->hasGroup('ezupgrade', 'Upgrade_' . $versionNo);
	}
	
	function manualAttentionNotice()
	{
		$this->manualAttentionNotificationList[] = "The log file is locateded in lib/ezcopy/logs/";
		// looping through the notification list to make output with manual attenions notices.
		foreach ( $this->manualAttentionNotificationList as $notification )
		{
			$this->log( $notification . "\n", 'warning' );
		}
	}
	function performUpgrades()
	{
		
		// fetch upgrade steps
		$upgradeStepList = $this->fetchUpgradeSteps();
		
		$upgradeFunctions = new upgradeFunctions($this);
		// for each applicable version
		foreach($upgradeStepList as $version => $upgradeStep)
		{
			$this->log("Running upgrades for v. $version\n", 'heading');
			// checkpoint
			$this->checkpoint( 'Upgrades for v. ' . $version );
			
			// check if the UpgradeFunctions key is in the UpgradeStep
			if ( array_key_exists( 'UpgradeFunctions', $upgradeStep ) )
			{
				// for each upgrade function
				foreach($upgradeStep['UpgradeFunctions'] as $upgrade)
				{
					// fetch upgrade function and lowest version number which does not require the upgrade
					$parts 				= explode(";", $upgrade);
					$upgradeFunction 	= $parts[0];
					
					if(isset($parts[1]))
					{
						$lowestVersionNotInNeedOfUpgrade = $parts[1];
					}
					else
					{
						$lowestVersionNotInNeedOfUpgrade = false;
					}
								
					$this->log('Upgrade function ' . $upgradeFunction . " ");
					
					// make sure that function should be run (depending on the version
					// set in the INI file)
					$runUpgrade = true;
					
					if($lowestVersionNotInNeedOfUpgrade AND version_compare($this->upgradeFromVersion, $lowestVersionNotInNeedOfUpgrade, '>'))
					{
						$runUpgrade = false;
					}
					
					if($runUpgrade)
					{
						// run upgrade function
						$this->log("run\n");
						if ( $upgradeFunction == 'updateDBForVersion' )
						{
							$upgradeFunctions->updateDBForVersion( $version );
						}
						else
						{
							$upgradeFunctions->$upgradeFunction();
						}
					}
					else
					{
						$this->log("not run\n");
					}
				}
			}
		}
	}
	
	function fetchUpgradeFromVersion()
	{
		$this->upgradeFromVersion = $this->data['ez_version'];
	}
	
	function fetchUpgradeSteps()
	{
		$upgradeStepList = array();
		
		// fetch all eZ versions from settings, in ascending order
		$eZversionsList = array_reverse($this->fetchAllVersions());
		
		// fetch the positions of the version we are upgrading from and to
		$upgradeFromPosition 	= array_search($this->upgradeFromVersion, $eZversionsList);
		$upgradeToPosition 		= array_search($this->upgradeToVersion, $eZversionsList);
		
		
		// if the version we are upgrading to is not specified
		if(!$upgradeToPosition)
		{
			$this->log("The version we are upgrading to is not specified in the INI files.\n", 'critical');
		}
		else
		{
			// for each version
			foreach ($eZversionsList as $key => $versionNo)
			{
				// if the current version is higher than the version we are upgrading from and
				// lower or equal to the version we are upgrading to
				if($key > $upgradeFromPosition AND $key <= $upgradeToPosition)
				{
					// if we can not upgrade to this version
					if(!$this->canUpgradeTo($versionNo))
					{
						$this->log("Unable to upgrade to version $versionNo as part of the upgrade path. The version has no settings.", 'critical');
					}
					else
					{
						// fetch version settings
						$currentVersionSettings = $this->fetchVersionSettings( $versionNo );
						
						// if the version is not just a maintenance release in a previos minor
						if($currentVersionSettings['MaintenanceRelease'] != 'true')
						{
							// add it to applicable versions
							$upgradeStepList[$versionNo] = $currentVersionSettings;
						}
					}
				}
			}
		}
		
		return $upgradeStepList;
	}
	
	function getDBDumpLocation()
	{
		return $this->getOldInstallationPath() . $this->dbDumpDir;
	}
	
	function copyDatabases()
	{
		$this->log("Copying databases\n", 'heading');
		
		// fetch the list of unique databases being used
		$dbList = $this->fetchDbList();

		// for each database
		foreach($dbList as $db)
		{
			// create new database name
			$newDBName = $this->createNewDBName( $db['Database']);
			
			// if, for some strange reason, the old db name is the same as the new one
			if($db['Database'] == $newDBName)
			{
				$this->log("The old and new datebase names are the same(old: '" . $db['Database'] . "' - new: '" . $newDBName . "'). This is probably best handled manually.", 'critical');
			}
			
			// create database
			$this->createDatabase($newDBName);
			
			// apply db dump
			$sqlFile = $this->getDBDumpLocation() . $db['Database'] . '.sql';
			$this->applyDatabase($newDBName, $sqlFile);
		}
	}
	
	function applyDatabaseSql($dbName, $sql)
	{
		$this->applyDatabase($dbName, $sql);
	}
	
	function createNewDBName($oldDBName)
	{
		// TODO: Here we assume that the db name is something like
		// "user_ezp" or "user_ezp_410" where the number is the ez version number
		
		$dbNameParts = explode("_", $oldDBName);
		
		// reverse the array to get the last element with index 0
		$reverseDBNameParts = array_reverse($dbNameParts);
		
		// if there is already a version number at the end of the DB name
		if(is_numeric($reverseDBNameParts[0]))
		{
			// chop off the version number
			unset($reverseDBNameParts[0]);
		}
		
		// reverse the array back again
		$dbNameParts = array_reverse($reverseDBNameParts);
		
		// remove dots from version number
		$versionParts = explode(".", $this->upgradeToVersion);
		
		// add version number to array of db name parts
		$dbNameParts[] = implode("", $versionParts);
		
		// build new db name
		$newDBName = implode("_", $dbNameParts);
		
		// return new db name
		return $newDBName;
	}
	
	function getDBAccessList()
	{
		// prepare result
		$result = array();
		
		// for each site.ini file
		foreach($this->getSiteIniFiles() as $siteIniFile)
		{
			// get database details from site ini file
			$dbDetails = $this->getDBDetailsFromINI($siteIniFile);
			
			// if the site.ini file has DB details
			if($dbDetails)
			{
				// unless these connection details have already been stored
				$key = $dbDetails['Server'] . '_' . $dbDetails['User'] . '_' . $dbDetails['Database'];
				if(!isset($result[$key]))
				{
					$result[$key] = $dbDetails;
				}
			}
		}
		
		return $result;
	}
	
	function getDBDetailsFromINI($siteIniFile)
	{
		$ini = $this->iniInstance($siteIniFile);

		// the database fields we require
		$dbFields = array('Server', 'User', 'Password', 'Database');
		
		$dbDetails = array();
		foreach($dbFields as $fieldName)
		{
			if($ini->hasVariable('DatabaseSettings', $fieldName))
			{
				$dbDetails[$fieldName] = $ini->variable('DatabaseSettings', $fieldName);
			}
		}

		// if all the required database fields exist in the INI file
		if(count($dbFields) == count($dbDetails))
		{
			return array_merge(array('ini' => $siteIniFile), $dbDetails);
		}
		else
		{
			return false;
		}
	}
	
	function getSiteIniFiles()
	{
		$siteIniList = ezcBaseFile::findRecursive( $this->getNewDistroPathName() . "settings", array( '@site\.ini@' ) );
		
		$result = array();
		foreach($siteIniList as $siteIniFilePath)
		{
			$parts = explode($this->getNewDistroPathName(), $siteIniFilePath);
			
			// ignore the default site.ini and any temp INI files
			if($parts[1] != 'settings/site.ini' AND $parts[1] != '/settings/site.ini' AND !strstr($siteIniFilePath, '~') AND !strstr($siteIniFilePath, '.LCK'))
			{
				$result[] = $parts[1];
			}
		}
		return $result;
	}
	function updateDBConnections()
	{
		foreach( $this->getSiteIniFiles() as $iniFile )
		{
			// get instance of current ini file
			$ini = $this->iniInstance($iniFile);
			
			$oldDBName = false;
			// get current db name
			
			if ( $ini->hasVariable( 'DatabaseSettings', 'Database' ) )
			{
				$oldDBName = $ini->variable('DatabaseSettings', 'Database');
			}
			
			// provided that the INI file has a database name set
			if($oldDBName !== false )
			{
				if ( $this->fetchUpgradeToVersion() > '3.10.1' )
				{
					// set new database name
					$ini->setVariable('DatabaseSettings', 'Database', $this->createNewDBName($oldDBName));
					// save changes in ini file
					if(!$ini->save() )
					{
						$this->checkpoint('updateDBConnections()', 'Please change your database name in ' . $iniFile, true);
					}
				}
				else
				{
					$this->checkpoint('updateDBConnections()', "The file '" . $iniFile . "' need to be altered to use database '" . $this->createNewDBName($oldDBName) . "' and then clear the cache.", true);
				}
				
			}
		}
		$this->log( "end of updateDBConnections()\n" );
	}
	

	
	function copyFiles()
	{
		$this->log("Copying files\n", 'heading');
		
		// copy the var files
		$this->copyVarFiles();
		
		// list of folder names to copy
		$folderCopyList = array('extension/',
								'settings/override/',
								'settings/siteaccess/',
								'design/');
		
		// for each folder to copy
		foreach($folderCopyList as $folderName)
		{
			// copy folder
			$this->promptFileOverride($folderName);
		}
	}
	
	function fetchFolderContents($folderName)
	{
		$cmd = 'cd ' . $this->getOldInstallationPath() . $folderName . ';ls';
		
		exec($cmd, $result);
		
		return $result;
	}
	
	function copyVarFiles()
	{
		$this->log("Copying var/ directory ");
		
		$cmd = 'cp -R ' . $this->getOldInstallationPath() . 'var/' . '* ' . $this->getNewDistroFolderName() . 'var';
				
		exec($cmd, $result);
		
		$this->log("OK\n", 'ok');
	}
	
	function promptFileOverride($dir)
	{
		
		$elementList = $this->fetchFolderContents($dir);
		
		// for each element
		foreach($elementList as $element)
		{
			$elementExists = false;
			// check if the element exists in the new distro
			$target = $this->getNewDistroFolderName() . $dir . $element;
			if(file_exists($target))
			{
				$elementExists = true;
			}

			$copyElement = true;
			
			// if the element exists in the new distro
			if($elementExists)
			{
				// prompt the user for whether the element shuold be overriden
				if(!$this->userWantsToOverrideElement($dir . $element))
				{
					// if the user does not want to override the element
					$copyElement = false;
				}
			}
			
			// if the design should be copied
			if($copyElement)
			{
				$this->log("Copying " . $dir . $element ." ");
				
				// create the dir if it doesnt excist.
				if ( !is_dir($this->getNewDistroFolderName() . $dir ) )
				{
					exec( "mkdir " . $this->getNewDistroFolderName() . $dir );
				}
				
				// copy the element
				$cmd = "cp -R " . $this->getOldInstallationPath() . $dir . $element . " " . $this->getNewDistroFolderName() . $dir;
				
				// execute command
				exec($cmd);
				
				$this->log("OK \n", 'ok');
			}
		}
	}
	
	function userWantsToOverrideElement($target)
	{
		// setting default action
		$option = 'prompt';
		
		// check if there is set any default prompt option, and set this to the option
		if ( $this->cfg->getSetting('account', 'Accounts', 'DefaultPromptOption') )
		{
			$option	 = $this->cfg->getSetting('account', 'Accounts', 'DefaultPromptOption');
		}
		
		// if the ini file say that we alway should answear the prompt with the default answer we return false
		if ( $option == 'use_default' )
		{
			return false;
		}
		
		$this->output->formats->question->color = 'yellow';

		$question = new ezcConsoleQuestionDialog( $this->output );
		$question->options->text = "The element $target already exists on the new installation. Do you want to override it?";
		$question->options->format = 'question';
		$question->options->showResults = true;
		$question->options->validator = new ezcConsoleQuestionDialogCollectionValidator(
		array( "y", "n" ),
		"n",
		ezcConsoleQuestionDialogCollectionValidator::CONVERT_LOWER
		);
		
		// if the answer is yes
		if(ezcConsoleDialogViewer::displayDialog( $question ) == 'y')
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	function prepareAndUnpackDistro()
	{
		$basePath = getcwd();
		
		
		// search distros/ for a matching version
		chdir('distros') or die("can't chdir!\n");
		
		$distroLocation = getcwd() . '/';
		
		$this->log("Checking for local distro..\n");
		
		$files = glob('*' . $this->upgradeToVersion . '*');
		
		$localDistro = true;
		
		// if we have local files
		if(count($files) > 0)
		{
			$this->log("Match found.\n");
			
			$filename = $files[0];
		}
		else
		{
			$localDistro = false;
			$this->log("Local distro not found. Checking for remote distro..\n");
			
			if(isset($this->upgradeVersionSettings['DownloadURL']))
			{
				$this->log("Remote distro found.\n");
			}
			else
			{
				$this->log("No distro available for version " . $this->upgradeToVersion . ". Aborting\n");
				exit();
			}
		}
		
		// change to install folder
		chdir($this->upgradeData['upgrade_base_path']) or die("can't chdir!\n");
		
		$newDistroFolderName = 'ezpublish-' . $this->upgradeToVersion;
		
		// if a distro location is specified
		if($localDistro)
		{
			$this->log('Copying distro from specified location ');
			
			// build distro file name and path
			$distroFile = $distroLocation . $filename;

			// do a checkpoint
			$this->checkpoint( 'Copying distro', 'Distro to copy: ' . $distroFile );
			
			// copy distro from distro location
			$cmd = 'cp \'' . $distroFile . '\' ' . $this->upgradeData['upgrade_base_path'] . $filename;
							
			exec($cmd);
			
			$this->log("OK\n", 'ok');
		}
		// if no distro location is specified
		else
		{
			$this->log("Downloading distro\n");
			
			// do a checkpoint
			$this->checkpoint( 'Downloading distro' );

			// set a temporary filename
			$filename = 'archive';
			
			// download the file
			$command = "curl -s -o $filename " . $this->upgradeVersionSettings['DownloadURL'] . " 2>&1";
			exec($command, $output, $rc);
			
			if ( $rc ) die("Error downloading file:<br>" . implode("<br>", $output));
			
			// check the file mime type, rename and add the correct file extension
			$fi = new finfo(FILEINFO_MIME);
			
			switch($fi->buffer(file_get_contents($filename)))
			{
				case 'application/x-gzip; charset=binary':
					exec("mv $filename ezpublish.tar.gz");
					$filename = "ezpublish.tar.gz";
					break;
				case 'application/x-tar; charset=binary':
					exec("mv $filename ezpublish.tar");
					$filename = "ezpublish.tar";
					break;
				case 'application/zip; charset=binary':
					exec("mv $filename ezpublish.zip");
					$filename = "ezpublish.zip";
					break;
				default:
					$this->log("File MIME type not recognized. Aborting\n");
					exit();
			}
			
			$this->log("OK\n", 'ok');
		}
		
		// unpacking archice
		$this->unpackArchive($filename, $this->upgradeData['upgrade_base_path']);
		
		// set the correct installation path
		$this->data['new_distro_folder_name'] = $this->upgradeData['upgrade_base_path'] . $newDistroFolderName . '/';
		
		// if the correct directory does not exist
		if ( !is_dir( $this->data['new_distro_folder_name'] ) )
		{
			$error = true;
			$dirs = scandir($this->upgradeData['upgrade_base_path']);
			
			// if we find a directory name containing the correct eZ Publish version, rename the directory
			foreach($dirs as $dir)
			{
				if (strpos($dir,$this->upgradeToVersion))
				{
					exec('mv ' . $dir . ' ' . $this->data['new_distro_folder_name']);
					$error = false;
				}
			}
			
			if($error)
			{
				$this->log( 'Did not find ' . $this->data['new_distro_folder_name'], 'critical' );
			}
			
			
		}
		// $last_line = exec("cd " . $this->upgradeData['upgrade_base_path'] . ";ls -lrt | grep ^d");
		// $this->data['new_distro_folder_name'] = rtrim(array_pop(preg_split("/[\s]+/", $last_line,-1,PREG_SPLIT_NO_EMPTY)), "\n");
		
		// TODO: change ownership of files - uncertaion which user we should change to here
		// $cmd = "chown -R " . $this->data['ssh_user'] . " " . $this->data[''];
		// exec($cmd, $output, $rc);
		// if ( $rc ) print("WARNING: failed to chown $folder_name<br>");
		
		// remove the distro file
		if(file_exists($this->upgradeData['upgrade_base_path'] . $filename))
		{
			unlink($this->upgradeData['upgrade_base_path'] . $filename);
		}
		
		// change back to old dir
		chdir($basePath);
	}
	
	function checkRequirements()
	{
		$this->log("Checking requirements ");
		
		$requirements = new requirements;
		
		// for each requirement
		foreach($this->upgradeVersionSettings['Requirements'] as $requirementMethod)
		{
			$requirements->runMethod($requirementMethod);
		}
		
		$this->log("OK\n", 'ok');
	}
	
	function fetchVersionSettings($versionNo)
	{
		// if the version has settings
		if($this->cfg->hasGroup('ezupgrade', 'Upgrade_' . $versionNo))
		{
			return $this->cfg->getSettingsInGroup('ezupgrade', 'Upgrade_' . $versionNo);
		}
		else
		{
			return false;
			$this->log("Unable to fetch settings for the version $versionNo.\n", 'critical');
		}
	}
	
	function fetchUpgradeToVersion()
	{
		// fetch the most recent version which holds upgrades further back than the version
		// we are upgrading from, and set it as the version we are upgrading to
		
		// fetch all eZ versions in descending order
		$eZversionsList = $this->fetchAllVersions();
		
		$passes_array = array('==', '<=');
		
		foreach($passes_array as $operator) {
			
			// for each version
			foreach ($eZversionsList as $currentVersionPosition => $versionNo)
			{
				
				// if the current version is less than or equal to the version the user wants to upgrade to
				if(version_compare($versionNo, $this->upgradeData['upgrade_to_version'], $operator))
				{
					// if we can upgrade to this version
					if($this->canUpgradeTo($versionNo))
					{
						// fetch how far back the this distro can be used for upgrades
						$upgradeContainerSinceVersion = $this->cfg->getSetting('ezupgrade', 'Upgrade_' . $versionNo, 'UpgradeContainerSinceVersion');
						
						// where in the order of versions is this version
						
						$upgradeContainerVersionPosition = $this->getVersionPosition($upgradeContainerSinceVersion);
						if($upgradeContainerVersionPosition > $currentVersionPosition)
						{
							$this->log('Upgrading ' . $this->upgradeData['account_name'] . ' from version ' . $this->upgradeFromVersion . ' to version ' . $versionNo . "\n");
							
							// do a checkpoint
							$this->checkpoint( 'Check on upgrade to version: ' . $versionNo );
							
							$this->upgradeToVersion = $versionNo;
							return;
						}
					}
				}
			}
		}
		
		$this->log("Unable to fetch the version we are upgrading to.\n", 'critical');
	}
	
	function fetchAllVersions()
	{
		if($this->versionList)
		{
			return $this->versionList;
		}
		else
		{
			return $this->cfg->getSetting('ezupgrade', 'General', 'Versions');
		}
	}
	function describeUsage()
	{
		$this->log("\nUSAGE\n", 'heading');
		$this->log("php ezupgrade [account_username]\n\n");
	}
	function checkParamsBeforeRunning( $data )
	{
		// TODO: Make som checks and return some errors!
		return true;
	}
	
	function unpackArchive($sourceFile, $destinationPath)
	{
		$fi = new finfo(FILEINFO_MIME);
		
		switch($fi->buffer(file_get_contents($sourceFile)))
		{
			case 'application/x-gzip; charset=binary':
				$cmd = 'tar xfvz';
				break;
			case 'application/x-tar; charset=binary':
				$cmd = 'tar xfv';
				break;
			case 'application/zip; charset=binary':
				$cmd = 'unzip';
				break;
		}
		
		exec("cd " . $destinationPath . "/;" . $cmd . ' '. $sourceFile);
		
		$this->log("OK\n", 'ok');
	}
}

?>