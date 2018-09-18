PhpMiko
=======

<3 NetMiko but I'm a php developer. So I've decided to build a php alternative

Mad respect to [Kirk Byers](https://github.com/ktbyers/netmiko) for creating netmiko and being a huge inspiration to this project.

I'll add this project to packagist once it is somewhat more finished.

#### Requires:

- Php >= 7.1

#### Supports:

###### Implemented

- Juniper Junos

###### Planned

- Aruba
- Avaya
- Cisco ios
- Linux
- Powershell
- Vyos
- ...

## Examples:

#### Connecting to a device

```php
$device = new \Epiecs\PhpMiko\connectionHandler([
	'device_type' => 'junos',
	'ip'          => '192.168.0.1',
	'username'    => 'username',
	'password'    => 'password',
	'port'        => 22,             //defaults to 22 if not set
	'secret'      => 'secret',       //default is ''
	'verbose'     => false           //default is false
	'raw'         => false           //default is false
]);
```

When the __raw__ flag is set to true PhpMiko will not clean up output and return it as is (minus a few hidden characters so you at least get all textual output).

__Secret__ is used when a runlevel requires a different password. Like for example enable mode in Cisco ios. You would put the enable password in the secret field.

__Verbose__ provides debugging information for each step that is being performed.

#### Sending commands

When sending commands you can either provide the resp. function with a string or an array consisting of commands. Either way is fine. When providing an array the commands are run in order.

After execution all (cleaned) output is returned.

###### Sending one command as string

```php
echo $device->operation('show interfaces ge-0/0/0');
```

###### Sending one command as an array

```php
echo $device->operation([
	'show interfaces ge-0/0/0',
]);
```

###### Sending multiple commands

```php
echo $device->operation([
	'show interfaces ge-0/0/0',
	'show interfaces ge-0/0/1',
]);
```

#### Command types

PhpMiko has 3 distinct mechanisms to send commands:

- cli
- operation
- configure

###### cli

Runs one or more cli commands on a device.
Eg. the standard linux cli in junos or user exec mode in cisco ios

Sends commands one by one and does not chain commands. So after every command you return back to your initial state.
For example run cd /tmp and then pwd you will still get back your standard home directory. If you need to chain
commands you need to chain them with ; or &&

cd /tmp ; pwd runs both commands back to back and ignores the return code of previous commands

cd /tmp && pwd only runs pwd if the previous command (cd /tmp) returns status 0 (command successfull)

This way the user still has enough flexibility. It would have been easy to just implode all the commands with ;
or && but that would make this library to opinionated imho.

```php
echo $device->cli([
	'pwd',
	'cd /var/www ; pwd ; ls -l'
]);
```

###### operation

Runs one or more operational/enable commands on a device.
Eg. cli mode in junos or privileged exec mode in cisco ios

Sends commands one by one and does not chain commands. So after every command you return back to your initial state.

```php
echo $device->operation([
	'show interfaces terse',
	'show configuration interfaces ge-0/0/0'
]);
```

###### configure

Sends one or more configuration commands to the device.
Eg. configuration/edit mode in junos or global configuration in cisco ios

In this mode commands can be chained and executed sequentially. However this is only per set of supplied commands.

After each block of commands the configuration mode of the device will be exited.

Eg. if you go into edit mode of an interface in junos or configure terminal in cisco ios and run another configure set of commands you wont start where you left of the previous time. After each configure run is complete there will be a clean exit.

```php
echo $device->configure([
	'set interfaces ge-0/0/0 description "Test for documentation"',
	'edit interfaces ge-0/0/1',
	'set description "Sequential commands work"',
]);
```

#### Setting verbose mode

Defaults to true when calling the function. Saves a bit of typing

```php
$device->verbose();
$device->verbose(false);
```

#### Setting raw mode

Defaults to true when calling the function.

```php
$device->raw();
$device->raw(false);
```

#### Closing the connection

```php
$device->disconnect();
```
