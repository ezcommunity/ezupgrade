<?php

class requirements
{
	function requirements()
	{
		
	}
	
	function runMethod($methodName)
	{
		$result = $this->$methodName();
				
		// if the requirement is not met
		if(!$result)
		{
			// halt script execution
			echo "The requirement for $methodName was not met.\n";
			exit(); 	
		}
	}

	// check for PHP v. 5
	function PHP5()
	{
		if ( version_compare( phpversion(), '5.1' ) < 0 )
		{
			return false;
		}
		
		return true;
	}
	
	function eZC20082()
	{
		// TODO: Create function for checking for eZ components 2008.2
		return true;
	}
	function eZC200921()
	{
		// TODO: Create function for checking for eZ components 2009.2.1
		return true;
	}
}

?>