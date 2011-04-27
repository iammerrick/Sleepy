<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Sleepy Restful Modeling
 *
 * @package Sleepy
 * @author Merrick Christensen
 */
class Sleepy_Core extends Model {
	
	private $_decoded_response;
	
	protected $_data_url = '';
	protected $_data = array();
	
	const STATE_NEW = 'new';
	const STATE_LOADED = 'loaded';
	const STATE_UPDATED = 'updated';
	
	protected $_status = Sleepy_Core::STATE_NEW;
	
	/**
	 * Constructor, if you don't pass a url it will fall back
	 * to the Sleep configuration. This is useful if you plan
	 * on working with more then one RESTful service.
	 *
	 * @param string $url 
	 * @author Merrick Christensen
	 */
	public function __construct($url = NULL)
	{
		if($url === NULL)
		{
			$this->_data_url = Kohana::config('sleepy.url').$this->_data_url;
		}
		else
		{
			$this->_data_url = $url.$this->_data_url;
		}
	}
	
	/**
	 * Take a request, execute it. If it is successful return the response.
	 * Otherwise throw an exception.
	 *
	 * @param string $request 
	 * @return object Request response
	 * @author Merrick Christensen
	 */
	public function execute($request)
	{
		$response = $request->execute();
		
		if($response->status() === 200)
		{
			$this->_decoded_response = json_decode($response);
			return $response;
		}
		else
		{
			throw new Kohana_Exception('Sleepy Request Failed, :response', array(':response', $response), '');
		}
	}
	
	/**
	 * Load in data from the rest service and parse it into $_data.
	 *
	 * @param string $url 
	 * @return void
	 * @author Merrick Christensen
	 */
	public function load($url = NULL)
	{
		$request = $this->get_request($url);
		$response = $this->execute($request);
		
		$this->load_data(); // No need to pass data pulls from instance variable
		
	}
	
	/**
	 * Loads either passed JSON or instance decoded JSON to the objects $_data.
	 *
	 * @param string JSON $data 
	 * @return this
	 * @author Merrick Christensen
	 */
	public function load_data($data = NULL)
	{
		
		if($data !== NULL)
		{
			$decoded = json_decode($data);
			$this->_decoded_response = $decoded;
		}
		else
		{
			$decoded = $this->_decoded_response;
		}
		
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
		
		$response = $this->execute($request);
		
		$this->load_data();
		
		$this->_status = Sleepy_Core::STATE_LOADED;
	}
	
	/**
	 * Generates request and posts the variables to the service.
	 * Used to create new items on the Restful service.
	 * Calls to set $_data to returned values.
	 *
	 * @param string $url 
	 * @return this
	 * @author Merrick Christensen
	 */
	public function create($url = NULL)
	{
		$request = $this->get_request($this->_data_url.$url);
		$request->method('POST');
		$request->post($this->_data);
		$response = $this->execute($request);
		
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
	 * Generate a request form the sleepy urls.
	 *
	 * @param string $url 
	 * @return Request
	 * @author Merrick Christensen
	 */
	private function get_request($url = NULL)
	{
		if($url === NULL)
		{
			$url = $this->_data_url;
		}
		
		return Request::factory($url);
	}
	
	/**
	 * Return the last parsed JSON response.
	 *
	 * @return void
	 * @author Merrick Christensen
	 */
	public function response()
	{
		return $this->_decoded_response;
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

		throw new Kohana_Exception('Field '.$key.' does not exist in '.get_class($this).'!', array(), '');
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
		$request = $this->get_request($this->_data_url.$name);
		
		if ( ! empty($arguments))
		{
			$request->method('POST');
			$request->post($arguments);
		}
		
		$response = $request->execute();
		
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
	
} // End Sleepy_Core
