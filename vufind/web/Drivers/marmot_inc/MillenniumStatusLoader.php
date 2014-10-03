<?php
/**
 * Class MillenniumStatusLoader
 *
 * Processes status information from Millennium to load holdings.
 */
class MillenniumStatusLoader{
	/** @var  MillenniumDriver $driver */
	private $driver;

	public function __construct($driver){
		$this->driver = $driver;
	}

	/**
	 * Load status (holdings) for a record and filter them based on the logged in user information.
	 *
	 * @param string            $id     the id of the record
	 * @return array A list of holdings for the record
	 */
	public function getStatus($id){
		global $library;
		global $user;
		global $timer;
		global $logger;
		global $configArray;

		$pType = $this->driver->getPType();
		$scope = $this->driver->getMillenniumScope();

		if (!$configArray['Catalog']['offline']){
			//Get information about holdings, order information, and issue information
			$millenniumInfo = $this->driver->getMillenniumRecordInfo($id);

			//Get the number of holds
			if ($millenniumInfo->framesetInfo){
				if (preg_match('/(\d+) hold(s?) on .*? of \d+ (copies|copy)/', $millenniumInfo->framesetInfo, $matches)){
					$holdQueueLength = $matches[1];
				}else{
					$holdQueueLength = 0;
				}
			}

			// Load Record Page
			$r = substr($millenniumInfo->holdingsInfo, stripos($millenniumInfo->holdingsInfo, 'bibItems'));
			$r = substr($r,strpos($r,">")+1);
			$r = substr($r,0,stripos($r,"</table"));
			$rows = preg_split("/<tr([^>]*)>/",$r);
		}else{
			$rows = array();
			$millenniumInfo = null;
		}

		//Load item information from marc record
		$matchItemsWithMarcItems = $configArray['Catalog']['matchItemsWithMarcItems'];
		if ($matchItemsWithMarcItems){
			// Load the full marc record so we can get the iType for each record.
			$marcRecord = MarcLoader::loadMarcRecordByILSId($id);
			$marcItemField = isset($configArray['Reindex']['itemTag']) ? $configArray['Reindex']['itemTag'] : '989';
			$itemFields = $marcRecord->getFields($marcItemField);
			$marcItemData = array();

			//TODO: Don't hardcode item subfields
			foreach ($itemFields as $itemField){
				/** @var $itemField File_MARC_Data_Field */
				$statusSubfield = $configArray['Reindex']['statusSubfield'];
				$dueDateSubfield = $configArray['Reindex']['dueDateSubfield'];
				$locationSubfield = $configArray['Reindex']['locationSubfield'];
				$iTypeSubfield = $configArray['Reindex']['iTypeSubfield'];
				$fullCallNumber = $itemField->getSubfield('s') != null ? ($itemField->getSubfield('s')->getData() . ' '): '';
				$fullCallNumber .= $itemField->getSubfield('a') != null ? $itemField->getSubfield('a')->getData() : '';
				$fullCallNumber .= $itemField->getSubfield('r') != null ? (' ' . $itemField->getSubfield('r')->getData()) : '';
				$itemData['callnumber'] = $fullCallNumber;
				$itemData['location'] = $itemField->getSubfield($locationSubfield) != null ? trim($itemField->getSubfield($locationSubfield)->getData()) : '?????';
				$itemData['iType'] = $itemField->getSubfield($iTypeSubfield) != null ? $itemField->getSubfield($iTypeSubfield)->getData() : '0';
				$itemData['matched'] = false;
				$itemData['status'] = $itemField->getSubfield($statusSubfield) != null ? $itemField->getSubfield($statusSubfield)->getData() : '-';
				$itemData['dueDate'] = $itemField->getSubfield($dueDateSubfield) != null ? trim($itemField->getSubfield($dueDateSubfield)->getData()) : null;
				$marcItemData[] = $itemData;
			}
		}else{
			$marcItemData = array();
			$marcRecord = null;
		}

		if (!$configArray['Catalog']['offline']){
			//Process each row in the callnumber table.
			$ret = $this->parseHoldingRows($id, $rows);

			if (count($ret) == 0){
				//Also check the frameset for links
				if (preg_match('/<div class="bibDisplayUrls">\s+<table.*?>(.*?)<\/table>.*?<\/div>/si', $millenniumInfo->framesetInfo, $displayUrlInfo)){
					$linksTable = $displayUrlInfo[1];
					preg_match_all('/<td.*?>.*?<a href="(.*?)".*?>(.*?)<\/a>.*?<\/td>/si', $linksTable, $linkData, PREG_SET_ORDER);
					for ($i = 0; $i < count($linkData); $i++) {
						$linkText = $linkData[$i][2];
						if ($linkText != 'Latest Received' && !preg_match('/\.(jpeg|jpg|gif|png)$/',$linkText)){
						//if ($linkText != 'Latest Received'){
							$newHolding = array(
									'type' => 'holding',
									'link' => array(),
									'status' => 'Online',
									'location' => 'Online'
							);
							$newHolding['link'][] = array(
									'link' => $linkData[$i][1],
									'linkText' => $linkText,
									'isDownload' => true
							);
							$ret[] = $newHolding;
						}
					}
				}
			}

			$timer->logTime('processed all holdings rows');
		}else{
			$ret = null;
		}


		global $locationSingleton; /** @var $locationSingleton Location */
		$physicalLocation = $locationSingleton->getPhysicalLocation();
		if ($physicalLocation != null){
			$physicalBranch = $physicalLocation->holdingBranchLabel;
		}else{
			$physicalBranch = '';
		}
		$homeBranch    = '';
		$homeBranchId  = 0;
		$nearbyBranch1 = '';
		$nearbyBranch1Id = 0;
		$nearbyBranch2 = '';
		$nearbyBranch2Id = 0;

		//Set location information based on the user login.  This will override information based
		if (isset($user) && $user != false){
			$homeBranchId = $user->homeLocationId;
			$nearbyBranch1Id = $user->myLocation1Id;
			$nearbyBranch2Id = $user->myLocation2Id;
		} else {
			//Check to see if the cookie for home location is set.
			if (isset($_COOKIE['home_location']) && is_numeric($_COOKIE['home_location'])) {
				$cookieLocation = new Location();
				$locationId = $_COOKIE['home_location'];
				$cookieLocation->whereAdd("locationId = '$locationId'");
				$cookieLocation->find();
				if ($cookieLocation->N == 1) {
					$cookieLocation->fetch();
					$homeBranchId = $cookieLocation->locationId;
					$nearbyBranch1Id = $cookieLocation->nearbyLocation1;
					$nearbyBranch2Id = $cookieLocation->nearbyLocation2;
				}
			}
		}
		//Load the holding label for the user's home location.
		$userLocation = new Location();
		$userLocation->whereAdd("locationId = '$homeBranchId'");
		$userLocation->find();
		if ($userLocation->N == 1) {
			$userLocation->fetch();
			$homeBranch = $userLocation->holdingBranchLabel;
		}
		//Load nearby branch 1
		$nearbyLocation1 = new Location();
		$nearbyLocation1->whereAdd("locationId = '$nearbyBranch1Id'");
		$nearbyLocation1->find();
		if ($nearbyLocation1->N == 1) {
			$nearbyLocation1->fetch();
			$nearbyBranch1 = $nearbyLocation1->holdingBranchLabel;
		}
		//Load nearby branch 2
		$nearbyLocation2 = new Location();
		$nearbyLocation2->whereAdd();
		$nearbyLocation2->whereAdd("locationId = '$nearbyBranch2Id'");
		$nearbyLocation2->find();
		if ($nearbyLocation2->N == 1) {
			$nearbyLocation2->fetch();
			$nearbyBranch2 = $nearbyLocation2->holdingBranchLabel;
		}
		$sorted_array = array();

		//Get a list of the display names for all locations based on holding label.
		$locationLabels = array();
		$location = new Location();
		$location->find();
		$libraryLocationLabels = array();
		$locationCodes = array();
		$suppressedLocationCodes = array();
		while ($location->fetch()){
			if (strlen($location->holdingBranchLabel) > 0 && $location->holdingBranchLabel != '???'){
				if ($library && $library->libraryId == $location->libraryId){
					$cleanLabel =  str_replace('/', '\/', $location->holdingBranchLabel);
					$libraryLocationLabels[] = str_replace('.', '\.', $cleanLabel);
				}

				$locationLabels[$location->holdingBranchLabel] = $location->displayName;
				$locationCodes[$location->code] = $location->holdingBranchLabel;
				if ($location->suppressHoldings == 1){
					$suppressedLocationCodes[$location->code] = $location->code;
				}
			}
		}
		if (count($libraryLocationLabels) > 0){
			$libraryLocationLabels = '/^(' . join('|', $libraryLocationLabels) . ').*/i';
		}else{
			$libraryLocationLabels = '';
		}

		//Get the current Ptype for later usage.
		$timer->logTime('setup for additional holdings processing.');

		//Now that we have the holdings, we need to filter and sort them according to scoping rules.
		if (!$configArray['Catalog']['offline']){
			$i = 0;
			foreach ($ret as $holdingKey => $holding){
				$holding['type'] = 'holding';
				//Process holdings without call numbers - Need to show items without call numbers
				//because they may have links, etc.  Also show if there is a status.  Since some
				//In process items may not have a call number yet.
				if ( (!isset($holding['callnumber']) || strlen($holding['callnumber']) == 0) &&
				(!isset($holding['link']) || count($holding['link']) == 0) && !isset($holding['status'])){
					continue;
				}

				//Determine if the holding is available or not.
				//First check the status
				if (preg_match('/^(' . $this->driver->availableStatiRegex . ')$/', $holding['status'])){
					$holding['availability'] = 1;
				}else{
					$holding['availability'] = 0;
				}
				if (preg_match('/^(' . $this->driver->holdableStatiRegex . ')$/', $holding['status'])){
					$holding['holdable'] = 1;
				}else{
					$holding['holdable'] = 0;
					$holding['nonHoldableReason'] = "This item is not currently available for Patron Holds";
				}

				if (!isset($holding['libraryDisplayName'])){
					$holding['libraryDisplayName'] = $holding['location'];
				}

				//Get the location id for this holding
				$holding['locationCode'] = '?????';
				foreach ($locationCodes as $locationCode => $holdingLabel){
					if (strlen($locationCode) > 0 && preg_match("~$holdingLabel~i", $holding['location'])){
						$holding['locationCode'] = $locationCode;
					}
				}
				if ($holding['locationCode'] == '?????'){
					$logger->log("Did not find location code for " . $holding['location'] . " record $id", PEAR_LOG_DEBUG);
				}
				if (array_key_exists($holding['locationCode'], $suppressedLocationCodes)){
					$logger->log("Location " . $holding['locationCode'] . " is suppressed", PEAR_LOG_DEBUG);
					continue;
				}

				//Now that we have the location code, try to match with the marc record
				$holding['iType'] = 0;
				if ($matchItemsWithMarcItems){
					foreach ($marcItemData as $itemData){
						if (!$itemData['matched']){
							$locationMatched = (strpos($itemData['location'], $holding['locationCode']) === 0);
							$itemCallNumber = isset($itemData['callnumber']) ? $itemData['callnumber'] : '';
							$holdingCallNumber = isset($holding['callnumber']) ? $holding['callnumber'] : '';
							if (strlen($itemCallNumber) == 0 || strlen($holding['callnumber']) == 0){
								$callNumberMatched = (strlen($itemCallNumber) == strlen($holdingCallNumber));
							}else{
								$callNumberMatched = (strpos($itemCallNumber, $holdingCallNumber) >= 0);
							}
							if ($locationMatched && $callNumberMatched){
								$holding['iType'] = $itemData['iType'];
								$itemData['matched'] = true;
							}
						}
					}

					//Check to see if this item can be held by the current patron.  Only important when
					//we know what pType is in use and we are showing all items.
					if ($scope == $this->driver->getDefaultScope() && $pType > 0){
						//Never remove the title if it is owned by the current library (could be in library use only)
						if (isset($library) && strlen($library->ilsCode) > 0 && strpos($holding['locationCode'], $library->ilsCode) === 0){
							$logger->log("Cannot remove holding because it belongs to the active library", PEAR_LOG_DEBUG);
						}else{
							if (!$this->driver->isItemHoldableToPatron($holding['locationCode'], $holding['iType'], $pType)){
								$logger->log("Removing item $holdingKey because it is not usable by the current patronType $pType, iType is {$holding['iType']}, location is {$holding['locationCode']}", PEAR_LOG_DEBUG);
								//echo("Removing item $holdingKey because it is not usable by the current patronType $pType, iType is {$holding['iType']}, location is {$holding['locationCode']}");
								unset($ret[$holdingKey]);
								continue;
							}
						}
					}
				}

				//Set the hold queue length
				$holding['holdQueueLength'] = isset($holdQueueLength) ? $holdQueueLength : null;

				//Add the holding to the sorted array to determine
				$paddedNumber = str_pad($i, 3, '0', STR_PAD_LEFT);
				$sortString = $holding['location'] . '-'. $paddedNumber;
				//$sortString = $holding['location'] . $holding['callnumber']. $i;
				if (strlen($physicalBranch) > 0 && stripos($holding['location'], $physicalBranch) !== false){
					//If the user is in a branch, those holdings come first.
					$holding['section'] = 'In this library';
					$holding['sectionId'] = 1;
					$sorted_array['1' . $sortString] = $holding;
				} else if (strlen($homeBranch) > 0 && stripos($holding['location'], $homeBranch) !== false){
					//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
					$holding['section'] = 'Your library';
					$holding['sectionId'] = 2;
					$sorted_array['2' . $sortString] = $holding;
				} else if ((strlen($nearbyBranch1) > 0 && stripos($holding['location'], $nearbyBranch1) !== false)){
					//Next come nearby locations for the user
					$holding['section'] = 'Nearby Libraries';
					$holding['sectionId'] = 3;
					$sorted_array['3' . $sortString] = $holding;
				} else if ((strlen($nearbyBranch2) > 0 && stripos($holding['location'], $nearbyBranch2) !== false)){
					//Next come nearby locations for the user
					$holding['section'] = 'Nearby Libraries';
					$holding['sectionId'] = 4;
					$sorted_array['4' . $sortString] = $holding;
				} else if (strlen($libraryLocationLabels) > 0 && preg_match($libraryLocationLabels, $holding['location'])){
					//Next come any locations within the same system we are in.
					$holding['section'] = $library->displayName;
					$holding['sectionId'] = 5;
					$sorted_array['5' . $sortString] = $holding;
				} else {
					//Finally, all other holdings are shown sorted alphabetically.
					$holding['section'] = 'Other Locations';
					$holding['sectionId'] = 6;
					$sorted_array['6' . $sortString] = $holding;
				}
				$i++;
			}
		}else{
			$i = 0;
			//Offline circ, process each item in the marc record
			foreach ($marcItemData as $marcData){
				$i++;
				$holding = array();
				$holding['type'] = 'holding';
				$holding['locationCode'] = $marcData['location'];
				$holding['callnumber'] = $marcData['callnumber'];
				$holding['statusfull'] = $this->translateStatusCode($marcData['status'], $marcData['dueDate']);

				//Try to translate the location code at least to location
				$location = new Location();
				$location->whereAdd("LOCATE(code, '{$marcData['location']}') = 1");
				if ($location->find(true)){
					$holding['location'] = $location->displayName;
				}else{
					$holding['location'] = $marcData['location'];
				}

				if (array_key_exists($holding['locationCode'], $suppressedLocationCodes)){
					$logger->log("Location " . $holding['locationCode'] . " is suppressed", PEAR_LOG_DEBUG);
					continue;
				}
				$holding['iType'] = $marcData['iType'];
				if ($marcData['status'] == '-' && $marcData['dueDate'] == null){
					$holding['availability'] = 1;
				}else{
					$holding['availability'] = 0;
				}
				//Check to see if this item can be held by the current patron.  Only important when
				//we know what pType is in use and we are showing all items.
				if ($scope == $this->driver->getDefaultScope() && $pType > 0){
					//Never remove the title if it is owned by the current library (could be in library use only)
					if (isset($library) && strlen($library->ilsCode) > 0 && strpos($holding['locationCode'], $library->ilsCode) === 0){
						$logger->log("Cannot remove holding because it belongs to the active library", PEAR_LOG_DEBUG);
					}else{
						if (!$this->driver->isItemHoldableToPatron($holding['locationCode'], $holding['iType'], $pType)){
							$logger->log("Removing item because it is not usable by the current patronType $pType, iType is {$holding['iType']}, location is {$holding['locationCode']}", PEAR_LOG_DEBUG);
							//echo("Removing item $holdingKey because it is not usable by the current patronType $pType, iType is {$holding['iType']}, location is {$holding['locationCode']}");
							continue;
						}
					}
				}
				$paddedNumber = str_pad($i, 3, '0', STR_PAD_LEFT);
				$sortString = $holding['location'] . '-'. $paddedNumber;
				if (strlen($physicalBranch) > 0 && stripos($holding['location'], $physicalBranch) !== false){
					//If the user is in a branch, those holdings come first.
					$holding['section'] = 'In this library';
					$holding['sectionId'] = 1;
					$sorted_array['1' . $sortString] = $holding;
				} else if (strlen($homeBranch) > 0 && stripos($holding['location'], $homeBranch) !== false){
					//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
					$holding['section'] = 'Your library';
					$holding['sectionId'] = 2;
					$sorted_array['2' . $sortString] = $holding;
				} else if ((strlen($nearbyBranch1) > 0 && stripos($holding['location'], $nearbyBranch1) !== false)){
					//Next come nearby locations for the user
					$holding['section'] = 'Nearby Libraries';
					$holding['sectionId'] = 3;
					$sorted_array['3' . $sortString] = $holding;
				} else if ((strlen($nearbyBranch2) > 0 && stripos($holding['location'], $nearbyBranch2) !== false)){
					//Next come nearby locations for the user
					$holding['section'] = 'Nearby Libraries';
					$holding['sectionId'] = 4;
					$sorted_array['4' . $sortString] = $holding;
				} else if (strlen($libraryLocationLabels) > 0 && preg_match($libraryLocationLabels, $holding['location'])){
					//Next come any locations within the same system we are in.
					$holding['section'] = $library->displayName;
					$holding['sectionId'] = 5;
					$sorted_array['5' . $sortString] = $holding;
				} else {
					//Finally, all other holdings are shown sorted alphabetically.
					$holding['section'] = 'Other Locations';
					$holding['sectionId'] = 6;
					$sorted_array['6' . $sortString] = $holding;
				}
			}
		}
		$timer->logTime('finished processing holdings');

		//Check to see if the title is holdable
		$holdable = $this->driver->isRecordHoldable($marcRecord);
		foreach ($sorted_array as $key => $holding){
			//Do not override holdability based on status
			if ($holding['holdable'] == 1){
				$holding['holdable'] = $holdable ? 1 : 0;
				$sorted_array[$key] = $holding;
			}
		}

		if (!$configArray['Catalog']['offline']){
			//Load order records, these only show in the full page view, not the item display
			$orderMatches = array();
			if (preg_match_all('/<tr\\s+class="bibOrderEntry">.*?<td\\s*>(.*?)<\/td>/s', $millenniumInfo->framesetInfo, $orderMatches)){
				for ($i = 0; $i < count($orderMatches[1]); $i++) {
					$location = trim($orderMatches[1][$i]);
					$location = preg_replace('/\\sC\\d{3}[\\s\\.]/', '', $location);
					//Remove courier code if any
					$sorted_array['7' . $location . $i] = array(
	                    'location' => $location,
	                    'section' => 'On Order',
	                    'sectionId' => 7,
	                    'holdable' => 1,
					);
				}
			}
			$timer->logTime('loaded order records');
		}

		ksort($sorted_array);

		//Check to see if we can remove the sections.
		//We can if all section keys are the same.
		$removeSection = true;
		$lastKeyIndex = '';
		foreach ($sorted_array as $key => $holding){
			$currentKey = substr($key, 0, 1);
			if ($lastKeyIndex == ''){
				$lastKeyIndex = $currentKey;
			}else if ($lastKeyIndex != $currentKey){
				$removeSection = false;
				break;
			}
		}
		foreach ($sorted_array as $key => $holding){
			if ($removeSection == true){
				$holding['section'] = '';
				$sorted_array[$key] = $holding;
			}
		}

		if (!$configArray['Catalog']['offline']){
			$issueSummaries = $this->driver->getIssueSummaries($millenniumInfo);
		}else{
			$issueSummaries = null;
		}

		$timer->logTime('loaded issue summaries');
		if (!is_null($issueSummaries)){
			krsort($sorted_array);
			//Group holdings under the issue issue summary that is related.
			foreach ($sorted_array as $key => $holding){
				//Have issue summary = false
				$haveIssueSummary = false;
				$issueSummaryKey = null;
				foreach ($issueSummaries as $issueKey => $issueSummary){
					if ($issueSummary['location'] == $holding['location']){
						$haveIssueSummary = true;
						$issueSummaryKey = $issueKey;
						break;
					}
				}

				if ($haveIssueSummary){
					$issueSummaries[$issueSummaryKey]['holdings'][strtolower($key)] = $holding;
				}else{
					//Need to automatically add a summary so we don't lose data
					$issueSummaries[$holding['location']] = array(
                        'location' => $holding['location'],
                        'type' => 'issue',
                        'holdings' => array(strtolower($key) => $holding),
					);
				}
			}
			foreach ($issueSummaries as $key => $issueSummary){
				if (isset($issueSummary['holdings']) && is_array($issueSummary['holdings'])){
					krsort($issueSummary['holdings']);
					$issueSummaries[$key] = $issueSummary;
				}
			}
			ksort($issueSummaries);
			return $issueSummaries;
		}else{
			return $sorted_array;
		}
	}

	private function translateStatusCode($status, $dueDate){
		$statuses = array(
			'-' => 'On Shelf',
			'm' => 'Missing',
			'n' => 'Billed',
			'z' => 'Claims Returned',
			't' => 'In Transit',
			's' => 'On Search',
			'o' => 'Library Use Only',
			'$' => 'Lost and Paid',
			'!' => 'On Hold Shelf',
			'r' => 'Repair',
			'b' => 'Bindery',
			'd' => 'Display',
			'g' => 'Damaged',
			'w' => 'New Book Shelf',
			'h' => 'On Reserve',
			'p' => 'In Process',
			'l' => 'Gone',
			'u' => 'Restricted Use',
			'q' => 'On Order',
			'c' => 'School Closed',
			'f' => 'Collections',
			'j' => 'Online',
			'a' => 'Storage',
			'#' => 'Prospector Received',
			'%' => 'Prospector Returned',
			'*' => 'Prospector Missing',
			'@' => 'Prospector Off Campus',
			'(' => 'Prospector Paged',
			')' => 'Prospector Cancelled',
			'_' => 'Prospector Re-request',
			'&' => 'Prospector Requested',
			'i' => 'Missing from Inventory'
		);
		if ($status == '-'){
			if (!is_null($dueDate) && strlen($dueDate) > 0){
				//Reformat the date
				$dueDateAsDate = DateTime::createFromFormat('ymd', $dueDate);
				return 'Due ' . $dueDateAsDate->format('m-d-y');
			}else{
				return 'On Shelf';
			}
		}else{
			return $statuses{$status};
		}
	}

	/**
	 * @param string            $id       The id of the record in the database
	 * @param array             $rows     An array of strings for each row in the Millennium holdings table
	 * @return array
	 */
	private function parseHoldingRows($id, $rows){
		$keys = array_pad(array(),10,"");

		global $configArray;
		$loc_col_name      = $configArray['OPAC']['location_column'];
		$call_col_name     = $configArray['OPAC']['call_no_column'];
		$status_col_name   = $configArray['OPAC']['status_column'];
		$reserves_col_name = $configArray['OPAC']['location_column'];
		$reserves_key_name = $configArray['OPAC']['reserves_key_name'];
		$transit_key_name  = $configArray['OPAC']['transit_key_name'];
		$stat_due          = $configArray['OPAC']['status_due'];

		$ret = array();
		$count = 0;
		$numHoldings = 0;
		foreach ($rows as $row) {
			//Skip the first row, it is always blank.
			if ($count == 0){
				$count++;
				continue;
			}
			//Break up each row into columns
			$cols = array();
			preg_match_all('/<t[dh].*?>\\s*(?:\\s*<!-- .*? -->\\s*)*\\s*(.*?)\\s*<\/t[dh]>/s', $row, $cols, PREG_PATTERN_ORDER);

			$curHolding = array();
			$addHolding = true;
			//Process each cell
			for ($i=0; $i < sizeof($cols[1]); $i++) {
				//Get the value of the cell
				$cellValue = str_replace("&nbsp;"," ",$cols[1][$i]);
				$cellValue = trim(html_entity_decode($cellValue));
				if ($count == 1) {
					//Header cell, this will become the key used later.
					$keys[$i] = $cellValue;
					$addHolding = false;
				} else {
					//We are in the body of the call number field.
					if (sizeof($cols[1]) == 1){
						//This is a special case, i.e. a download link.  Process it differently
						//Get the last holding we processed.
						if (count($ret) > 0){
							$lastHolding = $ret[$numHoldings -1];
							$linkParts = array();
							if (preg_match_all('/<a href=[\'"](.*?)[\'"]>(.*)(?:<\/a>)*/s', $cellValue, $linkParts)){
								$linkCtr = 0;
								foreach ($linkParts[1] as $index => $linkInfo){
									$linkText = $linkParts[2][$index];
									$linkText = trim(preg_replace('/Click here (for|to) access\.?\s*/', '', $linkText));
									$isDownload = preg_match('/(SpringerLink|NetLibrary|digital media|Online version|ebrary|gutenberg|Literature Online)\.?/i', $linkText);
									$linkUrl = $linkParts[1][$index];
									if (preg_match('/netlibrary/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'NetLibrary';
									}elseif (preg_match('/ebscohost/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'Ebsco';
									}elseif (preg_match('/overdrive/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'OverDrive';
									}elseif (preg_match('/ebrary/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'ebrary';
									}elseif (preg_match('/gutenberg/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'Gutenberg Project';
									}elseif (preg_match('/gale/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'Gale Group';
									}
									$lastHolding['link'][] = array('link' => $linkUrl,
                                                                   'linkText' => $linkText,
                                                                   'isDownload' => $isDownload);
									$linkCtr++;
								}
								$ret[$numHoldings -1] = $lastHolding;
							}

							$addHolding = false;
						}
					}else{
						//This is a normal call number row.
						//should have Location, Call Number, and Status
						if (stripos($keys[$i],$loc_col_name) > -1) {
							//If the location has a link in it, it is a link to a map of the library
							//Process that differently and store independently
							if (preg_match('/<a href=[\'"](.*?)[\'"]>(.*)/s', $cellValue, $linkParts)){
								$curHolding['locationLink'] = $linkParts[1];
								$location = trim($linkParts[2]);
								if (substr($location, strlen($location) -4, 4) == '</a>'){
									$location = substr($location, 0, strlen($location) -4);
								}
								$curHolding['location'] = $location;

							}else{
								$curHolding['location'] = strip_tags($cellValue);
							}
							//Trim off the courier code if one exists
							if (preg_match('/(.*?)\\sC\\d{3}\\w{0,2}$/', $curHolding['location'], $locationParts)){
								$curHolding['location'] = $locationParts[1];
							}
						}
						if (stripos($keys[$i],$reserves_col_name) > -1) {
							if (stripos($cellValue,$reserves_key_name) > -1) {  // if the location name has "reserves"
								$curHolding['reserve'] = 'Y';
							} else if(stripos($cols[1][$i],$transit_key_name) > -1) {
								$curHolding['reserve'] = 'Y';
							} else {
								$curHolding['reserve'] = 'N';
							}
						}
						if (stripos($keys[$i],$call_col_name) > -1) {
							$curHolding['callnumber'] = strip_tags($cellValue);
						}
						if (stripos($keys[$i],$status_col_name) > -1) {
							//Load status information
							$curHolding['status'] = $cellValue;
							if (stripos($cellValue,$stat_due) > -1) {
								$p = substr($cellValue,stripos($cellValue,$stat_due));
								$s = trim($p, $stat_due);
								$curHolding['duedate'] = $s;
							}

							$statfull = strip_tags($cellValue);
							if (isset($this->driver->statusTranslations[$statfull])){
								$statfull = $this->driver->statusTranslations[$statfull];
							}else{
								$statfull = strtolower($statfull);
								$statfull = ucwords($statfull);
							}
							$curHolding['statusfull'] = $statfull;
						}
					}

				}
			} //End looping through columns

			if ($addHolding){
				$numHoldings++;
				$curHolding['id'] = $id;
				$curHolding['number'] = $numHoldings;
				$curHolding['holdQueueLength'] = isset($holdQueueLength) ? $holdQueueLength : null;
				$ret[] = $curHolding;
			}
			$count++;
		} //End looping through rows
		return $ret;
	}

	/**
	 * Returns a summary of the holdings information for a single id. Used to display
	 * within the search results and at the top of a full record display to ensure
	 * the holding information makes sense to all users.
	 *
	 * @param string $id the id of the bid to load holdings for
	 * @param boolean $forSearch whether or not the summary will be shown in search results
	 * @return array an associative array with a summary of the holdings.
	 */
	public function getStatusSummary($id, $forSearch = false){
		global $configArray;
		$holdings = MillenniumStatusLoader::getStatus($id);
		$summaryInformation = array();
		$summaryInformation['recordId'] = $id;
		$summaryInformation['shortId'] = substr($id, 1);
		$summaryInformation['isDownloadable'] = false; //Default value, reset later if needed.

		if ($configArray['Catalog']['offline']){
			$summaryInformation['offline'] = true;
			$summaryInformation['status'] = 'The circulation system is offline, status not available.';
			$summaryInformation['holdable'] = true;
			$summaryInformation['class'] = "unavailable";
			$summaryInformation['showPlaceHold'] = true;
			return $summaryInformation;
		}

		global $library;
		/** Location $locationSingleton */
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		$canShowHoldButton = true;
		if ($library){
			if ($forSearch){
				$canShowHoldButton = ($library->showHoldButtonInSearchResults != 0);
			}else{
				$canShowHoldButton = ($library->showHoldButton != 0);
			}
		}
		if ($location){
			if ($forSearch){
				if ($library){
					$canShowHoldButton = ($library->showHoldButtonInSearchResults != 0);
				}else{
					$canShowHoldButton = ($location->showHoldButton != 0);
				}
			}else{
				$canShowHoldButton = ($location->showHoldButton != 0);
			}
		}
		$physicalLocation = $locationSingleton->getPhysicalLocation();

		//Check to see if we are getting issue summaries or actual holdings
		if (count($holdings) > 0){
			$lastHolding = end($holdings);
			if (isset($lastHolding['type']) && ($lastHolding['type'] == 'issueSummary' || $lastHolding['type'] == 'issue')){
				$issueSummaries = $holdings;
				$holdings = array();
				foreach ($issueSummaries as $issueSummary){
					if (isset($issueSummary['holdings'])){
						$holdings = array_merge($holdings, $issueSummary['holdings']);
					}else{
						//Create a fake holding for subscriptions so something
						//will be displayed in the holdings summary.
						$holdings[$issueSummary['location']] = array(
							'availability' => '1',
							'location' => $issueSummary['location'],
							'libraryDisplayName' => $issueSummary['location'],
							'callnumber' => isset($issueSummary['cALL']) ? $issueSummary['cALL'] : '',
							'showPlaceHold' => $canShowHoldButton,
						);
						$summaryInformation['status'] = 'Available';
						$summaryInformation['statusfull'] = 'Available';
						$summaryInformation['class'] = 'available';
					}
				}
			}
		}

		//Valid statuses are:
		//It's here
		//  - at the physical location and not checked out
		//  - also show the call number for the location
		//  - do not show place hold button
		//It's at *location*
		//  - at the user's home branch or preferred location and not checked out
		//  - also show the call number for the location
		//  - show place hold button
		//Available by Request
		//  - not at the user's home branch or preferred location, but at least one copy is not checked out
		//  - do not show the call number
		//  - show place hold button
		//Checked Out
		//  - all copies are checked out
		//  - show the call number for the local library if any
		//  - show place hold button
		//Downloadable
		//  - there is at least one download link for the record.
		$numAvailableCopies = 0;
		$numHoldableCopies = 0;
		$numCopies = 0;
		$numCopiesOnOrder = 0;
		$availableLocations = array();
		$additionalAvailableLocations = array();
		$unavailableStatus = null;
		$holdQueueLength = 0;
		//The status of all items.  Will be set to an actual status if all are the same
		//or null if the item statuses are inconsistent
		$allItemStatus = '';
		$firstCallNumber = null;
		$firstLocation = null;
		foreach ($holdings as $holdingKey => $holding){
			if (is_null($allItemStatus)){
				//Do nothing, the status is not distinct
			}else if ($allItemStatus == '' && isset($holding['statusfull'])){
				$allItemStatus = $holding['statusfull'];
			}elseif(isset($holding['statusfull']) && $allItemStatus != $holding['statusfull']){
				$allItemStatus = null;
			}
			if (isset($holding['holdQueueLength'])){
				$holdQueueLength = $holding['holdQueueLength'];
			}
			if (isset($holding['availability']) && $holding['availability'] == 1){
				$numAvailableCopies++;
				$addToAvailableLocation = false;
				$addToAdditionalAvailableLocation = false;
				//Check to see if the location should be listed in the list of locations that the title is available at.
				//Can only be in this system if there is a system active.
				if (sizeof($availableLocations) < 3 && !in_array($holding['libraryDisplayName'], $availableLocations)){
					if (isset($library)){
						//Check to see if the location is within this library system. It is if the key is less than or equal to 5
						if (substr($holdingKey, 0, 1) <= 5){
							$addToAvailableLocation = true;
						}
					}else{
						$addToAvailableLocation = true;
					}
				}
				//Check to see if the location is listed in the count of additional locations (can be any system).
				if (!$addToAvailableLocation && !in_array($holding['libraryDisplayName'], $availableLocations) && !in_array($holding['libraryDisplayName'], $additionalAvailableLocations)){
					$addToAdditionalAvailableLocation = true;
				}
				if ($addToAvailableLocation){
					$availableLocations[] = $holding['libraryDisplayName'];
				}elseif ($addToAdditionalAvailableLocation){
					$additionalAvailableLocations[] = $holding['libraryDisplayName'];
				}
			}else{
				if ($unavailableStatus == null && isset($holding['status'])){
					$unavailableStatus = $holding['status'];
				}
			}

			if (isset($holding['holdable']) && $holding['holdable'] == 1){
				$numHoldableCopies++;
			}
			$numCopies++;

			//Check to see if the holding has a download link and if so, set that info.
			if (isset($holding['link'])){
				foreach ($holding['link'] as $link){
					if ($link['isDownload']){
						$summaryInformation['status'] = "Available for Download";
						$summaryInformation['class'] = 'here';
						$summaryInformation['isDownloadable'] = true;
						$summaryInformation['downloadLink'] = $link['link'];
						$summaryInformation['downloadText'] = $link['linkText'];
					}
				}
			}

			//Only show a call number if the book is at the user's home library, one of their preferred libraries, or in the library they are in.
			$showItsHere = ($library == null) ? true : ($library->showItsHere == 1);
			if (in_array(substr($holdingKey, 0, 1), array('1', '2', '3', '4', '5')) && !isset($summaryInformation['callnumber'])){
				//Try to get an available non reserver call number
				if ($holding['availability'] == 1 && $holding['holdable'] == 1){
					//echo("Including call number " . $holding['callnumber'] . " because is  holdable");
					$summaryInformation['callnumber'] = $holding['callnumber'];
				}else if (is_null($firstCallNumber)){
					//echo("Skipping call number " . $holding['callnumber'] . " because it is holdable");
					$firstCallNumber = $holding['callnumber'];
				}else if (is_null($firstLocation)){
					//echo("Skipping call number " . $holding['callnumber'] . " because it is holdable");
					$firstLocation = $holding['location'];
				}
			}
			if ($showItsHere && substr($holdingKey, 0, 1) == '1' && $holding['availability'] == 1){
				//The item is available within the physical library.  Patron should go get it off the shelf
				$summaryInformation['status'] = "It's here";
				$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				$summaryInformation['class'] = 'here';
				$summaryInformation['location'] = $holding['location'];
			}elseif ($showItsHere && !isset($summaryInformation['status']) &&
					substr($holdingKey, 0, 1) >= 2 && (substr($holdingKey, 0, 1) <= 4) &&
					$holding['availability'] == 1 ){
				if (!isset($summaryInformation['class']) || $summaryInformation['class'] != 'here'){
					//The item is at one of the patron's preferred branches.
					$summaryInformation['status'] = "It's at " . $holding['location'];
					$summaryInformation['showPlaceHold'] = $canShowHoldButton;
					$summaryInformation['class'] = 'nearby';
					$summaryInformation['location'] = $holding['location'];
				}
			}elseif (!isset($summaryInformation['status']) &&
					((!$showItsHere && substr($holdingKey, 0, 1) <= 5) || substr($holdingKey, 0, 1) == 5 || !isset($library) ) &&
					(isset($holding['availability']) && $holding['availability'] == 1)){
				if (!isset($summaryInformation['class']) || ($summaryInformation['class'] != 'here' && $summaryInformation['class'] = 'nearby')){
					//The item is at a location either in the same system or another system.
					$summaryInformation['status'] = "Available At";
					$summaryInformation['showPlaceHold'] = $canShowHoldButton;
					if ($physicalLocation != null){
						$summaryInformation['class'] = 'availableOther';
					}else{
						$summaryInformation['class'] = 'available';
					}
				}
			}elseif (!isset($summaryInformation['status']) &&
					(substr($holdingKey, 0, 1) == 6 ) &&
					(isset($holding['availability']) && $holding['availability'] == 1)){
				//The item is at a location either in the same system or another system.
				$summaryInformation['status'] = "Marmot";
				$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				$summaryInformation['class'] = 'available';
			}elseif (substr($holdingKey, 0, 1) == 7){
				$numCopiesOnOrder++;
				$numCopies--; //Don't increment number of copies for titles we don't have yet.
			}
		}

		//If all items are checked out the status will still be blank
		$summaryInformation['availableCopies'] = $numAvailableCopies;
		$summaryInformation['holdableCopies'] = $numHoldableCopies;
		if ($numHoldableCopies == 0){
			$summaryInformation['showPlaceHold'] = false;
		}

		$summaryInformation['numCopiesOnOrder'] = $numCopiesOnOrder;
		//Do some basic sanity checking to make sure that we show the total copies
		//With at least as many copies as the number of copies on order.
		if ($numCopies < $numCopiesOnOrder){
			$summaryInformation['numCopies'] = $numCopiesOnOrder;
		}else{
			$summaryInformation['numCopies'] = $numCopies;
		}
		$summaryInformation['copies'] = "$numAvailableCopies of $numCopies are on shelf";
		if ($numCopiesOnOrder > 0){
			$summaryInformation['copies'] .= ", $numCopiesOnOrder on order";
		}

		$summaryInformation['holdQueueLength'] = $holdQueueLength;

		if ($unavailableStatus != 'ONLINE'){
			$summaryInformation['unavailableStatus'] = $unavailableStatus;
		}

		if (isset($summaryInformation['status']) && $summaryInformation['status'] != "It's here"){
			//Replace all spaces in the name of a location with no break spaces
			foreach ($availableLocations as $key => $location){
				$availableLocations[$key] = str_replace(' ', ' ', $location);
			}
			$summaryInformation['availableAt'] = join(', ', $availableLocations);
			if ($summaryInformation['status'] == 'Marmot'){
				$summaryInformation['numAvailableOther'] = count($additionalAvailableLocations) + count($availableLocations);
			}else{
				$summaryInformation['numAvailableOther'] = count($additionalAvailableLocations);
			}
		}

		//If Status is still not set, apply some logic based on number of copies
		if (!isset($summaryInformation['status'])){
			if ($numCopies == 0){
				if ($numCopiesOnOrder > 0){
					//No copies are currently available, but we do have some that are on order.
					//show the status as on order and make it available.
					$summaryInformation['status'] = "On Order";
					$summaryInformation['class'] = 'available';
					$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				}else{
					//Deal with weird cases where there are no items by saying it is unavailable
					$summaryInformation['status'] = "Unavailable";
					$summaryInformation['showPlaceHold'] = false;
					$summaryInformation['class'] = 'unavailable';
				}
			}else{
				if ($numHoldableCopies == 0 && $canShowHoldButton){
					$summaryInformation['status'] = "Not Available For Checkout";
					$summaryInformation['showPlaceHold'] = false;
					$summaryInformation['class'] = 'reserve';
				}else{
					$summaryInformation['status'] = "Checked Out";
					$summaryInformation['showPlaceHold'] = $canShowHoldButton;
					$summaryInformation['class'] = 'checkedOut';
				}
			}
		}

		//Reset status if the status for all items is consistent.
		//That way it will jive with the actual full record display.
		if ($allItemStatus != null && $allItemStatus != ''){
			//Only override this for statuses that don't have special meaning
			if ($summaryInformation['status'] != 'Marmot' && $summaryInformation['status'] != 'Available At' && $summaryInformation['class'] != 'here' && $summaryInformation['class'] != 'nearby'){
				$summaryInformation['status'] = $allItemStatus;
			}
		}
		if ($allItemStatus == 'In Library Use Only'){
			$summaryInformation['inLibraryUseOnly'] = true;
		}else{
			$summaryInformation['inLibraryUseOnly'] = false;
		}


		if ($summaryInformation['availableCopies'] == 0 && $summaryInformation['isDownloadable'] == true){
			$summaryInformation['showAvailabilityLine'] = false;
		}else{
			$summaryInformation['showAvailabilityLine'] = true;
		}

		//Clear unavailable status if it matches the status
		if (isset($summaryInformation['unavailableStatus']) && strcasecmp(trim($summaryInformation['unavailableStatus']), trim($summaryInformation['status'])) == 0){
			$summaryInformation['unavailableStatus'] = '';
		}

		//Reset call number as needed
		if (!is_null($firstCallNumber) && !isset($summaryInformation['callnumber'])){
			$summaryInformation['callnumber'] = $firstCallNumber;
		}
		//Reset location as needed
		if (!is_null($firstLocation) && !isset($summaryInformation['location'])){
			$summaryInformation['location'] = $firstLocation;
		}

		//Set Status text for the summary
		if ($summaryInformation['status'] == 'Available At'){
			if ($summaryInformation['numCopies'] == 0){
				$summaryInformation['statusText'] = "No Copies Found";
			}else{
				if (strlen($summaryInformation['availableAt']) > 0){
					$summaryInformation['statusText'] = "Available now" . ($summaryInformation['inLibraryUseOnly'] ? "for in library use" : "") . " at " . $summaryInformation['availableAt'] . ($summaryInformation['numAvailableOther'] > 0 ? (", and {$summaryInformation['numAvailableOther']} other location" . ($summaryInformation['numAvailableOther'] > 1 ? "s" : "")) : "");
				}else{
					$summaryInformation['statusText'] = "Available now" . ($summaryInformation['inLibraryUseOnly'] ? "for in library use" : "");
				}
			}
		}else if ($summaryInformation['status'] == 'Marmot'){
			$summaryInformation['class'] = "nearby";
			$totalLocations = intval($summaryInformation['numAvailableOther']) + intval($summaryInformation['availableAt']);
			$summaryInformation['statusText'] = "Available now at " . $totalLocations . " Marmot " . ($totalLocations == 1 ? "Library" : "Libraries");
		}else{
			$summaryInformation['statusText'] = translate($summaryInformation['status']);
		}

		return $summaryInformation;
	}
}
