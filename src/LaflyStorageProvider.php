<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-08-27
 * Time: 16:46
 */

namespace Thikdev\LaFly;


use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class LaflyStorageProvider extends ServiceProvider {
	
	public function boot() {
		\Storage::extend('lafly', function ($app, $config) {
			$adapter = new LaFlyAdapter( $config );
			$adapter->setCacheRepository( $app->make('cache.store') );
			return new Filesystem($adapter);
		});
	}
	
}
