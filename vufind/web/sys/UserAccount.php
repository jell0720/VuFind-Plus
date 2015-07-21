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

// This is necessary for unserialize
require_once ROOT_DIR . '/services/MyResearch/lib/User.php';

class UserAccount
{
	// Checks whether the user is logged in.
	public static function isLoggedIn()
	{
		if (isset($_SESSION['userinfo'])) {
			return unserialize($_SESSION['userinfo']);
		}
		return false;
	}

	// Updates the user information in the session.
	public static function updateSession($user)
	{
		$_SESSION['userinfo'] = serialize($user);
		if (isset($_REQUEST['rememberMe']) && ($_REQUEST['rememberMe'] === "true" || $_REQUEST['rememberMe'] === "on")){
			$_SESSION['rememberMe'] = true;
		}else{
			$_SESSION['rememberMe'] = false;
		}
		session_commit();
	}

	// Try to log in the user using current query parameters; return User object
	// on success, PEAR error on failure.
	public static function login() {
		global $configArray;

		$validUsers = array();

		//Test all valid authentication methods and see which (if any) result in a valid login.
		require_once ROOT_DIR . '/sys/Authentication/AccountProfile.php';
		$driversToTest = array();
		$accountProfile = new AccountProfile();
		$accountProfile->find();
		$user = null;
		while ($accountProfile->fetch()){
			$additionalInfo = array(
				'driver' => $accountProfile->driver,
				'authenticationMethod' => $accountProfile->authenticationMethod
			);
			$driversToTest[$accountProfile->name] = $additionalInfo;
		}
		if (count($driversToTest) == 0){
			$additionalInfo = array(
				'driver' => $configArray['Catalog']['driver'],
				'authenticationMethod' => $configArray['Authentication']['method']
			);
			$driversToTest['ils'] = $additionalInfo;
		}

		foreach ($driversToTest as $driverName => $additionalInfo){
			// Perform authentication:
			$authN = AuthenticationFactory::initAuthentication($additionalInfo['authenticationMethod'], $additionalInfo);
			$user = $authN->authenticate($additionalInfo);

			// If we authenticated, store the user in the session:
			if (!PEAR_Singleton::isError($user)) {
				$validUsers[] = $user;
				self::updateSession($user);
			}else{
				global $logger;
				$logger->log("Error authenticating patron for driver {$accountProfile->driver}\r\n" . print_r($user, true), PEAR_LOG_ERR);
			}
		}

		// Send back the user object (which may be a PEAR error):
		return $user;
	}

	/**
	 * Validate the account information (username and password are correct)
	 * @param $username
	 * @param $password
	 *
	 * @return User|PEAR_Error
	 */
	public static function validateAccount($username, $password){
		global $configArray;

		// Perform authentication:
		//Test all valid authentication methods and see which (if any) result in a valid login.
		$driversToTest = array();
		$accountProfile = new AccountProfile();
		$accountProfile->find();
		$user = null;
		while ($accountProfile->fetch()){
			$additionalInfo = array(
				'driver' => $accountProfile->driver,
				'authenticationMethod' => $accountProfile->authenticationMethod
			);
			$driversToTest[$accountProfile->name] = $additionalInfo;
		}
		if (count($driversToTest) == 0){
			$additionalInfo = array(
				'driver' => $configArray['Catalog']['driver'],
				'authenticationMethod' => $configArray['Authentication']['method']
			);
			$driversToTest['ils'] = $additionalInfo;
		}

		foreach ($driversToTest as $driverName => $additionalInfo){
			$additionalInfo = array('driver' => $accountProfile->driver);
			$authN = AuthenticationFactory::initAuthentication($additionalInfo['authenticationMethod'], $additionalInfo);
			if ( $authN->validateAccount($username, $password)){
				return true;
			}
		}
		return false;
	}

	/**
	 * Completely logout the user annihilating their entire session.
	 */
	public static function logout()
	{
		session_destroy();
		session_regenerate_id(true);
		$_SESSION = array();
	}

	/**
	 * Remove user info from the session so the user is not logged in, but
	 * preserve hold message and search information
	 */
	public static function softLogout(){
		if (isset($_SESSION['userinfo'])){
			unset($_SESSION['userinfo']);
		}
	}
}