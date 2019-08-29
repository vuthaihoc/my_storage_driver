<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-08-29
 * Time: 14:46
 */

require __DIR__ ."/../vendor/autoload.php";

ini_set( 'display_errors', 1);
error_reporting(E_ALL);

$config = [
	'driver'     => 'lafly',
	'host'       => 'http://localhost/flystorage/public',
	'username'   => 'judah.yundt@example.net',
	'password'   => 'secret',
	'connection' => 'local_001',
	'cdn' => 'http://localhost/flystorage/public',
];

$adapter = new \Thikdev\LaFly\LaFlyAdapter( $config );

$cache_store = new \Illuminate\Cache\ArrayStore();

$adapter->setCacheStore( $cache_store );

$contents = $adapter->listContents();

var_dump( $contents );