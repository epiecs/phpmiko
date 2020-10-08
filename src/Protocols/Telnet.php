<?php

namespace Epiecs\PhpMiko\Protocols;

class Telnet implements ProtocolInterface
{
    const DEFAULTPORT = 23;
    const DEFAULTTIMEOUT = 5;

    private $connection = false;

    private $readBytes = 128;

    public function __construct($hostname, $port = null, $timeout = null)
    {
        $port = $port ?? self::DEFAULTPORT;
        $timeout = $timeout ?? self::DEFAULTTIMEOUT;

        $this->connection = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
        stream_set_timeout($this->connection, 5);

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

        if(!in_array($username, [null, ""]))
        {
            $this->read(false);
            fputs($this->connection, "{$username}\r\n");
        }

        if(!in_array($password, [null, ""]))
        {
            $this->read(false);
            fputs($this->connection, "{$password}\r\n");
        }

        return true;
    }

    public function write($command) : void
    {
        fputs($this->connection, "{$command}");
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
        $outputBuffer = "";

        while (!feof($this->connection) && $this->connection)
        {
             $read = fgets($this->connection, $this->readBytes);
             $outputBuffer .= $read;

             switch ($mode)
             {
                 case 2:
                     if(preg_match($expect, $read)) {break 2;}
                     break;
                 case 1:
                 default:
                    if($read == $expect) {break 2;}
                    break;
             }
        }

        return $outputBuffer;
    }

    /**
     * Will flush all data from the connection and return it.
     *
     * @return string The output buffer
     */

    public function flush() : string
    {
        // read with a value false keeps on reading until the end of the buffer
        $outputBuffer = $this->read(false);

        return $outputBuffer;
    }

    /**
     * Gracefully closes the connection
     *
     * @return boolean
     */

    public function disconnect() : void
    {
        fclose($this->connection);
    }
}
