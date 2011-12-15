<?php

class AccountConfiguration {
	
	var $output;
	var $accountName;
	var $iniParams;
	var $isRemote = false;
	var $mainSiteaccess;

	function __construct()
	{
		$this->output = new ezcConsoleOutput();
		$this->output->formats->info->style = array('bold');

		$this->showMenu();

		$this->setAccountName();
		$this->setIsRemote();
		$this->setExistingInstallationPath();
		$this->setExistingInstallationVersion();
		$this->getSiteaccessInfo();
		$this->getDBInfo();
		$this->getDBRootInfo();

		$this->getUpgradeToVersion();
		$this->getBasePath();
		$this->write();

		exit();
	}

	function getUserInput($text)
	{
		$this->output = new ezcConsoleOutput();
		$question = new ezcConsoleQuestionDialog( $this->output );
		$question->options->text = $text;
		
		do
		{
			$input = ezcConsoleDialogViewer::displayDialog( $question );
		}
		while($input == "");
		
		$question->reset();
		
		return $input;
	}

	function showMenu()
	{
		// Welcome message
		$this->output->outputLine("\nWelcome to the ezupgrade configuration.\n", "info");

		$this->output->outputLine("You are running ezupgrade in setup mode.");
		$this->output->outputText("If you want to perform an upgrade and have already configured an account,\nexit this session (Ctrl+C) and run ");
		$this->output->outputLine("php ezupgrade <account_name>", 'info');
		// Menu
		$menu = new ezcConsoleMenuDialog( $this->output );
		$menu->options = new ezcConsoleMenuDialogOptions();
		$menu->options->text = "What would you like to do?\n";
		$menu->options->validator = new ezcConsoleMenuDialogDefaultValidator(
			array(
				"1" => "Configure a new account",
				"0" => "Quit",
			),
			"1"
		);
		
		$choice = ezcConsoleDialogViewer::displayDialog( $menu );
		
		// if the user selects "Quit"
		if($choice == 0)
		{
			exit();
		}
	}

	// name the account
	function setAccountName()
	{
		// name the account
		$this->accountName = $this->getUserInput("Provide a name for the account:");

		$this->iniParams = array(
			"ezcopy" => array(
				"Account_{$this->accountName}" => array()
			),
			
			'account' => array(
				"Account_{$this->accountName}" => array()
			)
		);
	}

	function setIsRemote()
	{
		// Remote server?
		$remote = ezcConsoleQuestionDialog::YesNoQuestion(
			$this->output,
			"Is the installation you want to upgrade currently located on a remote server?",
			"n"
		);
		
		if(ezcConsoleDialogViewer::displayDialog( $remote ) !== "n" )
		{
			$this->isRemote = true;
			do
			{
				$this->iniParams['ezcopy']["Account_{$this->accountName}"]["ssh_host"] = $this->getUserInput("SSH host");
				$this->iniParams['ezcopy']["Account_{$this->accountName}"]["ssh_user"] = $this->getUserInput("SSH username");
				$this->iniParams['ezcopy']["Account_{$this->accountName}"]["ssh_pass"] = $this->getUserInput("SSH password");
			}
			while(!$this->validateSSH());
		}
	}

	function setExistingInstallationPath()
	{
		// existing installation path
		do
		{
			$input = $this->addSlash($this->getUserInput("Where is your installation currently residing? (Provide an absolute path)"));
		}
		while(!$this->validatePathInput($input));
		
		if($this->isRemote)
		{
			$isLocal = 'false';
			$this->iniParams['account']["Account_{$this->accountName}"]["ExistingInstallationLocation"] = "remote";
			$this->iniParams['ezcopy']["Account_{$this->accountName}"]["path_to_ez"] = $input;
		}
		else
		{
			$isLocal = 'true';
			$this->iniParams['account']["Account_{$this->accountName}"]["ExistingInstallationLocation"] = $input;
		}
		
		$this->iniParams['account']["Accounts"]['IsLocal'] = $isLocal;
	}

	function setExistingInstallationVersion()
	{
		// existing installation version
		do
		{
			$this->iniParams['ezcopy']["Account_{$this->accountName}"]["ez_version"] = $this->getUserInput("Which version is your current eZ Publish installation?");
		}
		while(!$this->validateFromVersion());
	}

	function getSiteaccessInfo()
	{
		// siteaccess list
		$this->siteaccessList = $this->getUserInput("Enter a comma separated list of siteaccesses with a unique database:");
		
		// split the string
		$this->siteaccessList = explode(',', $this->siteaccessList);
		
		$this->mainSiteaccess = array_shift($this->siteaccessList);
		$this->iniParams['account']["Account_{$this->accountName}"]["SiteaccessList"][] = $this->mainSiteaccess;
	}

	function getDBInfo()
	{
		// MySQL settings for the main DB
		do
		{
			$this->iniParams['ezcopy']["Account_{$this->accountName}"]["mysql_db"] = $this->getUserInput("Enter the name of the MySQL database for siteaccess {$this->mainSiteaccess}:");
			$this->iniParams['ezcopy']["Account_{$this->accountName}"]["mysql_user"] = $this->getUserInput("Enter the name of the MySQL username for siteaccess {$this->mainSiteaccess}:");
			$this->iniParams['ezcopy']["Account_{$this->accountName}"]["mysql_pass"] = $this->getUserInput("Enter the name of the MySQL password for siteaccess {$this->mainSiteaccess}:");
		}
		while(!$this->validateMySQL());
		
		// if there are remaining siteaccesses
		if(count($this->siteaccessList) > 0)
		{
			// get connection information for these as well
			foreach($siteaccessList as $key => $siteaccess)
			{
				$this->setup['account']["Account_{$accountName}"]["SiteaccessList"][] = $siteaccess;
				
				do
				{
					$this->iniParams['ezcopy']["Account_{$this->accountName}"]["additional_mysql[{$key}][host]"] = "localhost";
					$this->iniParams['ezcopy']["Account_{$this->accountName}"]["additional_mysql[{$key}][db]"] = $this->getUserInput("Enter the name of the MySQL database for siteaccess {$siteaccess}:");
					$this->iniParams['ezcopy']["Account_{$this->accountName}"]["additional_mysql[{$key}][user]"] = $this->getUserInput("Enter the name of the MySQL username for siteaccess {$siteaccess}:");
					$this->iniParams['ezcopy']["Account_{$this->accountName}"]["additional_mysql[{$key}][pass]"] = $this->getUserInput("Enter the name of the MySQL password for siteaccess {$siteaccess}:");
				}
				while(!$this->validateAdditionalMySQL($key));
			}
		}
	}

	function getDBRootInfo()
	{
		// db root
		do
		{
			$this->iniParams['ezcopy']["DBRoot"]["username"] = $this->getUserInput("Enter the database root username:");
			$this->iniParams['ezcopy']["DBRoot"]["password"] = $this->getUserInput("Enter the database root password:");
		}
		while(!$this->validateMySQLRoot());
	}

	function getUpgradeToVersion()
	{
		// Upgrade to
		do
		{
			$this->iniParams['account']["Account_{$this->accountName}"]["ToVersion"] = $this->getUserInput("Which version do you want to upgrade to?");
		}
		while(!$this->validateToVersion());
	}

	function getBasePath()
	{
		// Base path
		do
		{
			$input = $this->addSlash($this->getUserInput("Where do you want to place the upgraded installation? (Provide an absolute path)"));
			$this->iniParams['account']["Account_{$this->accountName}"]["BasePath"] = $input;
		}
		while(
			!$this->validatePathInput(
				$this->iniParams['account']["Account_{$this->accountName}"]["BasePath"],
				$this->iniParams['account']["Account_{$this->accountName}"]["ExistingInstallationLocation"]
			)
		);
	}

	function write()
	{
		foreach($this->iniParams as $iniFileName => $settingsArray)
		{

			$reader = new ezcConfigurationIniReader();
			$reader->init(dirname( __FILE__ )."/../settings", $iniFileName);

			// validate the settings file, and loop over all the validation errors and
			// warnings
			$result = $reader->validate();

			// load the settings into an ezcConfiguration object
			$iniObject = $reader->load();

			foreach($settingsArray as $blockName => $variableList)
			{
				foreach($variableList as $variable => $value)
				{
					$iniObject->setSetting($blockName, $variable, $value);
				}
			}

			switch($iniFileName)
			{
				case 'ezcopy':
					$accountList = $iniObject->getSetting('General', 'account_list');
					array_push($accountList, $this->accountName);
					$iniObject->setSetting('General', 'account_list', $accountList);
					break;
				case 'account':
					$accountList = $iniObject->getSetting('Accounts', 'AccountList');
					array_push($accountList, $this->accountName);
					$iniObject->setSetting('Accounts', 'AccountList', $accountList);
					break;
				default:
					break;
			}


			$writer = new ezcConfigurationIniWriter();
			$writer->init(dirname( __FILE__ )."/../settings", $iniFileName, $iniObject);
			@$writer->save();
		}

		if($this->isRemote)
		{
			$this->ssh->disconnect();
		}

		$this->output->outputLine("\nYou have completed the setup wizard!", 'info');
		$this->output->outputLine("To perform an upgrade of this account, run this command:");
		$this->output->outputLine("php ezupgrade {$this->accountName}\n");
	}

	/************************** VALIDATION FUNCTIONS **************************/

	function validateSSH()
	{
		$this->output->outputText("Checking SSH details..");

		
		if(count($hostPart=explode(':', $this->iniParams['ezcopy']["Account_{$this->accountName}"]['ssh_host']))>1)
		{
			@$this->ssh 		= new Net_SSH2($hostPart[0], $hostPart[1]);
		}
		else
		{
			@$this->ssh 		= new Net_SSH2($this->iniParams['ezcopy']["Account_{$this->accountName}"]['ssh_host']);
		}
		echo "Username: " . $this->iniParams['ezcopy']["Account_{$this->accountName}"]['ssh_user'];
		echo "\nPassword: ". $this->iniParams['ezcopy']["Account_{$this->accountName}"]['ssh_pass'];
		if ($this->ssh->login($this->iniParams['ezcopy']["Account_{$this->accountName}"]['ssh_user'], $this->iniParams['ezcopy']["Account_{$this->accountName}"]['ssh_pass']))
		{
			$this->output->outputText("OK\n", 'ok');
			return true;
		}
		else
		{
			$this->output->outputText("Unable to log in, please try again\n", "warning");
			return false;
		}
	}

	function validatePathInput($path, $shouldNotMatch = false)
	{
		if($shouldNotMatch)
		{
			if($path == $shouldNotMatch)
			{
				$this->output->outputText("You can't place the upgraded installation in the same directory as the current installation!\n", "warning");
				return false;
			}
		}

		$this->output->outputText("Validating format.. ");
		
		if(substr($path,-1) !== DIRECTORY_SEPARATOR)
		{
			$this->output->outputText("The path must end with trailing slash\n", "warning");
			return false;
		}

		$this->output->outputText("OK\n", 'ok');

		$this->output->outputText('Validating existence.. ');

		if($this->isRemote)
		{
			$this->output->outputText("Site isn't local, can't validate existence.\n");
			return true;
		}

		if(is_dir($path))
		{
			$this->output->outputText("OK\n", "ok");
			return true;
		}
		else
		{
			$this->output->outputText("{$path} does not exist\n", "warning");
			return false;
		}
	}

	function validateFromVersion()
	{
		$fromVersion = $this->iniParams['ezcopy']["Account_{$this->accountName}"]["ez_version"];

		return true;
	}

	function validateToVersion()
	{
		$fromVersion = $this->iniParams['ezcopy']["Account_{$this->accountName}"]["ez_version"];
		$toVersion = $this->iniParams['account']["Account_{$this->accountName}"]["ToVersion"];

		//$reader = new ezcConfigurationIniReader();
		//$reader->init(dirname( __FILE__ )."/../settings", 'ezupgrade');

		// validate the settings file, and loop over all the validation errors and
		// warnings
		//$result = $reader->validate();

		// load the settings into an ezcConfiguration object
		//$iniObject = $reader->load();

		//$validVersionList = $iniObject->getSetting('General', 'Versions');

		$this->output->outputText("Checking version.. ");

		$checker = ezcConfigurationManager::getInstance();
		$checker->init( 'ezcConfigurationIniReader', 'settings' );

		if(($checker->hasGroup('ezupgrade', 'Upgrade_' . $toVersion)) && (version_compare($toVersion, $fromVersion) == 1))
		{
			$this->output->outputText("Valid\n", "ok");
			return true;
		}
		else
		{
			$this->output->outputText("Upgrading to version {$toVersion} is not supported or you've entered a version older or equal to the current version\n", "warning");
			return false;
		}
	}

	function validateMySQL()
	{
		if($this->isRemote)
		{
			return true;
		}

		$db 	= $this->iniParams['ezcopy']["Account_{$this->accountName}"]["mysql_db"];
		$user 	= $this->iniParams['ezcopy']["Account_{$this->accountName}"]["mysql_user"];
		$pass 	= $this->iniParams['ezcopy']["Account_{$this->accountName}"]["mysql_pass"];

		$this->output->outputText('Logging in.. ');

		$connection = mysql_connect('localhost', $user, $pass);

		if($connection && mysql_select_db($db, $connection))
		{
			mysql_close();
			$this->output->outputText("OK\n", "ok");
			return true;
		}
		else
		{
			mysql_close();
			$this->output->outputText("Connection could not be established\n", "warning");
			return false;
		}
	}

	function validateMySQLRoot()
	{
		if($this->isRemote)
		{
			return true;
		}

		$user 	= $this->iniParams['ezcopy']["Account_{$this->accountName}"]["mysql_user"];
		$pass 	= $this->iniParams['ezcopy']["Account_{$this->accountName}"]["mysql_pass"];

		$rootuser = $this->iniParams['ezcopy']["DBRoot"]["username"];
		$rootpass = $this->iniParams['ezcopy']["DBRoot"]["password"];

		$connection = @mysql_connect('localhost', $rootuser, $rootpass);

		if($connection)
		{
			$dbName = md5(time());

			$this->output->outputText("Logged in successfully\n", "ok");

			$this->output->outputText("Creating database {$dbName}.. ");
			$sql = "CREATE DATABASE IF NOT EXISTS {$dbName}";

			if(!mysql_query($sql, $connection))
			{
				$this->output->outputText("Can't create database\n", "warning");
				return false;
			}

			$this->output->outputText("OK\n", 'ok');

			$this->output->outputText("Granting privileges.. ");
			$sql = "GRANT ALL PRIVILEGES ON {$dbName}.* TO {$user}@localhost IDENTIFIED BY '{$pass}'";

			if(!mysql_query($sql, $connection))
			{
				$this->output->outputText("Can't grant database privileges\n", "warning");
				return false;
			}

			$this->output->outputText("OK\n", 'ok');

			$this->output->outputText("Deleting database {$dbName}.. ");
			$sql = "DROP DATABASE {$dbName}";
			if(!mysql_query($sql, $connection))
			{
				$this->output->outputText("Couldn't delete database {$dbName}!\n", "warning");
			}
			else
			{
				$this->output->outputText("OK\n", 'ok');
			}
			
			mysql_close();

			return true;

		}
		else
		{
			$this->output->outputText("Connection could not be established\n", "warning");
			return false;
		}
	}

	function validateAdditionalMySQL($arrayKey)
	{
		if($this->isRemote)
		{
			return true;
		}

		$db 	= $this->iniParams['ezcopy']["Account_{$this->accountName}"]["additional_mysql[{$arrayKey}][db]"];
		$user 	= $this->iniParams['ezcopy']["Account_{$this->accountName}"]["additional_mysql[{$arrayKey}][user]"];
		$pass 	= $this->iniParams['ezcopy']["Account_{$this->accountName}"]["additional_mysql[{$arrayKey}][pass]"];

		$this->output->outputText('Validating.. ');
		
		$connection = mysql_connect('localhost', $user, $pass);

		if($connection && mysql_select_db($db, $connection))
		{
			mysql_close();
			$this->output->outputText("OK\n", "ok");
			return true;
		}
		else
		{
			mysql_close();
			$this->output->outputText("Connection could not be established\n", "warning");
			return false;
		}
	}

	function addSlash($path)
	{
		$path = trim($path);
		if(substr($path,-1) !== DIRECTORY_SEPARATOR)
		{
			$path = $path . DIRECTORY_SEPARATOR;
		}
		if($path[0] !== DIRECTORY_SEPARATOR)
		{
			$path = DIRECTORY_SEPARATOR . $path;
		}
		return $path;
	}
}
