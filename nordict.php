<?php
/**
* NT API WRAPPER
* Created By: Dennis B�kgaard @ Tquila Nordic-T.me
* Date: 30/08 - 2013
* Class: NordicT 
* Description: Core of the NT API Wrapper, dependency classes: user
* 
* Note: All methods return FALSE in case of miss' on the database in the API. For example, if you try to get a specific post with $user->getPostById(int) and the post is 
* gone or otherwise no retrieveable, then the result is false. It may be changed at a later point to return a custom class which also contains the error message.
*/

require_once('user.php');
class NordicT
{
	
	/**
	* FIELDS
	*/

	//SETUP CHANGE THESE FIELDS
	private $application_id = 'secret';
	private $app_key 	= 'secret';




/*****************************************************\
|       DO NOT CHANGE ANYTHING BELOW THIS LINE        |
|      UNLESS YOU HAVE A CLUE WHAT YOU'RE DOING       |
******************************************************/

	
	//CURL specifics
	protected $api_url = 'https://api.nordic-t.me/api/public/';
	
	//Nordic-T API specifics
	private $user;
	
	
	
	/**
	* Constructor
	* @param username : the Nordic-T username
	* @param token : the token can be present if it has already been aquired.	
	*/
	public function __construct($username, $token = null)
	{
		$this->user = new user($username, $token);
	}
	
	protected function runCurl($options)
	{
		$connection = curl_init();
		curl_setopt_array($connection, $options);
		
		//DEBUG ONLY
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);
		
		$result = curl_exec($connection);
		curl_close($connection);
		
		return $result;
	
	}
	
	/**
	 * PROPERTIES
	 */
	
	public function getUser()
	{
		return $this->user;
	}
	
	
	/**
	 * aquire_token
	 * call the API and returns user data	 
	 */
	public function aquireToken()
	{
		//signed key used in the API
		$signed = sha1($this->app_key . $this->application_id . $this->user->getUserName());
		
		//array to create the query to the API
		$data = array('username' => $this->user->getUsername(), 'appId' => $this->application_id, 'signed' => $signed);
		
		if($this->user->getToken() != null)
			$data['token'] = $this->user->getToken();
	
		//Make curl ready to run the request
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_BINARYTRANSFER => true,
			CURLOPT_FAILONERROR => false,
			CURLOPT_URL =>  $this->api_url . 'authorize?' . http_build_query($data)
		);
		
		$request = $this->runCurl($options);
		
		//if the curl request was succesfull, then we have received data as jSon, and it is decoded, if not return false.
		if($request)
		{
			$data = json_decode($request, true);
			
			if($data['result'] == true)
			{
				$this->user->setToken($data['data']['token']);
				$this->user->setTokenExpiration($data['data']['expires']);
				$this->user->setTokenStatus($data['data']['status']);
				$this->user->setUserId($data['data']['userId']);
				
			}
			
			return $data;
		}
		else
			return false;
	}
	
	/**
	 * Very often a user entity is sent as part of a data return (post, pm, etc etc) 
	 * I am not sure if the user is always worth saving, however, the simple thing is just to do it so it's available if it's needed.
	 * @abstract Helper function. This function will instantiate a user from the user-information-array. Simply saves time and make use of code-reuseability (WE LIKE THAT!)
	 * @param user_data : the entire user data information array.	 
	 */
	protected function createUserFromJSON($user_data)
	{
		
		return new User($user_data->username, null, $user_data->userId, null, null, $user_data->enabled, $user_data->joindate, $user_data->isAdultAvatar, $user_data->warned, $user_data->class , $user_data->avatar, (array)$user_data->country, $user_data->showNswfAvatar, $user_data->title, (array) $user_data->friends, $user_data->donor, $user_data->likes, $user_data->age, $user_data->gender);		
		
	}
	
}
?>