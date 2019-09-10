<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 3/20/17
 * Time: 13:23
 */

namespace Thikdev\LaFly;


use League\Flysystem\Exception;
use Thikdev\Colombo\DiskManager;
use Thikdev\LaFly\Exceptions\AuthException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use League\Flysystem\Config;
use Psr\Http\Message\ResponseInterface;

trait AuthTrait {
	
	protected $token;
	protected $config = [
		"username" => '',
		"password" => '',
		"host" => '',
		"connection_name" => "",
		"log_level" => "error",
		"cache_token_name" => "xxx_",
		"root" => "",
		"cdn" => "",
		"url" => "",
	];
	
	protected $try_login = 0;
	protected $max_try_login = 3;
	
	
	protected function start_login(){
		if($this->try_login > $this->max_try_login){
			throw new AuthException("Try login more than "
			                                   . $this->max_try_login
			                                   . " times with credential "
			                                   . $this->config['username']
			                                   . "|****");
		}
	}
	protected function reset_login(){
		$this->try_login = 0;
	}
	protected function after_login($bool = true){
		$this->try_login = $bool ? 0 : $this->try_login + 1;
		return $bool;
	}
	
	protected function login(){
		start_login:
		$this->start_login();
		try{
			$client = $this->initClient(false);
			$response = $client->request("post", $this->urlAuth(), [
				'form_params' => [
					'email' => $this->config['username'],
					'password' => $this->config['password'],
				]
			]);
			$data = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
			if(isset($data['token'])){
				$this->token = $data['token'];
				$this->cache_repository->put($this->config['cache_token_name'], $data['token'], 300);
			}
			return $this->after_login(true);
		}catch (RequestException $ex){
			$this->after_login(false);// mark as login fail
			if(!$ex->hasResponse()){
				throw new AuthException("No response");
			}
			$response = $ex->getResponse();
			if($response->getStatusCode() == 401){// sai thong tin dang nhap thi fail luôn
				throw new AuthException("Wrong auth info");
			}else{                                // lỗi khác thì thử lại
				goto start_login;
			}
		}catch (\Exception $ex){
			$this->after_login(false);
		}
		return false;
		
	}
	
	protected function request($method, $url, $options = [], $auth = false){
		$reconnected = false;
		start_request:
		try{
			$client = $this->initClient($auth);
			$response = $client->request($method, $url, $options);
			return [
				"success" => true,
				"response" => $response
			];
		}catch (RequestException $ex){
			if(!$ex->hasResponse()){
				if(!$reconnected){
					$reconnected = true;
					usleep( 1000);
					goto start_request;
				}
				return [
					"success" => false,
					"response" => null
				];
			}
			$response = $ex->getResponse();
			if($response->getStatusCode() == 400) {// nếu lỗi chưa đăng nhập thì đăng nhập lại rồi tiếp tục
				if ( $this->login() ) {
					usleep( 1000);
					goto start_request;
				}
			}
//			dd($response->getBody()->getContents());
			return [
				"success" => false,
				"response" => $response
			];
		}
	}
	
	/**
	 * Khởi tạo client kèm chuỗi xác thực
	 *
	 * @param bool $authorized
	 *
	 * @return Client
	 * @throws AuthException
	 */
	protected function initClient($authorized = false){
		$this->token = $this->cache_repository->get($this->config['cache_token_name']);
		if($authorized && empty($this->token)){
			$this->login();
		}
		$headers = $authorized ? ["authorization" => "bearer " . $this->token] : [];
		$client = new Client([
			"headers" => $headers
		]);
		return $client;
	}
	
	/**
	 * @param ResponseInterface $response
	 *
	 * @return mixed
	 */
	protected function getJson(ResponseInterface $response = null){
		if(!$response){
			return [];
		}
		return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
	}
	
	/**
	 * @param $input
	 * @param $path
	 * @param string $input_type
	 * @param string $mode new|update|empty
	 *
	 * @param Config $config
	 *
	 * @return bool|mixed
	 */
	protected function _write($action, $path, $input, Config $config){
		
		$file_stream = \GuzzleHttp\Psr7\stream_for($input);
		$file_name = basename($path);
		$config = ConfigExtractor::process($config);
		
		$multipart = [
			[
				"name" => "file",
				"contents" => $file_stream,
				"filename" => $file_name
			],
			[
				"name" => "action",
				"contents" => $action,
			],
			[
				"name" => "root",
				"contents" => $this->config["root"],
			],
			[
				"name" => "path",
				"contents" => $path
			],
			[
				"name" => "connection",
				"contents" => $this->config['connection_name'],
			]
		];
		
		if(!empty($config)){
			$multipart[] = [
				"name" => "config",
				"contents" => $config
			];
		}
		
		$result = $this->request('post', $this->urlWrite(), [
			"multipart" => $multipart,
		], true);
		if($result["success"]){
			$data = $this->getJson($result["response"]);
			return Arr::get($data, 'data');
		}else{
			if(isset($result['response'])){
				throw new Exception($result['response']->getBody()->getContents());
			}else{
				throw new Exception("Request error");
			}
		}
	}
	
	public function _readInfo($what, $path, $options = []){
		
		$multipart = [
			[
				"name" => "what",
				"contents" => $what
			],
			[
				"name" => "root",
				"contents" => $this->config["root"],
			],
			[
				"name" => "path",
				"contents" => $path,
			],
			[
				"name" => "connection",
				"contents" => $this->config['connection_name'],
			]
		];
		
		foreach ($options as $k => $v){
			$multipart[] = [
				"name" => $k,
				"contents" => $v
			];
		}
		$result = $this->request('post', $this->urlReadInfo(), [
			"multipart" => $multipart,
		], true);
		$data = $this->getJson($result["response"]);
		if($data["success"]){
			return Arr::get($data, 'data');
		}else{
			throw new AuthException($data['message']);
		}
	}
	
	public function _manipulate($what, $path, $options = []){
		
		$multipart = [
			[
				"name" => "what",
				"contents" => $what
			],
			[
				"name" => "root",
				"contents" => $this->config["root"],
			],
			[
				"name" => "path",
				"contents" => $path,
			],
			[
				"name" => "connection",
				"contents" => $this->config['connection_name'],
			]
		];
		foreach ($options as $k => $v){
			$multipart[] = [
				"name" => $k,
				"contents" => $v
			];
		}
		$result = $this->request('post', $this->urlManipulate(), [
			"multipart" => $multipart,
		], true);
		$data = $this->getJson($result["response"]);
		if($result["success"]){
			return Arr::get($data, 'data');
		}else{
			\Log::error("Lafly Auth::" . print_r( $data, true));
			\Log::info("Write to flystorage error::" . print_r($result, true));
			return false;
		}
	}
	
	/**
	 * @param string $as stream|string
	 * @param $path
	 *
	 * @return bool|mixed
	 */
	public function _read($path, $tmp = null){
		
		$multipart = [
			[
				"name" => "as",
				"contents" => "string"
			],
			[
				"name" => "root",
				"contents" => $this->config["root"],
			],
			[
				"name" => "path",
				"contents" => $path,
			],
			[
				"name" => "connection",
				"contents" => $this->config['connection_name'],
			]
		];
		$options = [
			"multipart" => $multipart
		];
//		if($tmp){
//			$options['sink'] = $tmp;
//		}
		$result = $this->request('post', $this->urlRead(), $options, true);
		if($result["success"]){
			/** @var ResponseInterface $res */
			$res = $result['response'];
//			dd($res->getBody()->getContents());
			if(is_string($tmp)){
				$fh = fopen($tmp, 'w');
				while (!$res->getBody()->eof()){
					fwrite($fh, $res->getBody()->read(2048), 2048);
				}
				fclose($fh);
				return $tmp;
			}elseif(is_resource($tmp)){
				while (!$res->getBody()->eof()){
					fwrite($tmp, $res->getBody()->read(2048), 2048);
				}
				rewind($tmp);
				return $tmp;
			}else{
				return $res->getBody()->getContents();
			}
		}else{
			\Log::info("Read from fly error error::" . print_r($result, true));
			return false;
		}
	}
	
	private function urlAuth(){
		return rtrim($this->config['host'], '/') . "/api/login";
	}
	private function urlPing(){
		return rtrim($this->config['host'], '/') . "/api/ping";
	}
	private function urlWrite(){
		return rtrim($this->config['host'], '/') . "/api/lafly/write";
	}
	private function urlRead(){
		return rtrim($this->config['host'], '/') . "/api/lafly/read";
	}
	private function urlReadInfo(){
		return rtrim($this->config['host'], '/') . "/api/lafly/read_info";
	}
	private function urlManipulate(){
		return rtrim($this->config['host'], '/') . "/api/lafly/manipulate";
	}
	private function urlAutoSave(){
		return rtrim($this->config['host'], '/') . "/api/ext/auto_save";
	}
	
	protected function setConfig($config){
		foreach ($this->config as $k => $v){
			$this->config[$k] = Arr::get($config, $k, $v);
		}
	}
	
	protected function getConfig($key){
		return Arr::get($this->config, $key);
	}
	
	protected function hasConfig($key){
		return Arr::has($this->config, $key);
	}
}