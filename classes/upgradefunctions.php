<?php

class upgradeFunctions  
{
	var $attention;
	var $dbBasePath;
	var $dbType;
	var $upgrade;
	
	function upgradeFunctions(&$upgrade)
	{
		$this->attention 	= array();
		$this->dbBasePath 	= 'update/database/';
		$this->dbType		= 'mysql';
		$this->upgrade		= $upgrade;
	}
	
	function runScript($script, $version=5)
	{
		$phpCli = $this->upgrade->getPathToPHP( $version );
		$siteAccessList = $this->upgrade->upgradeData['siteaccess_list'];
		if ( is_array( $siteAccessList ) )
		{
			foreach( $siteAccessList as $siteaccess )
			{
				$this->upgrade->log("Running script $script  -s" . $siteaccess);
				
				$this->upgrade->checkpoint( 'Running script: ' . $script );
				
				exec("cd " . $this->upgrade->getNewDistroFolderName() . ";".$phpCli." " . $script . " -s" . $siteaccess);
				$this->upgrade->log("OK\n", 'ok');
			}
		}
	}
	function updateCharsetDBu40()
	{	
		$siteAccessList = $this->upgrade->upgradeData['siteaccess_list'];
		if ( is_array( $siteAccessList ) )
		{
			foreach( $siteAccessList as $siteaccess )
			{
				$this->runScript('bin/php/ezconvertdbcharset.php -s ' . $siteaccess);		
			}
		}
	}
	function manualAttention($msg)
	{
		$this->attention[] = $msg;
		$this->upgrade->manualAttentionNotificationList[] = $msg;
	}
	function updateDB3100()
	{
		$sqlFile		= 'sql/3.10/3.10.0.sql';
		$this->updateDB( $sqlFile, false );
	}
	function updateDB($sql, $useBasePath = true)
	{
		
		if($useBasePath)
		{
			$sql = $this->upgrade->getNewDistroFolderName() . $sql;
		}
		
		// for each database
		$dbList = $this->upgrade->fetchDbList();
		foreach($dbList as $db)
		{
			$newDBName = $this->upgrade->createNewDBName( $db['Database']);
			
			// apply db dump
			$this->upgrade->applyDatabaseSql($newDBName, $sql);
		}
	}
	function updateDBForVersion( $version )
	{
		$versionStep 	= explode('.', $version );
		$sqlFile		= 'sql/' . $versionStep[0] . '.' . $versionStep[1] . '/' . $version .'.sql';
		$this->updateDB( $sqlFile, false );
	}
	function updateDBOE501()
	{
		$sql = '/extension/ezoe/update/database/5.0/dbupdate-5.0.0-to-5.0.1.sql';
		$this->updateDB($sql);
	}

	function updateOERewriteRules()
	{
		$this->manualAttention('Add to rewrite rules: RewriteRule ^/var/[^/]+/cache/public/.* - [L]');
	}
	
	function updateImageSystem()
	{
		$this->manualAttention('If you have an eZP version which used to be older than 3.3, please run the updateimagesystem.php script manually.');
	}
	function moduleListNotice()
	{
		$this->manualAttention('Please check your custom extensions with views. In module.ini.append.php, they should have the setting ModuleList[]' );
	}
	function upgrade430Notice()
	{
		$this->manualAttention('Replace replacerules: RewriteRule ^/var/cache/texttoimage/.* - [L] and RewriteRule  ^/var/[^/]+/cache/(texttoimage|public)/.* - [L] with RewriteRule ^/var/([^/]+/)?cache/(texttoimage|public)/.* - [L]');
		$this->manualAttention('In order to get new admin design to work add AdditionalSiteDesignList[]=admin2 in your admin siteaccess. Must be above the AdditionalSiteDesignList[]=admin');
		$this->manualAttention('You need to add the access content/dashboard to usergroups that are not administrators, but should have this.');
		$this->manualAttention('You need to activate the extension ezjscore to the the admin2 interface to work');
		$this->manualAttention("The 'Webshop'-tab is by default hidden. If your webpages is a webshop, you might want to enable this tab in the menu.ini");
		$this->manualAttention("A new settings in the right bar has been added in this version. To enable to get access to change viewsettings for placement and preview please add Tool[]=admin_preferences in toolbar.ini under [Toolbar_admin_right]");
		$this->manualAttention('If you have custom made views/functions you need to check for this depracated functions:');
		$this->manualAttention('ezi18n(), ezx18n(), imageInit(), templateInit(), removeAssignment()');
		$this->manualAttention('Make sure that the patch http://issues.ez.no/IssueView.php?Id=16814&activeItem=5 is appended to the version' );
	}
	function generateAutoLoads()
	{
		$script = 'bin/php/ezpgenerateautoloads.php --extension';
		$this->runScript($script);
	}
	function generateAutoLoadsKernel()
	{
		$script = 'bin/php/ezpgenerateautoloads.php --kernel';
		$this->runScript($script);
	}
	function upgradeScripts41()
	{
		$scriptList = array('addlockstategroup.php', 
							'fixclassremoteid.php', 
							'fixezurlobjectlinks.php', 
							'fixobjectremoteid.php --mode=a', 
							'initurlaliasmlid.php');

		foreach($scriptList as $script)
		{
			$this->runScript('update/common/scripts/4.1/' . $script);
		}
				
		$this->manualAttention('You need to deactivate the extension ezdhtml and activate the extension ezoe to get the new editor to work.');
		$this->manualAttention('You also need to add the rewrite rule: RewriteRule ^/var/[^/]+/cache/public/.* - [L]' );
		
	}
	function upgradeScripts43()
	{
		$scriptList = array('updatenodeassignment.php');

		foreach($scriptList as $script)
		{
			$this->runScript('update/common/scripts/4.3/' . $script);
		}
	}
	function upgradeScripts310() 
	{
		$this->upgrade->checkpoint('preUpgradeScripts310()', 'Please change your database name in the given siteaccess in account.ini', true);
		$scriptList = array(
							'updatemultioption.php',
							'updatevatcountries.php');
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/3.10/' . $script, 4 );
		}
		$scriptListSecond = array( 'updateniceurls.php --import',
									'ezimportdbafile.php --datatype=ezisbn');
		foreach( $scriptListSecond as $script )
		{
			$this->runScript('bin/php/' . $script, 4 );
		}	
	}
	function upgradeScripts394()
	{
		$this->upgrade->checkpoint('upgradeScripts394()', 'Please change your database name in the given siteaccess in account.ini', true);
		$scriptList = array( 'updatevatcountries.php' );
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/3.9/' . $script, 4 );
		}
	}
	function upgradeScripts391()
	{

		$this->upgrade->checkpoint('upgradeScripts391()', 'Please change your database name in the given siteaccess in account.ini', true);
		$scriptList = array( 'correctxmltext.php', 'updateclasstranslations.php --language=nor-NO', 'updatetypedrelation.php' );
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/3.9/' . $script, 4 );
		}
	}
	function upgradeScripts360()
	{
		$this->upgrade->checkpoint('upgradeScripts360()', 'Please change your database name in the given siteaccess in account.ini', true);
		$scriptList = array( 'convertxmllinks.php', 'updaterelatedobjectslinks.php', 'updateeztimetype.php' );
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/' . $script, 4 );
		}
		$this->manualAttention('In 3.6.0 its possible to clear the cache from right column. Take a look at this page for how to activate the toolbar. (Step 4): http://ez.no/doc/ez_publish/upgrading/upgrading_to_3_6/from_3_5_2_to_3_6_0');
	}
	function upgradeScripts380()
	{
		$this->upgrade->checkpoint('upgradeScripts380', "In version 3.8.0 it have been introduced multilanguage. In order to make this to work you need to change your site.ini.append.php files\n
		\n
		Frontend site.ini:\n
		SiteLanguageList[]\n
		SiteLanguageList[]=<my-first-language>\n
		SiteLanguageList[]=<my-second-language>\n
		ShowUntranslatedObjects=disabled\n
		\n
		Backend site.ini:\n
		SiteLanguageList[]\n
		SiteLanguageList[]=<my-first-language>\n
		SiteLanguageList[]=<my-second-language>\n
		ShowUntranslatedObjects=enabled\n
		\n
		Please do this changes before you proceed.", true );
		
		$this->upgrade->checkpoint('upgradeScripts380()', 'Please change your database name in the given siteaccess in account.ini', true);
		
		$siteAccessList = $this->upgrade->upgradeData['siteaccess_list'];
		if ( is_array( $siteAccessList ) )
		{
			foreach( $siteAccessList as $siteaccess )
			{
				$runScript = $this->upgrade->getPathToPHP(4) . " update/common/scripts/updatemultilingual.php -s " . $siteaccess . "\n";
			}
		}
		$this->upgrade->checkpoint('upgradeScripts380()', "You need to run the following command(s) from shell in the ezroot on the new ezpublish installatio before you continue.\n\n " . $runScript ."\n", true );
		$scriptList = array( 'updaterssimport.php' );
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/' . $script, 4 );
		}
		$this->manualAttention('In 3.8.0 settings for controlling classes shown in TreeMenu was added. You can control this in contentstructuremenu.ini under [TreeMenu]ShowClasses[]');
		$this->manualAttention('The binaryfile.ini`s standard values has been changed in 3.8.0. If you have an override of this, you should check the content of it.');
	}
	function upgradeScripts386()
	{
		$this->upgrade->checkPoint( 'upgradeScripts386', "In version 3.8.6 you need to specify custome class names to the online editor. This is done in content.ini.append.php under AvailableClasses[] for the element. Please check that your class names are added here before you go futher");
		$scriptList = array( 'correctxmltextclasses.php' );
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/' . $script, 4 );
		}
	}
	function upgradeScripts351()
	{
		$scriptList = array( 'updatetoplevel.php', 'updateeztimetype.php' );
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/' . $script, 4 );
		}
	}
	function upgradeScripts358()
	{
		$scriptList = array( 'updatecrc32.php' );
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/' . $script, 4 );
		}
	}
}

?>