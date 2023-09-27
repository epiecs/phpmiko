<?php

namespace Epiecs\PhpMiko;
use phpseclib3\Exception\UnableToConnectException;
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

	protected $deviceConnection;

	/**
	 * Sets base values and tries to auto load the class based off the device_type
	 * @param array $parameters Array containing parameters.
	 *
	 * Parameters are defined as follows:
	 *
	 * $device = new \Epiecs\PhpMiko\connectionHandler([
	 *     'device_type' => 'junos',
	 *     'hostname'    => '192.168.0.1',
	 *     'username'    => 'username',
	 *     'password'    => 'password',
	 *     'protocol'    => 'ssh',          //default is ssh
	 *     'port'        => 22,             //defaults to protocol default if not set
	 *     'secret'      => 'secret',       //default is ''. eg. enable password for cisco
	 *     'verbose'     => false           //default is false
	 *     'raw'         => false           //default is false, returns raw unfiltered output if true
	 * ]);
	 */

	public function __construct(array $parameters)
	{
		if(!isset($parameters['device_type']) || empty($parameters['device_type'])){ throw new \Exception("device_type must be set", 1);}
		if(!isset($parameters['hostname']) || empty($parameters['hostname'])){ throw new \Exception("hostname must be set", 1);}


        $protocol = $parameters['protocol'] ?? 'ssh';
        $port     = $parameters['port'] ?? null;

        $username = $parameters['username'] ?? null;
        $password = $parameters['password'] ?? null;

        //
		// Check if the class for the specific device exists
        // First check for explicit DeviceInterface classes given, then built in classes
        //

        $device_type = ucfirst(strtolower($parameters['device_type']));
		$builtInDeviceClass = 'Epiecs\\PhpMiko\\Devices\\' . $device_type;

        if (class_exists($parameters['device_type'])  && is_subclass_of($parameters['device_type'], DeviceInterface::class)) {
            $deviceClass = $parameters['device_type'];
        } elseif (class_exists($builtInDeviceClass)) {
            $deviceClass = $builtInDeviceClass;
        } else {
			throw new \Exception("No known class for device_type {$device_type}", 1);
		}


        //
		// Check if the class for the specific protocol exists
        //

        $protocol      = ucfirst(strtolower($protocol));
		$protocolClass = 'Epiecs\\PhpMiko\\Protocols\\' . $protocol;

		if(!class_exists($protocolClass))
		{
			throw new \Exception("No known class for protocol {$protocol}", 1);
		}

        if(!$connection = new $protocolClass($parameters['hostname'], $port))
        {
            throw new \Exception("Could not instantiate {$protocol}", 1);
        }

        try 
        {
            if(!$connection->login($username, $password))
            {
                throw new \Exception("Login failed.", 1);
            }
        } catch (UnableToConnectException $error) {
            throw new \Exception("Connection refused", 1);
        }

		$this->deviceConnection = new $deviceClass($connection);

		$this->deviceConnection->secret  = $parameters['secret'] ?? '';

		$this->verbose = $parameters['verbose'] ?? false;
        $this->raw     = $parameters['raw'] ?? false;

        // if(!defined('NET_SSH2_LOGGING'))
        // {
        //     $this->verbose ? define('NET_SSH2_LOGGING', 2) : define('NET_SSH2_LOGGING', 0);
        // }

		return true;
	}


	/**
	 * Public methods
	 */

	public function cli($commands) : array
	{
        $commands = is_array($commands) ? $commands : array($commands);

        $output = $this->deviceConnection->cli($commands);
        $output = $this->raw ? $output : $this->cleanOutput($output, $commands);

        echo ($this->verbose ? $this->deviceConnection->conn->getLog() : false);

        return $output;
	}

	public function operation($commands) : array
	{
        $commands = is_array($commands) ? $commands : array($commands);

		$output = $this->deviceConnection->operation($commands);
        $output = $this->raw ? $output : $this->cleanOutput($output, $commands);

        echo ($this->verbose ? $this->deviceConnection->conn->getLog() : false);

        return $output;
	}

	public function configure($commands) : array
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

     private function cleanOutput(array $output, array $commands) : array
     {
         /**
          * Prep an array with all regex patterns to clean up the ouput. first we walk all provided
          * commands and turn them into a regex pattern.
          *
          * When we create the patterns we reduce the command to the shorthand version and create a eager regex
          * based on that command.
          *
          * The reason for this is that the first line of output contains the command that has been executed.
          * Although we do clean up the command that was run in the connection handler later on we still face the
          * issue with some switch os-es auto completing the command when there is a slight difference.
          *
          * eg. when we run the command 'show interface ge-0/0/0' junos will correct it to 'show interfaceS ge-0/0/0'
          * as such the regex for the run command wont detect this in the cleanoutput. 'sh int ge-0/0/0' will do
          * the same.
          *
          * So we take the command and split it on whitespace a regex string that eagerly checks for words/$parameters
          * containing the first 2 letters of each word. If there is only one character then this character is used.
          */

          $cleanupOutputPatterns = array_values($commands);

          array_walk($cleanupOutputPatterns, function(&$value, $key)
          {
              // From the beginning of the command match all text in between whitespace and match only the first
              // two letters or if there is only one character match that

              $regex = '/((?<=\s|^)\w{1,2})/m';

              preg_match_all($regex, $value, $matches, PREG_SET_ORDER);
              $baseMatches = array_column($matches, 0);
              array_walk($baseMatches, 'preg_quote');

              $value = "/^" . implode(".*", $baseMatches) . ".*/m";
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

        if(!defined('NET_SSH2_LOGGING'))
        {
            $this->verbose ? define('NET_SSH2_LOGGING', 2) : define('NET_SSH2_LOGGING', 0);
        }

		return true;
	}

    /**
     * Disconnects from the device
     * @return boolean returns true when successfull
     */

	public function disconnect() : void
	{
        $this->deviceConnection->conn->disconnect();
		unset($this->deviceConnection);
	}

    public function __destruct()
    {
        $this->disconnect();
    }
}
