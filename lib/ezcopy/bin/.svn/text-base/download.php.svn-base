#!/usr/bin/php
<?php

include_once('classes/ezcopy.php');

$copy = new eZCopy;

// get arguments
$identifier		= $argv[1];
$remoteFile		= $argv[2];
$localFile		= $argv[3];

// if additional arguments are provided
if(isset($argv[4]))
{
	$copy->setAdditionalOptions(array_slice($argv, 4));
}

// get details for account
$copy->selectAccount($identifier);

// log in
$copy->logInSFTP();

// download
$copy->downloadFile($remoteFile, $localFile);

?>