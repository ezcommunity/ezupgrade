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
	
	function runScript($script)
	{
		$siteAccessList = $this->upgrade->upgradeData['siteaccess_list'];
		if ( is_array( $siteAccessList ) )
		{
			foreach( $siteAccessList as $siteaccess )
			{
				$this->upgrade->log("Running script $script ");
				
				$this->upgrade->checkpoint( 'Running script: ' . $script );
				
				exec("cd " . $this->upgrade->getNewDistroFolderName() . ";php " . $script . " -s" . $siteaccess . " -siteaccess " . $siteaccess);
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
		$this->manualAttentionNotificationList[] = $msg;
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
	function preUpgradeScripts310()
	{
		$this->runScript('update/common/scripts/3.10/fixobjectremoteid.php' );
	}
	function upgradeScripts310() 
	{
		$list = array(  'updateniceurls.php --import',
						'ezimportdbafile.php --datatype=ezisbn' 
					);
		$siteAccessList = $this->upgrade->upgradeData['siteaccess_list'];
		if ( is_array( $siteAccessList ) )
		{
			foreach( $siteAccessList as $siteaccess )
			{
				foreach ( $list as $bin )
				{
					exec("cd " . $this->upgrade->getNewDistroFolderName() . ";php bin/php/". $bin . " -s" . $siteaccess);
				}
			}
		}
		$scriptList = array('updatemultioption.php',
							'updatevatcountries.php');
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/3.10/' . $script );
		}
		
	}
	function upgradeScripts39()
	{
		$scriptList = array( 'fixobjectremoteid.php', 'updatevatcountries.php', 'updatebinaryfile.php' );
		foreach( $scriptList as $script )
		{
			$this->runScript('update/common/scripts/3.9/' . $script );
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
	}
	function upgradeScripts43()
	{
		$scriptList = array('updatenodeassignment.php');

		foreach($scriptList as $script)
		{
			$this->runScript('update/common/scripts/4.3/' . $script);
		}
		
		$this->manualAttention('Replace replacerules: RewriteRule ^/var/cache/texttoimage/.* - [L] and RewriteRule  ^/var/[^/]+/cache/(texttoimage|public)/.* - [L] with RewriteRule ^/var/([^/]+/)?cache/(texttoimage|public)/.* - [L]');
		$this->manualAttention('In order to get new admin design to work add AdditionalSiteDesignList[]=admin2 in your admin siteaccess. Must be above the AdditionalSiteDesignList[]=admin');
		$this->manualAttention('You need to add the access content/dashboard to usergroups that are not administrators, but should have this.')
	}
}

?>