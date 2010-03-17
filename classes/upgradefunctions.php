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
				$this->upgrade->log("Running script $script \n");
				
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
	
	function generateAutoLoads()
	{
		$script = 'bin/php/ezpgenerateautoloads.php --extension';
		$this->runScript($script);
	}
	
	function upgradeScripts41()
	{
		$scriptList = array('addlockstategroup.php', 
							'fixclassremoteid.php', 
							'fixezurlobjectlinks.php', 
							'fixobjectremoteid.php', 
							'initurlaliasmlid.php');

		foreach($scriptList as $script)
		{
			$this->runScript('update/common/scripts/4.1/' . $script);
		}
	}
}

?>