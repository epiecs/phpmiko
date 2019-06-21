<?php

namespace Epiecs\PhpMiko\Devices;

interface deviceInterface
{
	/**
	 * Runs one or more cli commands on a device.
	 * Eg. the standard linux cli in junos or user exec mode in cisco ios
	 *
	 * Sends commands one by one and does not chain commands. So after every command you return back to your initial state.
	 * For example run cd /tmp and then pwd you will still get back your standard home directory. If you need to chain
	 * commands you need to chain them with ; or &&
	 *
	 * cd /tmp ; pwd runs both commands back to back and ignores the return code of previous commands
	 *
	 * cd /tmp && pwd only runs pwd if the previous command (cd /tmp) returns status 0 (command successfull)
	 *
	 * This way the user still has enough flexibility. It would have been easy to just implode all the commands with ;
	 * or && but that would make this library to opiniated imho.
	 *
	 * @param mixed $commands Either a string containing one command or an array containing multiple command's
	 * @return array Returns an array with the output of each command with the command as key
	 */

    public function cli($commands) : array;

	/**
	 * Runs one or more operational/enable commands on a device.
	 * Eg. cli mode in junos or privileged exec mode in cisco ios
	 *
	 * Sends commands one by one and does not chain commands. So after every command you return back to your initial state.
	 *
	 * @param mixed $commands Either a string containing one command or an array containing multiple command's
	 * @return array Returns an array with the output of each command with the command as key
	 */

    public function operation($commands) : array;

	/**
	 * Sends one or more configuration commands to the device.
	 * Eg. configuration/edit mode in junos or global configuration in cisco ios
	 *
	 * In this mode commands can be chained and executed sequentially. However this is only per set of supplied commands.
	 * After each block of commands the configuration mode of the device will be exited.
	 *
	 * @param mixed $commands Either a string containing one command or an array containing multiple command's
	 * @return array Returns an array with the output of each command with the command as key
	 */

    public function configure($commands) : array;

    /**
	 * Returns all patterns used for the device connection that are needed to clean up output
	 *
	 * @return array Returns an array with regex patterns neeeded for cleanup purposes
	 */

    public function cleanupPatterns();
}
