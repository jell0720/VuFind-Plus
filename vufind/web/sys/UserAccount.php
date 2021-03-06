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

require_once 'XML/Unserializer.php';
require_once 'XML/Serializer.php';

require_once ROOT_DIR . '/sys/Authentication/AuthenticationFactory.php';

class UserAccount {
	static $isLoggedIn = null;
	static $primaryUserData = null;
	/**
	 * Checks whether the user is logged in.
	 *
	 * When logged in we store information the id of the active user within the session.
	 * The actual user is stored within memcache
	 *
	 * @return bool|User
	 */
	public static function isLoggedIn() {
		if (UserAccount::$isLoggedIn != null){
			if (UserAccount::$isLoggedIn){
				return UserAccount::$primaryUserData;
			}else{
				return false;
			}
		}
		global $action;
		global $module;
		$userData = false;
		if (isset($_SESSION['activeUserId'])) {
			$activeUserId = $_SESSION['activeUserId'];
			/** @var Memcache $memCache */
			global $memCache;
			global $serverName;

			/** @var User $userData */
			$userData = $memCache->get("user_{$serverName}_{$activeUserId}");
			if ($userData === false || isset($_REQUEST['reload'])){
				//Load the user from the database
				$userData = new User();
				$userData->id = $activeUserId;
				if ($userData->find(true)){
					$userData = UserAccount::validateAccount($userData->cat_username, $userData->cat_password, $userData->source);
				}
			}else{
				$userData->updateRuntimeInformation();
				global $timer;
				$timer->logTime("Updated Runtime Information");
			}
			UserAccount::$isLoggedIn = true;

		//Check to see if the patron is already logged in within CAS as long as we aren't on a page that is likely to be a login page
		}elseif ($action != 'AJAX' && $action != 'Logout' && $module != 'MyAccount' && !isset($_REQUEST['username'])){
			//If the library uses CAS/SSO we may already be logged in even though they never logged in within Pika
			global $library;
			if (strlen($library->casHost) > 0){
				//Check CAS first
				require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
				$casAuthentication = new CASAuthentication(null);
				$casUsername = $casAuthentication->validateAccount(null, null, null, false);
				if ($casUsername == false || PEAR_Singleton::isError($casUsername)){
					//The user could not be authenticated in CAS
					UserAccount::$isLoggedIn = false;
					return false;
				}else{
					//We have a valid user via CAS, need to do a login to Pika
					$_REQUEST['casLogin'] = true;
					$userData = UserAccount::login();
					UserAccount::$isLoggedIn = true;
				}
			}
		}
		if (UserAccount::$isLoggedIn){
			UserAccount::$primaryUserData = $userData;
		}
		return $userData;
	}

	/**
	 * Updates the user information in the session and in memcache
	 *
	 * @param User $user
	 */
	public static function updateSession($user) {
		$_SESSION['activeUserId'] = $user->id;

		if (isset($_REQUEST['rememberMe']) && ($_REQUEST['rememberMe'] === "true" || $_REQUEST['rememberMe'] === "on")){
			$_SESSION['rememberMe'] = true;
		}else{
			$_SESSION['rememberMe'] = false;
		}

		// If the user browser has the showCovers settings stored, set the Session variable
		// Used for showing or hiding covers on MyAccount Pages
		if (isset($_REQUEST['showCovers'])) {
			$showCovers = ($_REQUEST['showCovers'] == 'on' || $_REQUEST['showCovers'] == 'true');
			$_SESSION['showCovers'] = $showCovers;
		}

		session_commit();
	}

	/**
	 * Try to log in the user using current query parameters
	 * return User object on success, PEAR error on failure.
	 *
	 * @return PEAR_Error|User
	 * @throws UnknownAuthenticationMethodException
	 */
	public static function login() {
		global $user;
		global $logger;

		$validUsers = array();

		$validatedViaSSO = false;
		if (isset($_REQUEST['casLogin'])){
			$logger->log("Logging the user in via CAS", PEAR_LOG_INFO);
			//Check CAS first
			require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
			$casAuthentication = new CASAuthentication(null);
			$casUsername = $casAuthentication->authenticate(false);
			if ($casUsername == false || PEAR_Singleton::isError($casUsername)){
				//The user could not be authenticated in CAS
				$logger->log("The user could not be logged in", PEAR_LOG_INFO);
				return new PEAR_Error('Could not authenticate in sign on service');
			}else{
				$logger->log("User logged in OK CAS Username $casUsername", PEAR_LOG_INFO);
				//Set both username and password since authentication methods could use either.
				//Each authentication method will need to deal with the possibility that it gets a barcode for both user and password
				$_REQUEST['username'] = $casUsername;
				$_REQUEST['password'] = $casUsername;
				$validatedViaSSO = true;
			}
		}

		/** @var User $primaryUser */
		$primaryUser = null;
		$lastError = null;
		$driversToTest = self::loadAccountProfiles();

		//Test each driver in turn.  We do test all of them in case an account is valid in
		//more than one system
		foreach ($driversToTest as $driverName => $driverData){
			// Perform authentication:
			$authN = AuthenticationFactory::initAuthentication($driverData['authenticationMethod'], $driverData);
			$tempUser = $authN->authenticate($validatedViaSSO);

			// If we authenticated, store the user in the session:
			if (!PEAR_Singleton::isError($tempUser)) {
				if ($validatedViaSSO){
					$tempUser->loggedInViaCAS = true;
				}
				global $library;
				if (isset($library) && $library->preventExpiredCardLogin && $tempUser->expired) {
					// Create error
					$cardExpired = new PEAR_Error('expired_library_card');
					return $cardExpired;
				}

				/** @var Memcache $memCache */
				global $memCache;
				global $serverName;
				global $configArray;
				$memCache->set("user_{$serverName}_{$tempUser->id}", $tempUser, 0, $configArray['Caching']['user']);

				$validUsers[] = $tempUser;
				if ($primaryUser == null){
					$primaryUser = $tempUser;
					self::updateSession($primaryUser);
				}else{
					//We have more than one account with these credentials, automatically link them
					$primaryUser->addLinkedUser($tempUser);
				}
			}else{
				global $logger;
				$username = isset($_REQUEST['username']) ? $_REQUEST['username'] : 'No username provided';
				$logger->log("Error authenticating patron $username for driver {$driverName}\r\n" . print_r($user, true), PEAR_LOG_ERR);
				$lastError = $tempUser;
				$logger->log($lastError->toString(), PEAR_LOG_ERR);
			}
		}

		// Send back the user object (which may be a PEAR error):
		if ($primaryUser){
			UserAccount::$isLoggedIn = true;
			UserAccount::$primaryUserData = $primaryUser;
			return $primaryUser;
		}else{
			return $lastError;
		}
	}

	/**
	 * Validate the account information (username and password are correct).
	 * Returns the account, but does not set the global user variable.
	 *
	 * @param $username       string
	 * @param $password       string
	 * @param $accountSource  string The source of the user account if known or null to test all sources
	 * @param $parentAccount  User   The parent user if any
	 *
	 * @return User|false
	 */
	public static function validateAccount($username, $password, $accountSource = null, $parentAccount = null){
		// Perform authentication:
		//Test all valid authentication methods and see which (if any) result in a valid login.
		$driversToTest = self::loadAccountProfiles();

		global $library;
		$validatedViaSSO = false;
		if (strlen($library->casHost) > 0 && $username == null && $password == null){
			//Check CAS first
			require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
			$casAuthentication = new CASAuthentication(null);
			$casUsername = $casAuthentication->validateAccount(null, null, $parentAccount, false);
			if ($casUsername == false || PEAR_Singleton::isError($casUsername)){
				//The user could not be authenticated in CAS
				return false;
			}else{
				//Set both username and password since authentication methods could use either.
				//Each authentication method will need to deal with the possibility that it gets a barcode for both user and password
				$username = $casUsername;
				$password = $casUsername;
				$validatedViaSSO = true;
			}
		}

		foreach ($driversToTest as $driverName => $additionalInfo){
			if ($accountSource == null || $accountSource == $additionalInfo['accountProfile']->name) {
				$authN = AuthenticationFactory::initAuthentication($additionalInfo['authenticationMethod'], $additionalInfo);
				$validatedUser = $authN->validateAccount($username, $password, $parentAccount, $validatedViaSSO);
				if ($validatedUser && !PEAR_Singleton::isError($validatedUser)) {
					/** @var Memcache $memCache */
					global $memCache;
					global $serverName;
					global $configArray;
					$memCache->set("user_{$serverName}_{$validatedUser->id}", $validatedUser, 0, $configArray['Caching']['user']);
					if ($validatedViaSSO){
						$validatedUser->loggedInViaCAS = true;
					}
					return $validatedUser;
				}
			}
		}

		return false;
	}

	/**
	 * Completely logout the user annihilating their entire session.
	 */
	public static function logout()
	{
		global $user;
		global $logger;
		if ($user && $user->loggedInViaCAS){
			require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
			$casAuthentication = new CASAuthentication(null);
			$casAuthentication->logout();
		}
		UserAccount::$isLoggedIn = false;
		UserAccount::$primaryUserData = null;
		session_destroy();
		session_regenerate_id(true);
		$_SESSION = array();
	}

	/**
	 * Remove user info from the session so the user is not logged in, but
	 * preserve hold message and search information
	 */
	public static function softLogout(){
		if (isset($_SESSION['activeUserId'])){
			unset($_SESSION['activeUserId']);
			global $user;
			if ($user && $user->loggedInViaCAS){
				require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
				$casAuthentication = new CASAuthentication(null);
				$casAuthentication->logout();
			}
			UserAccount::$isLoggedIn = false;
			UserAccount::$primaryUserData = null;
			session_commit();
		}
	}

	/**
	 * @return array
	 */
	protected static function loadAccountProfiles() {
		/** @var Memcache $memCache */
		global $memCache;
		global $instanceName;
		global $configArray;
		$accountProfiles = $memCache->get('account_profiles_' . $instanceName);

		if ($accountProfiles == false || isset($_REQUEST['reload'])){
			$accountProfiles = array();

			//Load a list of authentication methods to test and see which (if any) result in a valid login.
			require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
			$accountProfile = new AccountProfile();
			$accountProfile->orderBy('weight', 'name');
			$accountProfile->find();
			while ($accountProfile->fetch()) {
				$additionalInfo = array(
					'driver' => $accountProfile->driver,
					'authenticationMethod' => $accountProfile->authenticationMethod,
					'accountProfile' => clone($accountProfile)
				);
				$accountProfiles[$accountProfile->name] = $additionalInfo;
			}
			if (count($accountProfiles) == 0) {
				global $configArray;
				//Create default information for historic login.  This will eventually be obsolete
				$accountProfile = new AccountProfile();
				$accountProfile->orderBy('weight', 'name');
				$accountProfile->driver = $configArray['Catalog']['driver'];
				if (isset($configArray['Catalog']['url'])){
					$accountProfile->vendorOpacUrl = $configArray['Catalog']['url'];
				}
				$accountProfile->authenticationMethod = 'ils';
				if ($configArray['Catalog']['barcodeProperty'] == 'cat_password'){
					$accountProfile->loginConfiguration = 'username_barcode';
				}else{
					$accountProfile->loginConfiguration = 'barcode_pin';
				}
				if (isset($configArray['OPAC']['patron_host'])){
					$accountProfile->patronApiUrl = $configArray['OPAC']['patron_host'];
				}
				$accountProfile->recordSource = 'ils';
				$accountProfile->name = 'ils';

				$additionalInfo = array(
					'driver' => $configArray['Catalog']['driver'],
					'authenticationMethod' => $configArray['Authentication']['method'],
					'accountProfile' => $accountProfile
				);
				$accountProfiles['ils'] = $additionalInfo;
			}
			$memCache->set('account_profiles_' . $instanceName, $accountProfiles, 0, $configArray['Caching']['account_profiles']);
			global $timer;
			$timer->logTime("Loaded Account Profiles");
		}
		return $accountProfiles;
	}
}