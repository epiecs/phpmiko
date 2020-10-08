<?php

namespace Epiecs\PhpMiko\Protocols;

interface ProtocolInterface
{
    /**
     * Constants for read() mode
     */

    const READ_LITERAL = 1;
    const READ_REGEX   = 2;

    /**
     * Connects to the device on the correct port
     *
     * @param  string  $hostname the ip or hostname of the device
     * @param  int     $port     the port that is listening on the device
     * @param  int     $timeout  seconds to wait before the connection times out
     * @return boolean           returns true if connected
     */

    public function __construct(string $hostname, int $port = null, int $timeout = null);

    /**
     * Perform a login on the device
     * @param  string $username Optional username
     * @param  string $password Optional password
     * @return boolean          true if login successfull
     */

    public function login(string $username = null, string $password = null) : bool;

	/**
     * Writes the command to the connection
     *
     * @param string $command The command that needs to be written
     */

    public function write(string $command) : void;

	/**
     * Keeps reading the connection until the expect string or regex has been encountered.
     *
     * Mode 1 is literal [default]
     * Mode 2 is regex
     *
     * @param  mixed   $expect Can be false or a string. If false is given reads will continue until a there is no more output
     * @param  integer $mode   Match mode, 1 is literal [default] and 2 is regex
     * @return string          All output from the command
     */

    public function read($expect, int $mode) : string;

    /**
     * Keeps on reading the connection until all data is read. Useful to clean the terminal before sending commands
     *
     * @return string All the output that was still unread from the connection
     */

    public function flush() : string;

    /**
     * Disconnects the connection
     */

    public function disconnect() : void;
}
