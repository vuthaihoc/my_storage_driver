<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 3/20/17
 * Time: 13:52
 */

namespace Thikdev\LaFly;


use League\Flysystem\Config;

class ConfigExtractor {
	
	protected static $keys = [
		"visibility"
	];
	
	public static function process(Config $config){
		$return = [];
		foreach ( self::$keys as $key ) {
			if($config->has($key)){
				$return[$key] = $config->get($key);
			}
		}
		return $return;
	}
}