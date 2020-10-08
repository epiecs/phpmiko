<?php

namespace Epiecs\PhpMiko\Protocols;

use phpseclib\Net\SSH2;
use phpseclib\Crypt\RSA;

class Ssh implements ProtocolInterface
{
    const DEFAULTPORT = 22;
    const DEFAULTTIMEOUT = 5;

    private $connection = false;

    public function __construct($hostname, $port = null, $timeout = null)
    {
        $port = $port ?? self::DEFAULTPORT;
        $timeout = $timeout ?? self::DEFAULTTIMEOUT;

        $this->connection = new SSH2($hostname, $port, $timeout);

        return $this->connection;
    }

    /**
     * Will try and log in with the given password and/or usename.
     * @param boolean $username Optional username
     * @param boolean $password Optional password
     */

    public function login($username = null, $password = null) : bool
    {
        /**
         * If the read value is null it means the prompt has stopped at a password/username input
         */

        if(!in_array($username, [null, ""]) && !in_array($password, [null, ""]))
        {
            return $this->connection->login($username, $password);
        }

        return true;
    }

    public function write($command) : void
    {
        $this->connection->write($command);
    }

    /**
     * Keeps on reading the connection until the expect is matched
     *
     * Mode 1 is literal [default]
     * Mode 2 is regex
     *
     * @param  mixed   $expect Can be false or a string. If false is given reads will continue until a there is no more output
     * @param  integer $mode   Match mode, 1 is literal [default] and 2 is regex
     * @return string          All output from the command
     */

    public function read($expect, $mode = 1) : string
    {
        return $this->connection->read($expect, $mode);
    }

    /**
     * Will flush all data from the connection and return it.
     *
     * @return string The output buffer
     */

    public function flush() : string
    {
        return $this->connection->read('', 1);
    }

    /**
     * Gracefully closes the connection
     *
     * @return boolean
     */

    public function disconnect() : void
    {
        $this->connection->disconnect();
    }
}
