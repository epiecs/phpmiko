<?php

namespace Epiecs\PhpMiko;

use phpseclib\Net\SSH2;
use JJG\Ping;

/**
*  Connection class
*
*  Instantiates the device object and loads the correspondig class for that type of cli.
*
*  @author Gregory Bers
*/

class Connection
{
	public $device_type = '';
	public $ip = '';
	public $username = '';
	public $password = '';
	public $port = 22;
	public $secret = '';
	public $verbose = '';

	//integrate function in exitsing task
	//init git
	//check methods
	//create interfaces

	public function __construct()
	{
		//pass array to construct and fill in vars
		//check for required fields set in private array
	}

	/**
	 * Getters and Setters
	 */

	 /**
	  * Set the ttl (in hops).
	  *
	  * @param int $ttl
	  *   TTL in hops.
	  */
	 public function setTtl($ttl) {
	   $this->ttl = $ttl;
	 }

	/**
	 * Magic Methods
	 */



	public function __toString()
	{
		// return json representation of object
	}

	public function __debugInfo()
	{
		// return array with vars
	}
}
