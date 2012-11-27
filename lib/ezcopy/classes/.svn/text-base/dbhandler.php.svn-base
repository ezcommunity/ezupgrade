<?php

class dbhandler
{
	var $ezcopy;
	var $accountconfig;
	var $dbRootConnection;

	function __construct($ezcopy, $accountconfig=false)
	{
		$this->ezcopy = $ezcopy;
		if($accountconfig)
		{
			$this->accountconfig = $accountconfig;
		}
	}

	/* ---------------------------------------------------------- FROM EZCOPY */
	
	function dumpDatabase()
	{
		$this->ezcopy->log("Dumping database\n");
		
		// prepare the directory to which the databases will be dumped
		$this->ezcopy->prepareDBDumpDir();
		
		$dbList = $this->ezcopy->fetchDbList();
		
		foreach( $dbList as $db )
		{
			// build and execute the database dump command
			$cmd = $this->dumpDBCommand($db['User'], $db['Password'], $db['Database'], $db['Server'], $db['File']);

			$this->ezcopy->exec($cmd);
			
			$cmd = "du -k " . $this->ezcopy->getBasePath() . $this->ezcopy->dbDumpDir . $db['File'];

			$filesizeinfo = $this->ezcopy->exec($cmd);
			$filesize = str_replace( $this->ezcopy->getBasePath() . $this->ezcopy->dbDumpDir . $db['File'], '', $filesizeinfo );
			$this->ezcopy->data['db_dump_filesize'] = trim($filesize)*1024;
			
			if ( $this->ezcopy->data['db_dump_filesize' ] > 0 )
			{
				$this->ezcopy->log( $db[ 'Database' ] . ': OK (' . $this->ezcopy->MBFormat($this->ezcopy->data['db_dump_filesize']) . ")\n", 'ok');
			}
			else
			{
				$this->ezcopy->log( $db['Database'] . ' size is 0. Please check your database settings', 'critical' );
			}
		}		
	}

	function createDatabase($dbName = false)
	{
		if(!$dbName)
		{
			$dbName = $this->ezcopy->data['db'];
		}

		$this->ezcopy->log("Creating database $dbName ");
		
		$cmd = $this->createDBCommand($dbName);
				
		exec($cmd);
		
		$this->ezcopy->log("OK\n", 'ok');
	}

	function createDatabaseList()
	{
		$dbList = $this->ezcopy->fetchDbList();
		foreach( $dbList as $db )
		{
			$this->createDatabase( $db['Database'] );
		}
	}
	
	function grantDBUserAccessList()
	{
		$dbList = $this->ezcopy->fetchDbList();
		foreach( $dbList as $db )
		{
			$this->grantDBUserAccess( $db['User'], $db['Password'], $db['Database'] );
		}
	}
	function grantDBUserAccess($user = false, $pass = false, $name = false)
	{
		if(!$user)
		{
			$user = $this->ezcopy->data['db_user'];
		}
		
		if(!$pass)
		{
			$pass = $this->ezcopy->data['db_pass'];
		}
		
		if(!$name)
		{
			$name = $this->ezcopy->data['db'];
		}
		
		$this->ezcopy->log("Granting DB user $user access to database $name ");
		
		$cmd = $this->grantDBPrivilegesCommand($name, $user, $pass);
	 	
     	exec($cmd);
		
		$this->ezcopy->log("OK\n", 'ok');
	}

	function applyDatabases()
	{
		$dbList = $this->ezcopy->fetchDbList();

		foreach( $dbList as $db )
		{
			$dumpFile = $this->ezcopy->getCopyLocation() . $this->ezcopy->dbDumpDir . $db[ 'File' ];
			$this->applyDatabase( $db[ 'Database' ], $dumpFile );
		}
	}
	function applyDatabase($dbName = false, $sqlDumpFile = false)
	{
		if(!$sqlDumpFile)
		{
			$sqlDumpFile = $this->ezcopy->getCopyLocation() . $this->ezcopy->dbDumpDir . $this->ezcopy->data['mysql_file'];
		}
		
		if(!$dbName)
		{
			$dbName = $this->ezcopy->data['db'];
		}
		
		$this->ezcopy->log("Applying $sqlDumpFile to database $dbName ");
		
		// checkpoint 
		$this->ezcopy->checkpoint( 'Applying ' . $sqlDumpFile . ' to database ' . $dbName );
		
		$cmd = $this->importDBDumpCommand($dbName, $sqlDumpFile);

		exec($cmd);
		
		$this->ezcopy->log("OK\n", 'ok');
	}

	function deleteLocalDatabase()
	{
		$this->ezcopy->log("Deleting database and DB user ");

		$cmd = $this->deleteLocalDBCommand($this->ezcopy->data['db'], $this->ezcopy->data['db_user']);
				
		exec($cmd);
		
		$this->ezcopy->log("OK\n", 'ok');
	}

	/* ------------------------------------------------------ END FROM EZCOPY */

	/* ------------------------------------------------------- FROM EZUPGRADE */

	function copyDatabases()
	{
		$this->ezcopy->log("Copying databases\n", 'heading');
		
		// fetch the list of unique databases being used
		$dbList = $this->ezcopy->fetchDbList();

		// for each database
		foreach($dbList as $db)
		{
			// create new database name
			$newDBName = $this->createNewDBName($db['Database']);
			
			// if, for some strange reason, the old db name is the same as the new one
			if($db['Database'] == $newDBName)
			{
				$this->log("The old and new datebase names are the same(old: '" . $db['Database'] . "' - new: '" . $newDBName . "'). This is probably best handled manually.", 'critical');
			}
			
			// create database
			$this->createDatabase($newDBName);
			
			// apply db dump
			$sqlFile = $this->ezcopy->getDBDumpLocation() . $db['Database'] . '.sql';
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
		$versionParts = explode(".", $this->ezcopy->upgradeToVersion);
		
		// add version number to array of db name parts
		$dbNameParts[] = implode("", $versionParts);
		
		// build new db name
		$newDBName = implode("_", $dbNameParts);
		
		// return new db name
		return $newDBName;
	}

	/* --------------------------------------------------- END FROM EZUPGRADE */

	/* --------------------------------------------------------- SQL COMMANDS */ 

	function dbRootCommmand()
	{
		$cmd = "mysql -u".$this->ezcopy->dbRootUser;
		if($this->ezcopy->dbRootPass !== '')
		{
			$cmd .= " -p" . $this->ezcopy->dbRootPass;
		}

		return $cmd;
	}

	function dumpDBCommand($user, $pass, $db, $srv, $filename)
	{
		// cd /path/to/ez/;mysqldump -h localhost -uroot -proot mydb > _dbdump/mydb.sql
		return "cd ".$this->ezcopy->getBasePath().";mysqldump -h ".$srv." -u".$user." -p".$pass." ".$db." > ".$this->ezcopy->dbDumpDir.$filename;
	}

	function createDBCommand($dbName)
	{
		return "echo \"create database if not exists " . $dbName . " default character set utf8 collate utf8_general_ci\" | mysql -u".$this->dbRootCommmand();
	}

	function grantDBPrivilegesCommand($db, $user, $pass)
	{
		return "echo \"grant all privileges on " . $db . ".* to " . $user . "@localhost identified by '" . $pass . "'\" | " . $this->dbRootCommmand();
	}

	function importDBDumpCommand($dbName, $sqlDumpFile)
	{
		return $this->dbRootCommmand() . " " . $dbName . " < " . $sqlDumpFile;
	}

	function deleteLocalDBCommand($database, $username)
	{
		return "echo \"drop database " . $database . "; " .
			"revoke all privileges, grant option from '" . $username . "'@'localhost';" . 
			"drop user '" . $username . "'@'localhost';\" | " . $this->dbRootCommmand();
	}

	/* ------------------------------------------------ ACCOUNT CONFIG WIZARD */

	function canConnect($db, $user, $pass, $host='localhost')
	{
		$connection = mysql_connect('localhost', $user, $pass);

		if($connection && mysql_select_db($db, $connection))
		{
			mysql_close();
			return true;
		}
		else
		{
			mysql_close();
			return false;
		}
	}

	function canConnectAsRoot($rootuser, $rootpass)
	{
		$this->dbRootConnection = @mysql_connect('localhost', $rootuser, $rootpass);
		if($this->dbRootConnection)
		{
			return true;
		}
		else
		{
			mysql_close();
			return false;
		}
	}

	function rootUserCanCreate($dbName)
	{
		$sql = "CREATE DATABASE IF NOT EXISTS {$dbName}";
		if(mysql_query($sql, $this->dbRootConnection))
		{
			return true;
		}
		else
		{
			return false;
		}
		
	}

	function rootUserCanGrantPrivileges($user, $pass, $dbName)
	{
		$sql = "GRANT ALL PRIVILEGES ON {$dbName}.* TO {$user}@localhost IDENTIFIED BY '{$pass}'";
		if(mysql_query($sql, $this->dbRootConnection))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function rootUserCanDelete($dbName)
	{
		$sql = "DROP DATABASE {$dbName}";
		if(mysql_query($sql, $this->dbRootConnection))
		{
			mysql_close();
			return true;
		}
		else
		{
			mysql_close();
			return false;
		}
	}

}