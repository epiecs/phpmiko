<?php

namespace Epiecs\PhpMiko;

class JunosDevice implements deviceInterface
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
	 * Constructor. Expects a ssh2 object
	 * @param object $connection SSH2 object
	 */

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
		$output = '';
		$commandList = is_array($commands) ? $commands : array($commands);

		foreach($commandList as $command)
		{
			if($this->verbose){ echo "Executing command :: cli -c '{$command}' \n"; }
			$output .= $this->conn->exec("cli -c '{$command}'");
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

		$shellPattern             = '/.*@.*:RE:[0-9]{1,2}%\s/m';
		$operationalModePattern   = '/.*@.*>\s/m';
		$configurationModePattern = '/.*@.*#\s/m';

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
			$configurationModePattern,
			'/\[edit.*\]/',
            '/{master:.*}/',
            '/{backup:.*}/',
            '/{line.*}/',
            '/{primary.*}/',
            '/{secondary.*}/',
			'/^\r?\n/m' //Filter empty lines
		]);

		// First we do a basic cleanup of the shell
		$this->conn->write("\n\n\n\n\n\n");
		$this->conn->read($shellPattern, $this->conn::READ_REGEX) . "\n";
		$this->conn->write("echo gregory \n");
		$this->conn->read($shellPattern, $this->conn::READ_REGEX) . "\n";

		// Go to configuration mode
		$this->conn->write("cli \n");
		$preConfig .= $this->conn->read($operationalModePattern, $this->conn::READ_REGEX) . "\n";

		$this->conn->write("configure \n");
		$preConfig .= $this->conn->read($configurationModePattern, $this->conn::READ_REGEX);

		$this->conn->write("run set cli screen-length 10000 \n");
		$preConfig .= $this->conn->read($configurationModePattern, $this->conn::READ_REGEX);

		$this->conn->write("run set cli screen-width 400 \n");
		$preConfig .= $this->conn->read($configurationModePattern, $this->conn::READ_REGEX);

		$this->conn->write("run show cli \n");
		$preConfig .= $this->conn->read($configurationModePattern, $this->conn::READ_REGEX);

		if($this->verbose){ echo $preConfig; echo "\n"; }

		// Loop commands

		foreach($commandList as $command)
		{
			if($this->verbose){ echo "Executing command :: {$command} \n"; }

			$this->conn->write($command . "\n");

			// Read the data and clean it up before we add it to the output

			$output .= $this->conn->read($configurationModePattern, $this->conn::READ_REGEX) . "\n";

		}

		if($this->verbose){ echo "Cleaning up output"; echo "\n"; }
		$output = $baseOutput = preg_replace($cleanupOutputPatterns, '', $output);

		// Exit the cli
		$this->conn->write("top \n");
		$postConfig .= $this->conn->read($configurationModePattern, $this->conn::READ_REGEX);

		$this->conn->write("run set cli screen-length 55 \n");
		$postConfig .= $this->conn->read($configurationModePattern, $this->conn::READ_REGEX);

		$this->conn->write("run set cli screen-length 93 \n");
		$postConfig .= $this->conn->read($configurationModePattern, $this->conn::READ_REGEX);

		$this->conn->write("exit configuration-mode \n");
		$postConfig .= $this->conn->read($operationalModePattern, $this->conn::READ_REGEX);

		$this->conn->write("exit \n");
		$postConfig .= $this->conn->read($shellPattern, $this->conn::READ_REGEX);

		if($this->verbose){ echo $postConfig; echo "\n"; }

		return $output;
    }
}
