<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Sleepy Restful Modeling
 *
 * @package Sleepy
 * @author Merrick Christensen
 */
class Sleepy_Core extends Model {
	
	protected $_url = '';
	protected $_data = array();
	
	const STATE_NEW = 'new';
	const STATE_LOADED = 'loaded';
	const STATE_UPDATED = 'updated';
	
	protected $_status = Sleepy_Core::STATE_NEW;
	
	
	/**
	 * Constructor, instantiate $_url from configuration.
	 *
	 * @author Merrick Christensen
	 */
	public function __construct()
	{
		$this->_url = Kohana::config('sleepy.url').$this->_url;
	}
	
	/**
	 * Loads either passed data or a string of JSON and sets to the objects $_data.
	 *
	 * @param string JSON $data 
	 * @return this
	 * @author Merrick Christensen
	 */
	public function load($data)
	{
		
		if(is_string($data))
		{
			$data = json_decode($data);
		}
		
		foreach($data as $key => $value)
		{
			if(array_key_exists($key, $this->_data))
			{
				$this->_data[$key] = $value;
			}
		}
		
		$this->_status = Sleepy_Core::STATE_LOADED;
		
		return $this;
	}
	
	/**
	 * Used to set data from client use, generally used to set values
	 * from form fields.
	 *
	 * @param array $data 
	 * @param array $keys 
	 * @return this
	 * @author Merrick Christensen
	 */
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
	
	/**
	 * Check if Sleepy status is loaded.
	 *
	 * @return void
	 * @author Merrick Christensen
	 */
	public function loaded()
	{
		return $this->_status === Sleepy_Core::STATE_LOADED;
	}

	/**
	 * Get a value by key from data.
	 *
	 * @param string $key 
	 * @return value
	 * @author Merrick Christensen
	 */
	public function __get($key)
	{
		if (array_key_exists($key, $this->_data))
		{
		 	return $this->_data[$key];
		}

		throw new Sleepy_Exception('Field '.$key.' does not exist in '.get_class($this).'!', array());
	}
	
	/**
	 * Set data by key value.
	 *
	 * @param string $key 
	 * @param string $value 
	 * @return void
	 * @author Merrick Christensen
	 */
	public function __set($key, $value)
	{
		if (array_key_exists($key, $this->_data))
		{
			$this->_data[$key] = $value;
			$this->_state = Sleepy_Core::STATE_UPDATED;
			return;
		}
		
	}
	
	/**
	 * isset any of the data properties.
	 *
	 * @param string $name 
	 * @return void
	 * @author Merrick Christensen
	 */
	public function __isset($name)
	{
		return isset($this->_data[$name]);
	}
	
	/**
	 * Method doesn't exist try it on the restful service.
	 *
	 * @param string $name 
	 * @param string $arguments 
	 * @return void
	 * @author Merrick Christensen
	 */
	public function __call($name, $arguments)
	{	
		return $this->call($name, $arguments);
	}
	
	public function call($name, $arguments)
	{
		$request = Request::factory($this->_url.$name);
		
		if( ! empty($arguments))
		{			
			$request->method('POST');
			$request->headers('content-type', 'application/json'); // A bit application specific I know.
		}
		
		if (is_array($arguments))
		{
			$request->body(json_encode($arguments)); // Again a bit specific
		}
		
		$response = $request->execute();
		
		if($response->status() === 200)
		{
			$response = json_decode($response->body());
		}
		else
		{
			throw new Sleepy_Exception('Request failed :response with :url !', array(':response' => $response->body(), ':url' => $this->_url.$name));
		}
		
		return $response;
	}
	
	/**
	 * Return data.
	 *
	 * @return void
	 * @author Merrick Christensen
	 */
	public function as_array()
	{
		return $this->_data;
	}
	
	public function as_json()
	{
		return json_encode($this->_data);
	}
	
} // End Sleepy_Core
