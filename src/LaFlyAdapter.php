<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 3/20/17
 * Time: 13:23
 */

namespace Thikdev\LaFly;


use Illuminate\Contracts\Cache\Store;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class LaFlyAdapter implements AdapterInterface {
	
	use AuthTrait;
	
	/** @var Store */
	protected $cache_store;
	
	/**
	 * LaFlyAdapter constructor.
	 */
	public function __construct($config, $connection_name = null) {
		if(!$connection_name){
			$connection_name = $config['connection'];
		}
		$this->setConfig($config);
		$this->config['cache_token_name'] = $this->config['cache_token_name'] .  $connection_name;
		$this->config['connection_name'] = $connection_name;
	}
	
	
	/**
	 * Write a new file.
	 *
	 * @param string $path
	 * @param string $contents
	 * @param Config $config Config object
	 *
	 * @return array|false false on failure file meta data on success
	 */
	public function write( $path, $contents, Config $config ) {
		return $this->_write('write', $path, $contents, $config);
	}
	
	/**
	 * Write a new file using a stream.
	 *
	 * @param string $path
	 * @param resource $resource
	 * @param Config $config Config object
	 *
	 * @return array|false false on failure file meta data on success
	 */
	public function writeStream( $path, $resource, Config $config ) {
		return $this->_write('writeStream', $path, $resource, $config);
	}
	
	/**
	 * Update a file.
	 *
	 * @param string $path
	 * @param string $contents
	 * @param Config $config Config object
	 *
	 * @return array|false false on failure file meta data on success
	 */
	public function update( $path, $contents, Config $config ) {
		return $this->_write('update', $path, $contents, $config);
	}
	
	/**
	 * Update a file using a stream.
	 *
	 * @param string $path
	 * @param resource $resource
	 * @param Config $config Config object
	 *
	 * @return array|false false on failure file meta data on success
	 */
	public function updateStream( $path, $resource, Config $config ) {
		return $this->_write('updateStream', $path, $resource, $config);
	}
	
	/**
	 * Rename a file.
	 *
	 * @param string $path
	 * @param string $newpath
	 *
	 * @return bool
	 */
	public function rename( $path, $newpath ) {
		return $this->_manipulate("rename", $path, [
			'newpath' => $newpath
		]);
	}
	
	/**
	 * Copy a file.
	 *
	 * @param string $path
	 * @param string $newpath
	 *
	 * @return bool
	 */
	public function copy( $path, $newpath ) {
		return $this->_manipulate("copy", $path, [
			'newpath' => $newpath
		]);
	}
	
	/**
	 * Delete a file.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function delete( $path ) {
		return $this->_manipulate("delete", $path);
	}
	
	/**
	 * Delete a directory.
	 *
	 * @param string $dirname
	 *
	 * @return bool
	 */
	public function deleteDir( $dirname ) {
		return $this->_manipulate("deleteDir", $dirname);
	}
	
	/**
	 * Create a directory.
	 *
	 * @param string $dirname directory name
	 * @param Config $config
	 *
	 * @return array|false
	 */
	public function createDir( $dirname, Config $config ) {
		$config = ConfigExtractor::process($config);
		return $this->_manipulate("createDir", $dirname, [
			'config' => $config
		]);
	}
	
	/**
	 * Set the visibility for a file.
	 *
	 * @param string $path
	 * @param string $visibility
	 *
	 * @return array|false file meta data
	 */
	public function setVisibility( $path, $visibility ) {
		return $this->_manipulate("setVisibility", $path, [
			'visibility' => $visibility
		]);
	}
	
	/**
	 * Check whether a file exists.
	 *
	 * @param string $path
	 *
	 * @return array|bool|null
	 * @throws Exceptions\AuthException
	 */
	public function has( $path ) {
		return $this->_readInfo('has', $path);
	}
	
	/**
	 * Read a file.
	 *
	 * @param string $path
	 *
	 * @return array|false
	 */
	public function read( $path ) {
		$contents = $this->_read($path);
		return [
			"contents" => $contents,
			"path" => $path
		];
	}
	
	/**
	 * Read a file as a stream.
	 *
	 * @param string $path
	 *
	 * @return array|false
	 */
	public function readStream( $path ) {
		$tmp = tmpfile();
//		echo $tmp;
		$this->_read($path, $tmp);
		return [
			"stream" => $tmp,
			"path" => $path
		];
	}
	
	/**
	 * List contents of a directory.
	 *
	 * @param string $directory
	 * @param bool $recursive
	 *
	 * @return array
	 * @throws Exceptions\AuthException
	 */
	public function listContents( $directory = '', $recursive = false ) {
		return $this->_readInfo('listContents', $directory, ['recursive' => $recursive]);
	}
	
	/**
	 * Get all the meta data of a file or directory.
	 *
	 * @param string $path
	 *
	 * @return array|false
	 * @throws Exceptions\AuthException
	 */
	public function getMetadata( $path ) {
		return $this->_readInfo('getMetadata', $path);
	}
	
	/**
	 * Get the size of a file.
	 *
	 * @param string $path
	 *
	 * @return array|false
	 * @throws Exceptions\AuthException
	 */
	public function getSize( $path ) {
		return $this->_readInfo('getSize', $path);
	}
	
	/**
	 * Get the mimetype of a file.
	 *
	 * @param string $path
	 *
	 * @return array|false
	 * @throws Exceptions\AuthException
	 */
	public function getMimetype( $path ) {
		return $this->_readInfo('getMimetype', $path);
	}
	
	/**
	 * Get the timestamp of a file.
	 *
	 * @param string $path
	 *
	 * @return array|false
	 * @throws Exceptions\AuthException
	 */
	public function getTimestamp( $path ) {
		return $this->_readInfo('getTimestamp', $path);
	}
	
	/**
	 * Get the visibility of a file.
	 *
	 * @param string $path
	 *
	 * @return array|false
	 * @throws Exceptions\AuthException
	 */
	public function getVisibility( $path ) {
		return $this->_readInfo('getVisibility', $path);
	}
	
	/**
	 * @param Store $cache_store
	 */
	public function setCacheStore( Store $cache_store ) {
		$this->cache_store = $cache_store;
	}
}