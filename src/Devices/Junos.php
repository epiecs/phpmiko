<?php

namespace Epiecs\PhpMiko\Devices;

class Junos implements DeviceInterface
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

    private $shellPattern             = '/.*@.*:RE:[0-9]{1,2}%\s/m';
    private $operationalModePattern   = '/.*@.*>\s/m';
    private $configurationModePattern = '/.*@.*#\s/m';


    // TODO: Take into account [yes,no] questions in the pattern. Maybe use a OR match in the pattern?
    // request system power-off at 1907292300 all-members
    // warning: This command will halt all the members.
    // If planning to halt only one member use the member option
    // Power Off the system at 1907292300? [yes,no]

    // TODO: maybe send enter at the beginning of the prompt and fetch the prompt and use that as a pattern?

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

        // First we do a basic cleanup of the shell (wait until we have shell)
		$this->conn->read($this->shellPattern, $this->conn::READ_REGEX);

		// Go to operational (cli) mode
        $this->conn->write("cli\n");
        $this->conn->read($this->operationalModePattern, $this->conn::READ_REGEX);
        $this->conn->write("set cli screen-length 10000\n");
        $this->conn->read($this->operationalModePattern, $this->conn::READ_REGEX);
        $this->conn->write("set cli screen-width 400\n");
        $this->conn->read($this->operationalModePattern, $this->conn::READ_REGEX);

		// Loop commands
		foreach($commands as $command)
		{
			$this->conn->write("{$command}\n");

			// Read the data and add it to the output
			$output[$command] = $this->conn->read($this->operationalModePattern, $this->conn::READ_REGEX);
		}

		// Exit the cli
		$this->conn->write("set cli screen-length 93\n");
		$this->conn->write("exit\n");
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

        // First we do a basic cleanup of the shell (wait until we have shell)
        $this->conn->read($this->shellPattern, $this->conn::READ_REGEX);

        // Go to operational (cli) mode
        $this->conn->write("cli\n");
        $this->conn->read($this->operationalModePattern, $this->conn::READ_REGEX);
        $this->conn->write("set cli screen-length 10000\n");
        $this->conn->read($this->operationalModePattern, $this->conn::READ_REGEX);
        $this->conn->write("set cli screen-width 400\n");
        $this->conn->read($this->operationalModePattern, $this->conn::READ_REGEX);

		$this->conn->write("configure\n");
		$this->conn->read($this->configurationModePattern, $this->conn::READ_REGEX);

        // Loop commands
        foreach($commands as $command)
        {
            $this->conn->write("{$command}\n");

            // Read the data and add it to the output
            $output[$command] = $this->conn->read($this->configurationModePattern, $this->conn::READ_REGEX);
        }

        // Exit the cli
        $this->conn->write("exit configuration-mode\n");
        $this->conn->read($this->operationalModePattern, $this->conn::READ_REGEX);
        $this->conn->write("set cli screen-length 93\n");
        $this->conn->write("exit\n");
        $this->conn->read($this->shellPattern, $this->conn::READ_REGEX);

        return $output;
    }

    public function cleanupPatterns()
    {
        return [
            $this->shellPattern,
            $this->operationalModePattern,
            $this->configurationModePattern,
            '/\[edit.*\]/',
            '/{master:.*}/',
            '/{backup:.*}/',
            '/{line.*}/',
            '/{primary.*}/',
            '/{secondary.*}/',
        ];
    }
}
