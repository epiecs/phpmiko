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
	 * Contains the SSH2 object with the ssh connection to the device
	 * @var object
	 */

	private $deviceConnection;

	/**
	 * Sets base values and tries to auto laod the class based off the device_type
	 * @param array $parameters Array containing parameters.
	 *
	 * Parameters are defined as follows:
	 *
	 * $device = new \Epiecs\PhpMiko\connectionHandler([
	 *     'device_type' => 'junos',
	 *     'ip'          => '192.168.0.1',
	 *     'username'    => 'username',
	 *     'password'    => 'password',
	 *     'port'        => 22,             //defaults to 22 if not set
	 *     'secret'      => 'secret',       //default is ''
	 *     'verbose'     => false           //default is false
	 *     'raw'         => false           //default is false
	 * ]);
	 */

	public function __construct($parameters)
	{
		if(!isset($parameters['device_type']) || empty($parameters['device_type'])){ throw new \Exception("device_type must be set", 1);}
		if(!isset($parameters['ip']) || empty($parameters['ip'])){ throw new \Exception("ip must be set", 1);}

		$port = isset($parameters['port']) ? $parameters['port']   : 22;

		// Instantiate the class for the specific device
		$device_type = ucfirst(mb_strtolower($parameters['device_type']));
		$deviceClass = 'Epiecs\\PhpMiko\\' . $device_type . 'Device';

		if(!class_exists($deviceClass))
		{
			throw new \Exception("No known class for device_type {$device_type}", 1);
		}

		// TODO: username password auth. If username or password is not set attempt auth with ssh pubkey
		//http://phpseclib.sourceforge.net/ssh/2.0/auth.html#rsakey,1.0,
		//
		$sshConnection = new SSH2($parameters['ip'], $port);

		if(!$sshConnection->login($parameters['username'], $parameters['password']))
		{
			throw new \Exception("could not connect to device", 1);
		}

		$this->deviceConnection = new $deviceClass($sshConnection);

		$this->deviceConnection->secret  = isset($parameters['secret']) ? $parameters['secret']   : '';
		$this->deviceConnection->verbose = isset($parameters['verbose']) ? $parameters['verbose'] : false;
		$this->deviceConnection->raw     = isset($parameters['raw']) ? $parameters['raw']         : false;

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

	/**
	 * Sets verbose output. Defaults to true
	 * @param  boolean $trueFalse set verbose on or off
	 * @return boolean returns true when successfull
	 */

	public function verbose($trueFalse = true)
	{
		$this->deviceConnection->verbose = $trueFalse;
		return true;
	}

	/**
	 * Sets raw output. Defaults to true
	 * @param  boolean $trueFalse set raw on or off
	 * @return boolean returns true when successfull
	 */

	public function raw($trueFalse = true)
	{
		$this->deviceConnection->raw = $trueFalse;
		return true;
	}

	public function disconnect()
	{
		unset($this->deviceConnection);
		return true;
	}
}
