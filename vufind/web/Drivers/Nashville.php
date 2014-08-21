<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once ROOT_DIR . '/Drivers/Millennium.php';

/**
 * VuFind Connector for Marmot's Innovative catalog (millenium)
 *
 * This class uses screen scraping techniques to gather record holdings written
 * by Adam Bryn of the Tri-College consortium.
 *
 * @author Adam Brin <abrin@brynmawr.com>
 *
 * Extended by Mark Noble and CJ O'Hara based on specific requirements for
 * Marmot Library Network.
 *
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @author CJ O'Hara <cj@marmot.org>
 */
class Nashville extends MillenniumDriver{
	public function __construct(){
		$this->fixShortBarcodes = false;
	}
	/**
	 * Login with barcode and pin
	 *
	 * @see Drivers/Millennium::patronLogin()
	 */
	public function patronLogin($barcode, $pin)
	{
		global $configArray;
		global $timer;
		//global $logger;

		if ($configArray['Catalog']['offline'] == true){
			//$logger->log("Trying to authenticate in offline mode $barcode, $pin", PEAR_LOG_DEBUG);
			//The catalog is offline, check the database to see if the user is valid
			$user = new User();
			$user->cat_username = $barcode;
			$user->cat_password = $pin;
			if ($user->find(true)){
				//$logger->log("Found the user", PEAR_LOG_DEBUG);

				$returnVal = array(
					'id'        => $user->id,
					'username'  => $user->username,
					'firstname' => $user->firstname,
					'lastname'  => $user->lastname,
					'fullname'  => $user->firstname . ' ' . $user->lastname,     //Added to array for possible display later.
					'cat_username' => $barcode, //Should this be $Fullname or $patronDump['PATRN_NAME']
					'cat_password' => $pin,

					'email' => $user->email,
					'major' => null,
					'college' => null,
					'patronType' => $user->patronType,
					'web_note' => translate('The catalog is currently down.  You will have limited access to circulation information.'));
				$timer->logTime("patron logged in successfully");
				return $returnVal;

			} else {
				//$logger->log("Did not find a user for that barcode and pin", PEAR_LOG_DEBUG);
				$timer->logTime("patron login failed");
				return null;
			}
		}else{
			if (isset($_REQUEST['password2']) && strlen($_REQUEST['password2']) > 0){
				//User is setting a pin for the first time.  Need to do an actual login rather than just checking patron dump
				$header=array();
				$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
				$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
				$header[] = "Cache-Control: max-age=0";
				$header[] = "Connection: keep-alive";
				$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
				$header[] = "Accept-Language: en-us,en;q=0.5";
				$cookie = tempnam ("/tmp", "CURLCOOKIE");

				$curl_connection = curl_init();
				curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
				curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
				curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
				curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
				curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
				curl_setopt($curl_connection, CURLOPT_HEADER, false);

				//Go to the login page
				$curl_url = $configArray['Catalog']['url'] . "/patroninfo";
				curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
				curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
				$sresult = curl_exec($curl_connection);

				$curl_url = $configArray['Catalog']['url'] . "/patroninfo";
				curl_setopt($curl_connection, CURLOPT_URL, $curl_url);

				//First post without the pin number
				$post_data = array();
				$post_data['submit.x']="35";
				$post_data['submit.y']="21";
				$post_data['code']= $barcode;
				$post_data['pin']= "";
				curl_setopt($curl_connection, CURLOPT_POST, true);
				curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
				foreach ($post_data as $key => $value) {
					$post_items[] = $key . '=' . $value;
				}
				$post_string = implode ('&', $post_items);
				curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
				$sresult = curl_exec($curl_connection);
				if (!preg_match('/Please enter your PIN/i', $sresult)){
					PEAR_Singleton::raiseError('Unable to register your new pin #.  Did not get to registration page.');
				}

				//Now post with both pins
				$post_data = array();
				$post_items = array();
				$post_data['code']= $barcode;
				$post_data['pin1']= $pin;
				$post_data['pin2']= $_REQUEST['password2'];
				$post_data['submit.x']="35";
				$post_data['submit.y']="15";
				curl_setopt($curl_connection, CURLOPT_POST, true);
				curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
				foreach ($post_data as $key => $value) {
					$post_items[] = $key . '=' . $value;
				}
				$post_string = implode ('&', $post_items);
				curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
				set_time_limit(15);
				$sresult = curl_exec($curl_connection);
				$post_data = array();

				unlink($cookie);
				if (preg_match('/the information you submitted was invalid/i', $sresult)){
					PEAR_Singleton::raiseError('Unable to register your new pin #.  The pin was invalid or this account already has a pin set for it.');
				}else if (preg_match('/PIN insertion failed/i', $sresult)){
					PEAR_Singleton::raiseError('Unable to register your new pin #.  PIN insertion failed.');
				}
			}

			//Load the raw information about the patron
			$patronDump = $this->_getPatronDump($barcode, true);

			//Check the pin number that was entered
			$pin = urlencode($pin);
			$patronDumpBarcode = $barcode;
			$host=$configArray['OPAC']['patron_host'];
			$apiurl = $host . "/PATRONAPI/$patronDumpBarcode/$pin/pintest";

			$curlConnection = curl_init($apiurl);

			curl_setopt($curlConnection, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curlConnection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($curlConnection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curlConnection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curlConnection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curlConnection, CURLOPT_UNRESTRICTED_AUTH, true);

			//Setup encoding to ignore SSL errors for self signed certs
			$api_contents = curl_exec($curlConnection);
			curl_close($curlConnection);

			$api_contents = trim(strip_tags($api_contents));

			$api_array_lines = explode("\n", $api_contents);
			foreach ($api_array_lines as $api_line) {
				$api_line_arr = explode("=", $api_line);
				$api_data[trim($api_line_arr[0])] = trim($api_line_arr[1]);
			}

			if (!isset($api_data['RETCOD'])){
				$userValid = false;
			}else if ($api_data['RETCOD'] == 0){
				$userValid = true;
			}else{
				$userValid = false;
			}

			$Fullname = $patronDump['PATRN_NAME']; // James Staub chose this simpler route over some $Fullname replace acrobatics in Millennium.php 20131205
			$nameParts = explode(',',$Fullname);
			$lastname = strtolower($nameParts[0]);
			$middlename = isset($nameParts[2]) ? strtolower($nameParts[2]) : ''; 
			$firstname = isset($nameParts[1]) ? strtolower($nameParts[1]) : $middlename;

			if ($userValid){
				$user = array(
					'id'		=> $barcode,
					'username'	=> $patronDump['RECORD_#'],
					'firstname'	=> $firstname,
					'lastname'	=> $lastname,
					'fullname'	=> $Fullname,	//Added to array for possible display later.
					'cat_username'	=> $barcode,	//Should this be $Fullname or $patronDump['PATRN_NAME']
		                	'cat_password'	=> $pin,
	                		'email'		=> isset($patronDump['EMAIL_ADDR']) ? $patronDump['EMAIL_ADDR'] : '',
	                		'major'		=> null,
	                		'college'	=> null,
					'patronType'	=> $patronDump['P_TYPE'],
					'web_note'	=> isset($patronDump['WEB_NOTE']) ? $patronDump['WEB_NOTE'] : '');
					$timer->logTime("patron logged in successfully");
					return $user;
			} else {
				$timer->logTime("patron login failed");
				return null;
			}
		}
	}

	public function _getLoginFormValues(){
		global $user;
		$loginData = array();
		$loginData['pin'] = $user->cat_password;
		$loginData['code'] = $user->cat_username;

		$loginData['submit'] = 'submit';
		return $loginData;
	}

	public function _getBarcode(){
		global $user;
		return $user->cat_username;
	}

	protected function _getHoldResult($holdResultPage){
		$hold_result = array();
		//Get rid of header and footer information and just get the main content
		$matches = array();

		if (preg_match('/success/', $holdResultPage)){
			//Hold was successful
			$hold_result['result'] = true;
			if (!isset($reason) || strlen($reason) == 0){
				$hold_result['message'] = 'Your hold was placed successfully';
			}else{
				$hold_result['message'] = $reason;
			}
		}else if (preg_match('/<font color="red" size="\+2">(.*?)<\/font>/is', $holdResultPage, $reason)){
			//Got an error message back.
			$hold_result['result'] = false;
			$hold_result['message'] = $reason[1];
		}else{
			//Didn't get a reason back.  This really shouldn't happen.
			$hold_result['result'] = false;
			$hold_result['message'] = 'Did not receive a response from the circulation system.  Please try again in a few minutes.';
		}

		return $hold_result;
	}

	function updatePin(){
		global $user;
		global $configArray;
		if (!$user){
			return "You must be logged in to update your pin number.";
		}
		if (isset($_REQUEST['pin'])){
			$pin = $_REQUEST['pin'];
		}else{
			return "Please enter your current pin number";
		}
		if ($user->cat_password != $pin){
			return "The current pin number is incorrect";
		}
		if (isset($_REQUEST['pin1'])){
			$pin1 = $_REQUEST['pin1'];
		}else{
			return "Please enter the new pin number";
		}
		if (isset($_REQUEST['pin2'])){
			$pin2 = $_REQUEST['pin2'];
		}else{
			return "Please enter the new pin number again";
		}
		if ($pin1 != $pin2){
			return "The pin numberdoes not match the confirmed number, please try again.";
		}

		//Login to the patron's account
		$cookieJar = tempnam ("/tmp", "CURLCOOKIE");
		$success = false;

		$barcode = $this->_getBarcode();
		$patronDump = $this->_getPatronDump($barcode);

		//Login to the site
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo";

		$curl_connection = curl_init($curl_url);
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, false);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = $this->_getLoginFormValues($patronDump);
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		//Issue a post request to update the pin
		$post_data = array();
		$post_data['pin']= $pin;
		$post_data['pin1']= $pin1;
		$post_data['pin2']= $pin2;
		$post_data['submit.x']="35";
		$post_data['submit.y']="15";
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo/" .$patronDump['RECORD_#'] . "/newpin";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		$sresult = curl_exec($curl_connection);

		curl_close($curl_connection);
		unlink($cookieJar);

		if ($sresult){
			if (preg_match('/<FONT COLOR=RED SIZE= 2><EM>(.*?)</EM></FONT>/i', $sresult, $matches)){
				return $matches[1];
			}else{
				$user->cat_password = $pin1;
				$user->update();
				UserAccount::updateSession($user);
				return "Your pin number was updated sucessfully.";
			}
		}else{
			return "Sorry, we could not update your pin number. Please try again later.";
		}
	}

	function selfRegister(){
		global $logger;
		global $configArray;

		$firstName = $_REQUEST['firstName'];
		$middleInitial = $_REQUEST['middleInitial'];
		$lastName = $_REQUEST['lastName'];
		$address1 = $_REQUEST['address1'];
		$address2 = $_REQUEST['address2'];
		$address3 = $_REQUEST['address3'];
		$address4 = $_REQUEST['address4'];
		$email = $_REQUEST['email'];
		$gender = $_REQUEST['gender'];
		$birthDate = $_REQUEST['birthDate'];
		$phone = $_REQUEST['phone'];

		$cookie = tempnam ("/tmp", "CURLCOOKIE");
		$curl_url = $configArray['Catalog']['url'] . "/selfreg~S" . $this->getMillenniumScope();
		$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);
		//echo "$curl_url";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);

		$post_data['nfirst'] = $firstName;
		$post_data['nmiddle'] = $middleInitial;
		$post_data['nlast'] = $lastName;
		$post_data['stre_aaddress'] = $address1;
		$post_data['city_aaddress'] = $address2;
		$post_data['stre_haddress2'] = $address3;
		$post_data['city_haddress2'] = $address4;
		$post_data['zemailaddr'] = $email;
		$post_data['F045pcode2'] = $gender;
		$post_data['F051birthdate'] = $birthDate;
		$post_data['tphone1'] = $phone;
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		curl_close($curl_connection);

		//Parse the library card number from the response
		if (preg_match('/Your temporary library card number is :.*?(\\d+)<\/(b|strong|span)>/si', $sresult, $matches)) {
			$barcode = $matches[1];
			return array('success' => true, 'barcode' => $barcode);
		} else {
			global $logger;
			$logger->log("$sresult", PEAR_LOG_DEBUG);
			return array('success' => false, 'barcode' => null);
		}

	}
}
