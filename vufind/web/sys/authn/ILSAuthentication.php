<?php
require_once 'Authentication.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class ILSAuthentication implements Authentication {
	private $username;
	private $password;
	public function authenticate(){
		global $configArray;

		$this->username = $_REQUEST['username'];
		$this->password = $_REQUEST['password'];

/*		if($this->username == '' || $this->password == ''){	*/
		if($this->username == ''){
			$user = new PEAR_Error('authentication_error_blank');
		} else {
			// Connect to Database
			$catalog = new CatalogConnection($configArray['Catalog']['driver']);

			if ($catalog->status) {
				$patron = $catalog->patronLogin($this->username, $this->password);
				if ($patron && !PEAR_Singleton::isError($patron)) {
					$user = $this->processILSUser($patron);
				} else {
					$user = new PEAR_Error('authentication_error_invalid');
				}
			} else {
				$user = new PEAR_Error('authentication_error_technical');
			}
		}
		return $user;
	}

	public function validateAccount($username, $password) {
		return $this->authenticate();
	}

	private function processILSUser($info){
		require_once ROOT_DIR . "/services/MyResearch/lib/User.php";

		$user = new User();
		//Marmot make sure we are using the username which is the
		//unique patron ID in Millennium.
		$user->username = $info['username'];
		if ($user->find(true)) {
			$insert = false;
		} else {
			$insert = true;
		}

		$user->password = $info['cat_password'];
		$user->firstname    = $info['firstname']    == null ? " " : $info['firstname'];
		$user->lastname     = $info['lastname']     == null ? " " : $info['lastname'];
		$user->cat_username = $info['cat_username'] == null ? " " : $info['cat_username'];
		$user->cat_password = $info['cat_password'] == null ? " " : $info['cat_password'];
		$user->email        = $info['email']        == null ? " " : $info['email'];
		$user->major        = $info['major']        == null ? " " : $info['major'];
		$user->college      = $info['college']      == null ? " " : $info['college'];
		$user->patronType   = $info['patronType']   == null ? " " : $info['patronType'];
		$user->web_note     = $info['web_note']     == null ? " " : $info['web_note'];

		if ($insert) {
			$user->created = date('Y-m-d');
			$user->insert();
		} else {
			$user->update();
		}

		return $user;
	}
}
?>
