<?php

namespace Epiecs\PhpMiko\Devices;

class Cisco_ios implements DeviceInterface
{
	/**
	 * Holds the ssh connection
	 * @var object
	 */

	public $conn;

	/**
	 * Contains the secret password if one is needed. Eg. Enable mode on a cisco device.
	 * @var boolean
	 */

	public $secret = '';

    /**
     * Patterns used to read the shell
     */

    private $shellPattern              = '/.*>$/m';
    private $privilegedExecModePattern = '/.*#$/m';
    private $configurationModePattern  = '/.*\([\w-]+\)#$/m';

    /**
	 * Constructor. Expects a ssh2 object
	 * @param object $connection SSH2 object
	 */

	public function __construct($connection)
	{
		$this->conn = $connection;
	}

	/**
	 * Sends one or more cli command(s)
	 * @param  mixed $commands String with one command or array containing multiple commands
	 * @return string           Returns all output
	 */

    public function cli($commands) : array
    {
		$output = array();

        // First we do a basic cleanup of the shell
        $this->conn->read($this->shellPattern, $this->conn::READ_REGEX);

		foreach($commands as $command)
		{
            $this->conn->write("{$command}\n");
            $output[$command] = $this->conn->read($this->shellPattern, $this->conn::READ_REGEX);
        }

        return $output;
    }

	/**
	 * Sends one or more  operation command(s)
	 * @param  mixed $commands String with one command or array containing multiple commands
	 * @return string           Returns all output
	 */

    public function operation($commands) : array
    {
        $output = array();

        // First we do a basic cleanup of the shell
		$this->conn->read($this->shellPattern, $this->conn::READ_REGEX);

		// Go to privileged exec mode
        $this->conn->write("enable\n");

        if($this->secret != '')
        {
            $this->conn->read("Password:");
            $this->conn->write("{$this->secret}\n");
            $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX);
        }

        $this->conn->write("terminal length 0\n");

        // Clean up the shell
        $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX);

		// Loop commands
		foreach($commands as $command)
		{
			$this->conn->write("{$command}\n");

			// Read the data and add it to the output
			$output[$command] = $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX);
		}

		// Exit the cli
		$this->conn->write("terminal no length\n");
		$this->conn->write("disable\n");
        $this->conn->read($this->shellPattern, $this->conn::READ_REGEX);

		return $output;
    }

	/**
	 * Sends one or more  configuration command(s)
	 * @param  mixed $commands String with one command or array containing multiple commands
	 * @return string           Returns all output
	 */

    public function configure($commands) : array
    {
        $output = array();

        // First we do a basic cleanup of the shell
		$this->conn->read($this->shellPattern, $this->conn::READ_REGEX);

		// Go to privileged exec mode
        $this->conn->write("enable\n");

        if($this->secret != '')
        {
            $this->conn->read("Password:");
            $this->conn->write("{$this->secret}\n");
            $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX);
        }

        $this->conn->write("terminal length 0\n");

        // Clean up the shell
        $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX);

        $this->conn->write("configure terminal\n");
        $this->conn->read($this->configurationModePattern, $this->conn::READ_REGEX); //Clear with full read

		// Loop commands
		foreach($commands as $command)
		{
			$this->conn->write("{$command}\n");

			// Read the data and add it to the output
			$output[$command] = $this->conn->read($this->configurationModePattern, $this->conn::READ_REGEX);
		}

		// Exit the cli
		$this->conn->write("exit\n");
		$this->conn->write("terminal no length\n");
        $this->conn->write("disable\n");
		$this->conn->read($this->shellPattern, $this->conn::READ_REGEX);

		return $output;
    }

    public function cleanupPatterns()
    {
        return [
            $this->shellPattern,
            $this->privilegedExecModePattern,
            $this->configurationModePattern
        ];
    }
}
