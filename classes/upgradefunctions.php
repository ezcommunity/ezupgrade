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
		$this->dbBasePath 	= '/update/database/';
		$this->dbType		= 'mysql';
		$this->upgrade		= $upgrade;
	}
	
	function runScript($script)
	{
		$this->upgrade->log("Running script $script ");
		
		exec("cd " . $this->upgrade->data['document_root'] . $this->upgrade->getNewDistroFolderName() . ";php " . $script);
		
		$this->upgrade->log("OK\n", 'ok');
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
			$sql = $this->upgrade->data['document_root'] . $this->upgrade->getNewDistroFolderName() . $sql;
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
	
	function updateDB413()
	{
		$sql = $this->dbBasePath . $this->dbType . '/4.1/dbupdate-4.1.2-to-4.1.3.sql';
		$this->updateDB($sql);
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