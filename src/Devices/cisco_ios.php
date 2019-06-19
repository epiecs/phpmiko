<?php

namespace Epiecs\PhpMiko\Devices;

class Cisco_ios implements deviceInterface
{
	/**
	 * Holds the ssh connection
	 * @var object
	 */

	private $conn;

	/**
	 * Contains the secret password if one is needed. Eg. Enable mode on a cisco device.
	 * @var boolean
	 */

	public $secret = '';

	/**
	 * Give more verbose output. eg. tell which command is being sent
	 * @var boolean
	 */

	public $verbose = false;

	/**
	 * Output raw text without cleanup
	 * @var boolean
	 */

	public $raw = false;

	/**
	 * Constructor. Expects a ssh2 object
	 * @param object $connection SSH2 object
	 */

    private $shellPattern              = '/.*>.*$/m';
    private $privilegedExecModePattern = '/.*#.*$/m';
    private $configurationModePattern  = '/.*\(config\)#.*$/m';

	public function __construct($connection)
	{
		$this->conn = $connection;
	}

	/**
	 * Sends one or more  cli command(s)
	 * @param  mixed $commands String with one command or array containing multiple commands
	 * @return string           Returns all output
	 */

    public function cli($commands) : string
    {
		$output = '';
		$commandList = is_array($commands) ? $commands : array($commands);

		foreach($commandList as $command)
		{
			if($this->verbose){ echo "Executing command :: {$command} \n"; }
			$output .= $this->conn->exec($command);
		}

        return $output;
    }

	/**
	 * Sends one or more  operation command(s)
	 * @param  mixed $commands String with one command or array containing multiple commands
	 * @return string           Returns all output
	 */

    public function operation($commands) : string
    {
        $output      = '';
		$preConfig   = '';
		$postConfig  = '';
		$commandList = is_array($commands) ? $commands : array($commands);

        // First we do a basic cleanup of the shell
		$this->conn->write("\n");

		$preConfig .=  $this->conn->read($this->shellPattern, $this->conn::READ_REGEX) . "\n";

		// Go to privileged exec mode
        $this->conn->write("enable\n");

        if($this->secret != '')
        {
            $preConfig .= $this->conn->read("Password:");
            $this->conn->write("{$this->secret}\n");
        }

        $preConfig .= $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX) . "\n";

        $this->conn->write("terminal length 0\n");
        $preConfig .= $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX) . "\n"; //Clear with full read

		if($this->verbose){ echo $preConfig; echo "\n"; }

		// Loop commands
		foreach($commandList as $command)
		{
			if($this->verbose){ echo "Executing command :: {$command} \n"; }

			$this->conn->write("{$command}\n");

			// Read the data and add it to the output
			$output .= $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX) . "\n";
		}

		// Exit the cli
		$this->conn->write("terminal no length\n");
		$postConfig .= $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX);

		$this->conn->write("disable\n");
		$postConfig .= $this->conn->read($this->shellPattern, $this->conn::READ_REGEX);

		if($this->verbose){ echo $postConfig; echo "\n"; }

		if(!$this->raw)
		{
			if($this->verbose){ echo "Cleaning up output"; echo "\n"; }
            $output = $this->cleanOutput($output, $commandList);
		}

		return $output;
    }

	/**
	 * Sends one or more  configuration command(s)
	 * @param  mixed $commands String with one command or array containing multiple commands
	 * @return string           Returns all output
	 */

    public function configure($commands) : string
    {
        $output      = '';
		$preConfig   = '';
		$postConfig  = '';
		$commandList = is_array($commands) ? $commands : array($commands);

        // First we do a basic cleanup of the shell
		$this->conn->write("\n");

		$preConfig .=  $this->conn->read($this->shellPattern, $this->conn::READ_REGEX) . "\n";

		// Go to privileged exec mode
        $this->conn->write("enable\n");

        if($this->secret != '')
        {
            $preConfig .= $this->conn->read("Password:");
            $this->conn->write("{$this->secret}\n");
        }

        $preConfig .= $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX) . "\n";

        $this->conn->write("terminal length 0\n");
        $preConfig .= $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX) . "\n"; //Clear with full read

        $this->conn->write("configure terminal\n");
        $preConfig .= $this->conn->read($this->configurationModePattern, $this->conn::READ_REGEX) . "\n"; //Clear with full read

		if($this->verbose){ echo $preConfig; echo "\n"; }

		// Loop commands
		foreach($commandList as $command)
		{
			if($this->verbose){ echo "Executing command :: {$command} \n"; }

			$this->conn->write("{$command}\n");

			// Read the data and add it to the output
			$output .= $this->conn->read($this->configurationModePattern, $this->conn::READ_REGEX) . "\n";
		}

		// Exit the cli
		$this->conn->write("exit\n");
		$postConfig .= $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX);

		$this->conn->write("terminal no length\n");
		$postConfig .= $this->conn->read($this->privilegedExecModePattern, $this->conn::READ_REGEX);

		$this->conn->write("disable\n");
		$postConfig .= $this->conn->read($this->shellPattern, $this->conn::READ_REGEX);

		if($this->verbose){ echo $postConfig; echo "\n"; }

		if(!$this->raw)
		{
			if($this->verbose){ echo "Cleaning up output"; echo "\n"; }
            $output = $this->cleanOutput($output, $commandList);
		}

		return $output;
    }

    private function cleanOutput($output, $commands)
    {
        /**
         * Prep an array with all regex patterns to clean up the ouput. first we walk all provided
         * commands and turn them into a regex pattern.
         *
         * At the end we add some extra patterns in orde to fully clean up the output.
         */

        $cleanupOutputPatterns = array_values($commands);
        array_walk($cleanupOutputPatterns, function(&$value, &$key)
        {
            $value = '/' . preg_quote($value, '/') . '\s/m';
        });

        $cleanupOutputPatterns    = array_merge($cleanupOutputPatterns, [
            $this->shellPattern,
            $this->privilegedExecModePattern,
            $this->configurationModePattern,
            '/^\r?\n/m' //Filter empty lines
        ]);

        $output = preg_replace($cleanupOutputPatterns, '', $output);

        return $output;
    }
}
