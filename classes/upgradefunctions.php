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
		$this->upgrade->log("Running script $script ");
		
		exec("cd " . $this->upgrade->getNewDistroFolderName() . ";php " . $script);
		
		$this->upgrade->log("OK\n", 'ok');
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
		echo $msg . "\n";
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
	/*function updateDB310()
	{
		$sql = $this->dbBasePath . $this->dbType . '/3.10/dbupdate-3.10.0-to-3.10.1.sql';
		$this->updateDB($sql);
	}
	function updateDB420u410()
	{
		$sql = 'sql/4.2/to-4.2.0-under-4.1.0.sql';
		$this->upgrade->manualAttentionNotificationList[] = 'You need to activate the ezoe extension';
		$this->upgrade->manualAttentionNotificationList[] = 'If you have custome made extensions, the setting ModuleList[] must be added in module.ini.append.php, with list of each module.';
		$this->updateDB($sql, false);
	}
	function updateDB420u404()
	{
		$sql = 'sql/4.2/to-4.2.0-under-4.0.4.sql';
		$this->updateDB($sql, false);
	}
	function updateDB410()
	{
		$sql = 'sql/4.1/to-4.1.0.sql';
		$this->updateDB($sql, false);
	}
	function updateDBu40()
	{
		$sql3_10_0to4_0_0 = $this->dbBasePath . $this->dbType . '/4.0/dbupdate-3.10.0-to-4.0.0.sql';
		$this->updateDB( $sql4_0_0to4_0_1 );
	}
	function updateDB407()
	{
		$sql = $this->dbBasePath . $this->dbType . '/4.1/dbupdate-4.0.6-to-4.0.7.sql';
		$this->updateDB( $sql );	
	}
	function updateDB420()
	{
		$sql = 'sql/4.2/to-4.2.0.sql';
		$this->updateDB($sql, false);
	}
	
	
	function updateDB411()
	{
		$sql = $this->dbBasePath . $this->dbType . '/4.1/dbupdate-4.1.0-to-4.1.1.sql';
		$this->updateDB($sql);
	}
	
	function updateDB412()
	{
		$sql = $this->dbBasePath . $this->dbType . '/4.1/dbupdate-4.1.1-to-4.1.2.sql';
		$this->updateDB($sql);
	}
	function updateDB414()
	{
		$sql = $this->dbBasePath . $this->dbType . '/4.1/dbupdate-4.1.3-to-4.1.4.sql';
		$this->updateDB($sql);
	}
	function updateDB413()
	{
		$sql = $this->dbBasePath . $this->dbType . '/4.1/dbupdate-4.1.2-to-4.1.3.sql';
		$this->updateDB($sql);
	}
	function updateDB402()
	{
		$sql = $this->dbBasePath . $this->dbType . '/4.2/dbupdate-4.1.0-to-4.2.0.sql';
		$this->updateDB( $sql );
	}
	function updateDBOE501()
	{
		$sql = '/extension/ezoe/update/database/5.0/dbupdate-5.0.0-to-5.0.1.sql';
		$this->updateDB($sql);
	}
	function updateDB403()
	{
		$sql = $this->dbBasePath . $this->dbType . '/4.0/dbupdate-4.0.2-to-4.0.3';
		$this->updateDB( $sql );
	}
	function updateDB404()
	{
		$sql = $this->dbBasePath . $this->dbType . '/4.0/dbupdate-4.0.3-to-4.0.4';
		$this->updateDB( $sql );
	}
	function updateDB405()
	{
		$sql = $this->dbBasePath . $this->dbType . '/4.0/dbupdate-4.0.4-to-4.0.5';
		$this->updateDB( $sql );
	}
	function updateDB406()
	{
		$sql = $this->dbBasePath . $this->dbType . '/4.0/dbupdate-4.0.5-to-4.0.6';
		$this->updateDB( $sql );
	}
	*/
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