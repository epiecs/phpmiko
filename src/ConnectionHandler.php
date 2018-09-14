<?php

namespace Epiecs\PhpMiko;

use phpseclib\Net\SSH2;
use phpseclib\Crypt\RSA;

/**
*  Connection class
*
*  Instantiates the device object and loads the correspondig class for that type of cli.
*
*  @author Gregory Bers
*/

class ConnectionHandler
{
	/**
	 * The type of device. Eg. Junos, cisco_ios, ...
	 * @var string
	 */

	public $device_type = '';

	/**
	 * The ip address of the device
	 * @var string
	 */

	public $ip = '';

	/**
	 * The ssh username
	 * @var string
	 */

	public $username = '';

	/**
	 * The ssh password
	 * @var string
	 */

	public $password = '';

	/**
	 * The port used for the ssh connection
	 * @var int
	 */

	public $port = 22;

	/**
	 * Additional password to access operation mode. Eg. enable mode on cisco_ios
	 * @var string
	 */

	public $secret = '';

	/**
	 * Turn verbose mode on or off. Provides a lot of output for debugging when on.
	 * @var boolean
	 */

	public $verbose = false;

	/**
	 * Contains the SSH2 object with the ssh connection to the device
	 * @var object
	 */

	private $deviceConnection;

	public function __construct($parameters)
	{
		// Loop the parameters and set the property if it property_exists
		foreach($parameters as $key => $value)
		{
			if(property_exists($this, $key))
			{
				$this->$key = $value;
			}
		}

		if(empty($this->device_type)){ throw new \Exception("device_type must be set", 1);}
		if(empty($this->ip)){ throw new \Exception("ip must be set", 1);}

		// Instantiate the class for the specific device
		$this->device_type = ucfirst(mb_strtolower($this->device_type));
		$deviceClass = 'Epiecs\\PhpMiko\\' . $this->device_type . 'Device';

		if(!class_exists($deviceClass))
		{
			throw new \Exception("No known class for device_type {$this->device_type}", 1);
		}

		// TODO: username password auth. If username or password is not set attempt auth with ssh pubkey
		//http://phpseclib.sourceforge.net/ssh/2.0/auth.html#rsakey,1.0,
		//
		$sshConnection = new SSH2($this->ip);

		if (!$sshConnection->login($this->username, $this->password))
		{
			throw new \Exception("could not connect to device", 1);
		}

		$this->deviceConnection = new $deviceClass($sshConnection);

		$this->deviceConnection->verbose = $this->verbose;
		$this->deviceConnection->secret  = $this->secret;

		return true;
	}


	/**
	 * Public methods
	 */

	public function cli($commands)
	{
		return $this->deviceConnection->cli($commands);
	}

	public function operation($commands)
	{
		return $this->deviceConnection->operation($commands);
	}

	public function configure($commands)
	{
		return $this->deviceConnection->configure($commands);
	}

	public function disconnect()
	{
		unset($this->deviceConnection);

		return true;
	}

	//__set voor verbose op te vangen
	//indien verbose deviceconn obj aanpassen
	//ook met verbose rekening houden met aanmaken van object
}
