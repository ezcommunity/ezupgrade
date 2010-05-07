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
	function updateDB3810()
	{
		$sql = 'sql/3.8/3.8.10.sql';
		$this->updateDB($sql, false);
	}
	function updateDB390()
	{
		$sql = 'sql/3.9/3.9.0.sql';
		$this->updateDB($sql, false);
	}
	function updateDB391()
	{
		$sql = 'sql/3.9/3.9.1.sql';
		$this->updateDB($sql, false);
	}
	function updateDB392()
	{
		$sql = 'sql/3.9/3.9.2.sql';
		$this->updateDB($sql, false);
	}
	function updateDB393()
	{
		$sql = 'sql/3.9/3.9.3.sql';
		$this->updateDB($sql, false);
	}
	function updateDB394()
	{
		$sql = 'sql/3.9/3.9.4.sql';
		$this->updateDB($sql, false);
	}
	function updateDB395()
	{
		$sql = 'sql/3.9/3.9.5.sql';
		$this->updateDB($sql, false);
	}
	function updateDB3100()
	{
		$sql = 'sql/3.10/3.10.0.sql';
		$this->updateDB($sql, false);
	}
	function updateDB3101()
	{
		$sql = 'sql/3.10/3.10.1.sql';
		$this->updateDB($sql, false);
	}
	function updateDB401()
	{
		$sql = 'sql/4.0/4.0.1.sql';
		$this->updateDB($sql, false);
	}
	function updateDB402()
	{
		$sql = 'sql/4.0/4.0.2.sql';
		$this->updateDB($sql, false);
	}
	function updateDB403()
	{
		$sql = 'sql/4.0/4.0.3.sql';
		$this->updateDB($sql, false);
	}
	function updateDB404()
	{
		$sql = 'sql/4.0/4.0.4.sql';
		$this->updateDB($sql, false);
	}
	function updateDB405()
	{
		$sql = 'sql/4.0/4.0.5.sql';
		$this->updateDB($sql, false);
	}
	function updateDB406()
	{
		$sql = 'sql/4.0/4.0.6.sql';
		$this->updateDB($sql, false);
	}
	function updateDB407()
	{
		$sql = 'sql/4.0/4.0.7.sql';
		$this->updateDB($sql, false);
	}
	function updateDB410()
	{
		$sql = 'sql/4.1/4.1.0.sql';
		$this->updateDB($sql, false);
	}
	function updateDB411()
	{
		$sql = 'sql/4.1/4.1.1.sql';
		$this->updateDB($sql, false);
	}
	function updateDB412()
	{
		$sql = 'sql/4.1/4.1.2.sql';
		$this->updateDB($sql, false);
	}	
	function updateDB413()
	{
		$sql = 'sql/4.1/4.1.3.sql';
		$this->updateDB($sql, false);
	}	
	function updateDB414()
	{
		$sql = 'sql/4.1/4.1.4.sql';
		$this->updateDB($sql, false);
	}
	function updateDB420()
	{
		$sql = 'sql/4.2/4.2.0.sql';
		$this->updateDB($sql, false);
	}
	function updateDB430()
	{
		$sql = 'sql/4.3/4.3.0.sql';
		$this->updateDB($sql, false);
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
		$scriptList = array( 'fixobjectremoteid.php', 'updatevatcountries.php', 'updatebinaryfile.php' );
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/3.9/' . $script, 4 );
		}
	}

	function upgradeScripts391()
	{

		$this->upgrade->checkpoint('upgradeScripts391()', 'Please change your database name in the given siteaccess in account.ini', true);
		$scriptList = array( 'correctxmltext.php', 'updateclasstranslations.php --language=eng-GB', 'updatetypedrelation.php' );
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/3.9/' . $script, 4 );
		}
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
}

?>