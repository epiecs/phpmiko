<?php

namespace Epiecs\PhpMiko\Devices;

interface DeviceInterface
{
	/**
	 * Runs one or more cli commands on a device.
	 * Eg. the standard linux cli in junos or user exec mode in cisco ios
	 *
     * Commands can be chained and executed sequentially. However this is only per set of supplied commands.
	 * After each block of commands the configuration mode of the device will be exited.
	 *
	 * @param mixed $commands Either a string containing one command or an array containing multiple command's
	 * @return array Returns an array with the output of each command with the command as key
	 */

    public function cli($commands) : array;

	/**
	 * Runs one or more operational/enable commands on a device.
	 * Eg. cli mode in junos or privileged exec mode in cisco ios
	 *
     * Commands can be chained and executed sequentially. However this is only per set of supplied commands.
	 * After each block of commands the configuration mode of the device will be exited.
	 *
	 * @param mixed $commands Either a string containing one command or an array containing multiple command's
	 * @return array Returns an array with the output of each command with the command as key
	 */

    public function operation($commands) : array;

	/**
	 * Sends one or more configuration commands to the device.
	 * Eg. configuration/edit mode in junos or global configuration in cisco ios
	 *
	 * Commands can be chained and executed sequentially. However this is only per set of supplied commands.
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
