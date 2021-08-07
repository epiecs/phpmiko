<?php

namespace Epiecs\PhpMiko\Devices;

use Epiecs\PhpMiko\Protocols\ProtocolInterface;

class Comware implements DeviceInterface
{
    /**
     * Holds the ssh connection
     * @var ProtocolInterface
     */

    public $conn;

    /**
     * Patterns used to read the shell
     */

    private $shellPattern    = '/<.*>$/m';
    private $systemVewPattern = '/\[.*]$/m';

    /**
     * Constructor. Expects a ssh2 object
     * @param ProtocolInterface $connection SSH2 object
     */

    public function __construct(ProtocolInterface $connection)
    {
        $this->conn = $connection;

    }

    public function cli($commands): array
    {
        $output = [];

        $this->conn->write("screen-length disable\n");

        // First we do a cleanup of the shell
        $this->conn->flush();

        foreach($commands as $command)
        {
            $this->conn->write("{$command}\n");
            $output[$command] = $this->conn->read($this->shellPattern, $this->conn::READ_REGEX);
        }

        return $output;
    }

    public function operation($commands): array
    {
        // Operation is just user mode.
        return $this->cli($commands);
    }

    public function configure($commands): array
    {
        $output = [];


        $this->conn->write("screen-length disable\n");
        // Go to system view
        $this->conn->write("system-view\n");
        $this->conn->flush();

        foreach($commands as $command)
        {
            $this->conn->write("{$command}\n");
            $output[$command] = $this->conn->read($this->systemVewPattern, $this->conn::READ_REGEX);
        }

        // go back to user view
        do {
            $this->conn->write("quit\n");
            $quit_output = $this->conn->read($this->systemVewPattern, $this->conn::READ_LITERAL);
        } while (! preg_match($this->shellPattern, $quit_output));

        return $output;
    }

    public function cleanupPatterns()
    {
        return [
            $this->shellPattern,
            $this->systemVewPattern,
        ];
    }
}
