<?php
/**
 *
 * Copyright (C) Anythink Libraries 2012.
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
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Anythink Libraries 2012.
 *
 */

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . "/sys/MaterialsRequest.php";

/**
 * MaterialsRequest Home Page, displays an existing Materials Request.
 */
class MaterialsRequest_NewRequest extends Action
{

	function launch()
	{
		global $configArray,
		       $interface,
		       $user,
		       $library,
		       $locationSingleton;
		
		if ($user){
			$interface->assign('defaultPhone', $user->phone);
			$interface->assign('defaultEmail', $user->email);
			$locations = $locationSingleton->getPickupBranches($user, $user->homeLocationId);
		}else{
			$locations = $locationSingleton->getPickupBranches(false, -1);
		}
		
		$interface->assign('pickupLocations', $locations);
		
		//Get a list of formats to show 
		$availableFormats = MaterialsRequest::getFormats();
		$interface->assign('availableFormats', $availableFormats);
		
		//Setup a default title based on the search term
		$interface->assign('new', true);
		if (isset($_REQUEST['lookfor']) && strlen ($_REQUEST['lookfor']) > 0){ 
			$request = new MaterialsRequest();
			$searchType = isset($_REQUEST['basicType']) ? $_REQUEST['basicType'] : (isset($_REQUEST['type']) ? $_REQUEST['type'] : 'Keyword');
			if (strcasecmp($searchType, 'author') == 0){
				$request->author = $_REQUEST['lookfor'];
			}else{
				$request->title = $_REQUEST['lookfor'];
			}
			$interface->assign('materialsRequest', $request);
		}

		$interface->assign('showPhoneField', $configArray['MaterialsRequest']['showPhoneField']);
		$interface->assign('showAgeField', $configArray['MaterialsRequest']['showAgeField']);
		$interface->assign('showBookTypeField', $configArray['MaterialsRequest']['showBookTypeField']);
		$interface->assign('showEbookFormatField', $configArray['MaterialsRequest']['showEbookFormatField']);
		$interface->assign('showEaudioFormatField', $configArray['MaterialsRequest']['showEaudioFormatField']);
		$interface->assign('showPlaceHoldField', $configArray['MaterialsRequest']['showPlaceHoldField']);
		$interface->assign('showIllField', $configArray['MaterialsRequest']['showIllField']);
		$interface->assign('requireAboutField', $configArray['MaterialsRequest']['requireAboutField']);
		
		$useWorldCat = false;
		if (isset($configArray['WorldCat']) && isset($configArray['WorldCat']['apiKey'])){
			$useWorldCat = strlen($configArray['WorldCat']['apiKey']) > 0;
		}
		$interface->assign('useWorldCat', $useWorldCat);

		// Set up for User Log in
		if (isset($library)){
			$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
			$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
			$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');
		}else{
			$interface->assign('enableSelfRegistration', 0);
			$interface->assign('usernameLabel', 'Your Name');
			$interface->assign('passwordLabel', 'Library Card Number');
		}

		$interface->setTemplate('new.tpl');
		$interface->assign('sidebar', 'Search/home-sidebar.tpl');
		$interface->setPageTitle('Materials Request');
		
		$interface->display('layout.tpl');
	}
}