<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 3/20/17
 * Time: 13:22
 */

namespace Thikdev\LaFly;


use GrahamCampbell\Manager\ConnectorInterface;
use Illuminate\Support\Arr;

class LaFlyConnector implements ConnectorInterface {
	
	/**
	 * Establish a connection.
	 *
	 * @param array $config
	 *
	 * @return LaFlyAdapter
	 */
	public function connect( array $config ) {
		$connection_name = Arr::get($config, 'connection', 'local');
		return new LaFlyAdapter($config, $connection_name);
	}
	
	
	
}