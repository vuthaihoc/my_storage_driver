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

$cache_repository = new \Illuminate\Cache\Repository( $cache_store );

$adapter->setCacheRepository( $cache_repository );

$contents = $adapter->listContents();

var_dump( $contents );

$path = 'write.txt';

echo '\n URL : ' . $adapter->getUrl( $path );
echo '\n Temporary URL : ' . $adapter->getTemporaryUrl( $path, \Carbon\Carbon::now()->addMinutes(10));