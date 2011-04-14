# Sleepy - A Restful Modeler

	<?php

	class Model_User extends Sleepy {

		protected $_data_url = 'http://restfulserver.com/user/';
	
		protected $_data = array(
			'id' => '',
			'username' => '',
			'email' => '',
		);
	}
	
	$user = Sleepy::factory('user');
	$user->load(); // Runs a GET method on the $_data_url and updates the data.
	
	$user->username = 'iammerrick'; // Change the user name.
	
	$user->save(); // Posts the updated $_data via to the $_data_url
	
	Sleep::factory('user')
		->set_fields($_POST, array('username', 'email'))
		->save(); // Posts the $_data via JSON to the $_data_url and sets $_data with the JSON response
	
	

	
	
	?>