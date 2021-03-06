<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 8/16/2016
 *
 */
require_once ROOT_DIR . '/Drivers/HorizonAPI.php';
abstract class HorizonAPI3_23 extends HorizonAPI
{
	private function getBaseWebServiceUrl() {
		global $configArray;
		if (!empty($this->accountProfile->patronApiUrl)) {
			$webServiceURL = $this->accountProfile->patronApiUrl;
		} elseif (!empty($configArray['Catalog']['webServiceUrl'])) {
			$webServiceURL = $configArray['Catalog']['webServiceUrl'];
		} else {
			global $logger;
			$logger->log('No Web Service URL defined in Horizon API Driver', PEAR_LOG_CRIT);
			return null;
		}

		$urlParts = parse_url($webServiceURL);
		$baseWebServiceUrl = $urlParts['scheme']. '://'. $urlParts['host']. (!empty($urlParts['port']) ? ':'. $urlParts['port'] : '');

		return $baseWebServiceUrl;
	}

	function updatePin($user, $oldPin, $newPin, $confirmNewPin){
		global $configArray;

		//Log the user in
		list($userValid, $sessionToken) = $this->loginViaWebService($user->cat_username, $user->cat_password);
		if (!$userValid){
			return 'Sorry, it does not look like you are logged in currently.  Please login and try again';
		}

		$updatePinUrl = $this->getBaseWebServiceUrl() . '/hzws/v1/user/patron/changeMyPin';
		$jsonParameters = array(
			'currentPin' => $oldPin,
			'newPin' => $newPin,
		);
		$updatePinResponse = $this->getWebServiceResponseUpdated($updatePinUrl, $jsonParameters, $sessionToken);
		if (isset($updatePinResponse['messageList'])) {
			$errors = '';
			foreach ($updatePinResponse['messageList'] as $errorMessage) {
				$errors .= $errorMessage['message'] . ';';
			}
			global $logger;
			$logger->log('WCPL Driver error updating user\'s Pin :'.$errors, PEAR_LOG_ERR);
			return 'Sorry, we encountered an error while attempting to update your pin. Please contact your local library.';
		} elseif ($updatePinResponse['sessionToken'] == $sessionToken){
			// Success response isn't particularly clear, but returning the session Token seems to indicate the pin updated. plb 8-15-2016
			$user->cat_password = $newPin;
			$user->update();
			return "Your pin number was updated successfully.";
		}else{
			return "Sorry, we could not update your pin number. Please try again later.";
		}
	}


	function resetPin($user, $newPin, $resetToken=null){
		if (empty($resetToken)) {
			global $logger;
			$logger->log('No Reset Token passed to resetPin function', PEAR_LOG_ERR);
			return array(
				'error' => 'Sorry, we could not update your pin. The reset token is missing. Please try again later'
			);
		}

		$changeMyPinAPIUrl = $this->getBaseWebServiceUrl() . '/hzws/v1/user/patron/changeMyPin';
		$jsonParameters = array(
			'resetPinToken' => $resetToken,
			'newPin' => $newPin,
		);
		$changeMyPinResponse = $this->getWebServiceResponseUpdated($changeMyPinAPIUrl, $jsonParameters);
		if (isset($changeMyPinResponse['messageList'])) {
			$errors = '';
			foreach ($changeMyPinResponse['messageList'] as $errorMessage) {
				$errors .= $errorMessage['message'] . ';';
			}
			global $logger;
			$logger->log('WCPL Driver error updating user\'s Pin :'.$errors, PEAR_LOG_ERR);
			return array(
				'error' => 'Sorry, we encountered an error while attempting to update your pin. Please contact your local library.'
			);
		} elseif (!empty($changeMyPinResponse['sessionToken'])){
			if ($user->username == $changeMyPinResponse['patronKey']) { // Check that the ILS user matches the Pika user
				$user->cat_password = $newPin;
				$user->update();
			}
			return array(
				'success' => true,
			);
//			return "Your pin number was updated successfully.";
		}else{
			return array(
				'error' => "Sorry, we could not update your pin number. Please try again later."
			);
		}
	}



	// Newer Horizon API version
	public function emailResetPin($barcode)
	{
		if (empty($barcode)) {
			$barcode = $_REQUEST['barcode'];
		}

		$patron = new User;
		$patron->get('cat_username', $barcode);
		if (!empty($patron->id)) {
			global $configArray;
			$userID = $patron->id;

			//email the pin to the user
			$resetPinAPIUrl = $this->getBaseWebServiceUrl() . '/hzws/v1/user/patron/resetMyPin';
			$jsonPOST       = array(
				'login'       => $barcode,
				'resetPinUrl' => $configArray['Site']['url'] . '/MyAccount/ResetPin?resetToken=<RESET_PIN_TOKEN>&uid=' . $userID
			);

			$resetPinResponse = $this->getWebServiceResponseUpdated($resetPinAPIUrl, $jsonPOST);
			// Reset Pin Response is empty JSON on success.

			if ($resetPinResponse === array() && !isset($resetPinResponse['messageList'])) {
				return array(
					'success' => true,
				);
			} else {
				$result = array(
					'error' => "Sorry, we could not e-mail your pin to you.  Please visit the library to reset your pin."
				);
				if (isset($resetPinResponse['messageList'])) {
					$errors = '';
					foreach ($resetPinResponse['messageList'] as $errorMessage) {
						$errors .= $errorMessage['message'] . ';';
					}
					global $logger;
					$logger->log('WCPL Driver error updating user\'s Pin :' . $errors, PEAR_LOG_ERR);
				}
				return $result;
			}
		} else {
			return array(
				'error' => 'Sorry, we did not find the card number you entered.'
			);
		}
	}


	/**
	 *  Handles API calls to the newer Horizon APIs.
	 *
	 * @param $url
	 * @param array $post  POST variables get encoded as JSON
	 * @return bool|mixed|SimpleXMLElement
	 */
	public function getWebServiceResponseUpdated($url, $post = array(), $sessionToken = ''){
		global $configArray;
		$requestHeaders = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'SD-Originating-App-Id: Pika',
			'x-sirs-clientId: ' . $configArray['Catalog']['clientId'],
		);

		if (!empty($sessionToken)) {
			$requestHeaders[] = "x-sirs-sessionToken: $sessionToken";
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		if (!empty($post)) {
			$post = json_encode($post);  // Turn Post Fields into JSON Data
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//		curl_setopt($ch, CURLINFO_HEADER_OUT, true); // enables request headers for curl_getinfo()
		$curlResponse = curl_exec($ch);

//		$info = curl_getinfo($ch);  // for debugging curl calls

		curl_close($ch);

		if ($curlResponse !== false && $curlResponse !== 'false'){
			$response = json_decode($curlResponse, true);
			if (json_last_error() == JSON_ERROR_NONE) {
				return $response;
			} else {
				global $logger;
				$logger->log('Error Parsing JSON response in WCPL Driver: ' . json_last_error_msg(), PEAR_LOG_ERR);
				return false;
			}


		}else{
			global $logger;
			$logger->log('Curl problem in getWebServiceResponseUpdated', PEAR_LOG_WARNING);
			return false;
		}
	}

}