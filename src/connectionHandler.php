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
     * When true the output will not be sanitized. Defaults to false
     * @var boolean
     */

    public $raw = false;

    /**
     * Gives verbose output of all commands entering the connection
     * @var boolean
     */

    public $verbose = false;

	/**
	 * Contains the SSH2 object with the ssh connection to the device
	 * @var object
	 */

	private $deviceConnection;

	/**
	 * Sets base values and tries to auto load the class based off the device_type
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
	 *     'secret'      => 'secret',       //default is ''. eg. enable password for cisco
	 *     'verbose'     => false           //default is false
	 *     'raw'         => false           //default is false, returns raw unfiltered output if true
	 * ]);
	 */

	public function __construct($parameters)
	{
		if(!isset($parameters['device_type']) || empty($parameters['device_type'])){ throw new \Exception("device_type must be set", 1);}
		if(!isset($parameters['ip']) || empty($parameters['ip'])){ throw new \Exception("ip must be set", 1);}

		$port = isset($parameters['port']) ? $parameters['port'] : 22;

		// Check if the class for the specific device exists
		$device_type = strtolower($parameters['device_type']);
		$deviceClass = 'Epiecs\\PhpMiko\\Devices\\' . $device_type;

		if(!class_exists($deviceClass))
		{
			throw new \Exception("No known class for device_type {$device_type}", 1);
		}

		$sshConnection = new SSH2($parameters['ip'], $port);

		if(!$sshConnection->login($parameters['username'], $parameters['password']))
		{
			throw new \Exception("could not connect to device", 1);
		}

		$this->deviceConnection = new $deviceClass($sshConnection);

		$this->deviceConnection->secret  = isset($parameters['secret']) ? $parameters['secret']   : '';


		$this->verbose = isset($parameters['verbose']) ? $parameters['verbose'] : false;
        $this->raw     = isset($parameters['raw']) ? $parameters['raw']         : false;

        $this->verbose ? define('NET_SSH2_LOGGING', 2) : define('NET_SSH2_LOGGING', 0);

		return true;
	}


	/**
	 * Public methods
	 */

	public function cli($commands)
	{
        $commands = is_array($commands) ? $commands : array($commands);

        $output = $this->deviceConnection->cli($commands);
        $output = $this->raw ? $output : $this->cleanOutput($output, $commands);

        echo ($this->verbose ? $this->deviceConnection->conn->getLog() : false);

        return $output;
	}

	public function operation($commands)
	{
        $commands = is_array($commands) ? $commands : array($commands);

		$output = $this->deviceConnection->operation($commands);
        $output = $this->raw ? $output : $this->cleanOutput($output, $commands);

        echo ($this->verbose ? $this->deviceConnection->conn->getLog() : false);

        return $output;
	}

	public function configure($commands)
	{
        $commands = is_array($commands) ? $commands : array($commands);

		$output = $this->deviceConnection->configure($commands);
        $output = $this->raw ? $output : $this->cleanOutput($output, $commands);

        echo ($this->verbose ? $this->deviceConnection->conn->getLog() : false);

        return $output;
	}

    /**
     * Prep an array with all regex patterns to clean up the ouput. first we walk all provided
     * commands and turn them into a regex pattern.
     *
     * At the end we add some extra patterns + fetch all patterns from the device class in order
     * to fully clean up the output.
     *
     * @param  array $output   All captured output from earlier commands
     * @param  array $commands All commands that have been run
     * @return array The array with cleaned output in all members
     */

    private function cleanOutput($output, $commands)
    {
        /**
         * Prep an array with all regex patterns to clean up the ouput. first we walk all provided
         * commands and turn them into a regex pattern.
         *
         * At the end we add some extra patterns + fetch all pattersn from the device class in order
         * to fully clean up the output.
         */

        $cleanupOutputPatterns = array_values($commands);
        array_walk($cleanupOutputPatterns, function(&$value, &$key)
        {
            $value = '/' . preg_quote($value, '/') . '\s/m';
        });

        $cleanupOutputPatterns    = array_merge(
            $cleanupOutputPatterns,
            $this->deviceConnection->cleanupPatterns(), //Fetch the patterns from the device class
            [
                '/^\r?\n/m' //Filter empty lines
            ]
        );

        $output = array_map(function($value) use ($cleanupOutputPatterns){
            $value = preg_replace($cleanupOutputPatterns, '', $value);
            
            return $value;
        }, $output);

        return $output;
    }

	/**
	 * Sets verbose output. Defaults to true
	 * @param  boolean $trueFalse set verbose on or off
	 * @return boolean returns true when successfull
	 */

	public function verbose($trueFalse = true)
	{
		$this->verbose = $trueFalse;
        $this->verbose ? define('NET_SSH2_LOGGING', 2) : define('NET_SSH2_LOGGING', 0);

		return true;
	}

    /**
     * Disconnects from the device
     * @return boolean returns true when successfull
     */

	public function disconnect()
	{
		unset($this->deviceConnection);
		return true;
	}
}
