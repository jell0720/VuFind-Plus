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

require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';

class OverDrive_Home extends Action{
	/** @var  SearchObject_Solr $db */
	private $id;
	private $isbn;

	function launch(){
		global $interface;
		global $configArray;

		if (isset($_REQUEST['searchId'])){
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		}else if (isset($_SESSION['searchId'])){
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		$interface->assign('overDriveVersion', isset($configArray['OverDrive']['interfaceVersion']) ? $configArray['OverDrive']['interfaceVersion'] : 1);

		$this->id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $this->id);
		$overDriveDriver = new OverDriveRecordDriver($this->id);

		if (!$overDriveDriver->isValid()){
			$interface->setTemplate('../Record/invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}else{
			$this->isbn = $overDriveDriver->getCleanISBN();
			$interface->assign('recordDriver', $overDriveDriver);

			//Load status summary
			require_once (ROOT_DIR . '/Drivers/OverDriveDriverFactory.php');
			$driver = OverDriveDriverFactory::getDriver();
			$holdings = $driver->getHoldings($overDriveDriver);
			$scopedAvailability = $driver->getScopedAvailability($overDriveDriver);
			$holdingsSummary = $driver->getStatusSummary($this->id, $scopedAvailability, $holdings);
			if (PEAR_Singleton::isError($holdingsSummary)) {
				PEAR_Singleton::raiseError($holdingsSummary);
			}
			$interface->assign('holdingsSummary', $holdingsSummary);

			//Load the citations
			$this->loadCitations($overDriveDriver);

			// Retrieve User Search History
			$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ?
			$_SESSION['lastSearchURL'] : false);

			//Get Next/Previous Links
			$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init($searchSource);
			$searchObject->getNextPrevLinks();

			$interface->setPageTitle($overDriveDriver->getTitle());
			$interface->assign('moreDetailsOptions', $overDriveDriver->getMoreDetailsOptions());

			// Display Page
			$interface->assign('sidebar', 'OverDrive/full-record-sidebar.tpl');
			$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
			$interface->setTemplate('view.tpl');

			$interface->display('layout.tpl');

		}
	}

	function loadCitations($recordDriver){
		global $interface;

		$citationCount = 0;
		$formats = $recordDriver->getCitationFormats();
		foreach($formats as $current) {
			$interface->assign(strtolower($current), $recordDriver->getCitation($current));
			$citationCount++;
		}
		$interface->assign('citationCount', $citationCount);
	}
}