<?php defined('SYSPATH') or die('No direct script access.');

class Sleepy_Core extends Model {
	
	protected $_data_url = '';
	protected $_data = array();
	
	const STATE_NEW = 'new';
	const STATE_LOADED = 'loaded';
	const STATE_UPDATED = 'updated';
	
	protected $_status = Sleepy_Core::STATE_NEW;
	
	public function __construct()
	{
		$this->_data_url = Kohana::config('sleepy.url').$this->_data_url;
	}
	
	public function load($url = NULL)
	{
		$request = $this->get_request($url);
		$response = $request->execute();
		
		if($response->status() === 200)
		{
			return $this->load_data($response->body());
		}
		else
		{	
			throw new Kohana_Exception('Sleepy Request Failed, :response', array(':response', $response));
		}
	}
	
	protected function load_data($data)
	{
		$decoded = json_decode($data);
		
		foreach($decoded as $key => $value)
		{
			if(array_key_exists($key, $this->_data))
			{
				$this->_data[$key] = $value;
			}
		}
		
		$this->_status = Sleepy_Core::STATE_LOADED;
		
		return $this;
	}
	
	public function save($url = NULL)
	{
	    return $this->loaded() ? $this->update($url) : $this->create($url);
	}
	
	public function update($url = NULL)
	{
		$request = $this->get_request($this->_data_url.$url);
		$request->method('POST');
		$request->post($this->_data);
		$request->execute();
		
		$this->_status = Sleepy_Core::STATE_LOADED;
	}
	
	public function create($url = NULL)
	{
		$request = $this->get_request($this->_data_url.$url);
		$request->method('POST');
		
		$request->post($this->_data);
		$response = $request->execute();
		
		if($response->status() === 200)
		{
			return $this->load_data($response->body());
		}
		else
		{
			throw new Kohana_Exception('Sleepy Request Failed, :response', array(':response' => $response));
		}
	}
	
	public function set_fields(array $data, array $keys = NULL)
	{
		if(is_array($keys))
		{
			$data = Arr::extract($data, $keys);
		}

		foreach (array_intersect_key($data, $this->_data) as $key => $value)
		{
			$this->$key = $value;
		}
		
		return $this;
	}
	
	public function loaded()
	{
		return $this->_status === Sleepy_Core::STATE_LOADED;
	}
	
	private function get_request($url = NULL)
	{
		if($url === NULL)
		{
			$url = $this->_data_url;
		}
		
		return Request::factory($url);
	}
	
	public function __get($key)
	{
		if (array_key_exists($key, $this->_data))
		{
		 	return $this->_data[$key];
		}

		throw new Kohana_Exception('Field '.$key.' does not exist in '.get_class($this).'!', array(), '');
	}
	
	public function __set($key, $value)
	{
		if (array_key_exists($key, $this->_data))
		{
			$this->_data[$key] = $value;
			$this->_state = Sleepy_Core::STATE_UPDATED;
			return;
		}
		
	}
	
	public function __isset($name)
	{
		return isset($this->_data[$name]);
	}
	
	public function __call($name, $arguments)
	{	
		$request = $this->get_request($this->_data_url.$name);
		
		if ( ! empty($arguments))
		{
			$request->method('POST');
			$request->post($arguments);
		}
		
		$response = $request->execute();
		
		return $response;
	}
	
	public function as_array()
	{
		return $this->_data;
	}
	
} // End Sleepy_Core
