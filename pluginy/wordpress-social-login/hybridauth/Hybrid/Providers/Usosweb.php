<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
* Hybrid_Providers_Usosweb provider adapter based on OAuth1 protocol
* Adapter to Usosweb API by Henryk Michalewski
*/

function addGroup($user_id, $name) {
	global $wpdb;
	$wpdb -> query($wpdb -> prepare("INSERT INTO Grupa (nazwa) VALUES(%s);", $name));
	$group = $wpdb -> get_row($wpdb -> prepare("SELECT id FROM Grupa WHERE nazwa = %s;", $name));
	$wpdb -> query($wpdb -> prepare("INSERT INTO GrupaUzytkownika (idUzytkownika, idGrupy) VALUES(%d, %s);", $user_id, $group -> id));
}
	
class Hybrid_Providers_Usosweb extends Hybrid_Provider_Model_OAuth1
{
	/**
	* IDp wrappers initializer 
	*/
	/* Required scopes. The only functionality of this application is to say hello,
    * so it does not really require any. But, if you want, you may access user's
    * email, just do the following:
    * - put array('email') here,
    * - append 'email' to the 'fields' argument of 'services/users/user' method,
    *   you will find it below in this script.
    */
	
	function initialize()
	{
		parent::initialize();
		
        $scopes = array("studies", "staff_perspective");

		// Provider api end-points 
		$this->api->api_base_url      = "https://usosapps.uw.edu.pl/";
		$this->api->request_token_url = "https://usosapps.uw.edu.pl/services/oauth/request_token?scopes=".implode("|", $scopes);
		$this->api->access_token_url  = "https://usosapps.uw.edu.pl/services/oauth/access_token";
		$this->api->authorize_url = "https://usosapps.uw.edu.pl/services/oauth/authorize";
	}
	
	
    /**
	* begin login step 
	*/
	function loginBegin()
	{
		$tokens = $this->api->requestToken( $this->endpoint ); 

		// request tokens as received from provider
		$this->request_tokens_raw = $tokens;
		
		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 5 );
		}

		if ( ! isset( $tokens["oauth_token"] ) ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid oauth_token.", 5 );
		}

		$this->token( "request_token"       , $tokens["oauth_token"] ); 
		$this->token( "request_token_secret", $tokens["oauth_token_secret"] ); 

		# redirect the user to the provider authentication url
		Hybrid_Auth::redirect( $this->api->authorizeUrl( $tokens ) );
	}
	
	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		global $wpdb;
		$response = $this->api->get( 'https://usosapps.uw.edu.pl/services/users/user?fields=id|first_name|last_name|sex|employment_functions[faculty]|student_number|student_programmes[programme[id|name|faculty[id|name]]]|homepage_url|profile_url|student_status|staff_status' );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		if ( ! is_object( $response ) || ! isset( $response->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}

		# store the user profile. 
		# written without a deeper study what is really going on in Usosweb API
		 
		$this->user->profile->identifier  = (property_exists($response,'id'))?$response->id:"";
		$this->user->profile->displayName = (property_exists($response,'first_name') && property_exists($response,'last_name'))?$response->first_name." ".$response->last_name:"";
		$this->user->profile->lastName   = (property_exists($response,'last_name'))?$response->last_name:""; 
		$this->user->profile->firstName   = (property_exists($response,'first_name'))?$response->first_name:""; 
        $this->user->profile->gender = (property_exists($response,'sex'))?$response->sex:""; 
		$this->user->profile->profileURL  = (property_exists($response,'profile_url'))?$response->profile_url:"";
		$this->user->profile->webSiteURL  = (property_exists($response,'homepage_url'))?$response->homepage_url:""; 
		$this->user->profile->studentStatus  = (property_exists($response,'student_status'))?$response->student_status:"";
		$this->user->profile->staffStatus  = (property_exists($response,'staff_status'))?$response->staff_status:"";
		$description = 0;
		if ($this->user->profile->staffStatus == 2)
			$description = 2;
		if ($this->user->profile->studentStatus == 2)
			$description = 1;
		$this->user->profile->description = $description;
		
		$sql = "CREATE TABLE Grupa (
			id INT AUTO_INCREMENT,
			nazwa VARCHAR(200),
			PRIMARY KEY (id),
			UNIQUE(nazwa)
		);";
		dbDelta($sql);
		
		$sql = "CREATE TABLE GrupaUzytkownika (
			id INT AUTO_INCREMENT,
			idUzytkownika INT NOT NULL,
			idGrupy INT NOT NULL,
			PRIMARY KEY (id),
			UNIQUE(idUzytkownika, idGrupy),
			FOREIGN KEY (idGrupy) REFERENCES Grupa(id)
		);";
		dbDelta($sql);
		
		$wpdb -> query($wpdb -> prepare("DELETE FROM GrupaUzytkownika WHERE idUzytkownika = %d", $response->id));
		if ($this->user->profile->studentStatus == 2) {
			$student_programmes = $response->student_programmes;
			foreach ($student_programmes as $student_programme) {
				$faculty_name = $student_programme -> programme -> faculty -> name -> pl;
				addGroup($response -> id, $faculty_name);
				$programme_name = $student_programme -> programme -> name -> pl;
				addGroup($response -> id, $programme_name);
			}
		}
		
		if ($this->user->profile->staffStatus == 2) {
			$employment_functions = $reponse -> employment_functions;
			foreach ($employment_functions as $function) {
				$faculty_name = $function -> faculty -> name -> pl;
				addGroup($response -> id, $faculty_name);
			}
		}
		return $this->user->profile;
 	}
}
