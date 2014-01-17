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

require_once "Action.php";
require_once "sys/MaterialsRequest.php";
require_once "sys/MaterialsRequestStatus.php";

/**
 * MaterialsRequest Submission processing, processes a new request for the user and
 * displays a success/fail message to the user.
 */
class Submit extends Action
{

	function launch()
	{
		global $configArray;
		global $interface;
		global $user;
		global $library;
		$maxActiveRequests = isset($library) ? $library->maxOpenRequests : 5;
		$maxRequestsPerYear = isset($library) ? $library->maxRequestsPerYear : 60;
		$accountPageLink = $configArray['Site']['path'] . '/MaterialsRequest/MyRequests';

		//Make sure that the user is valid
		$processForm = true;
		if (!$user){
			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];
			$user = UserAccount::login();
			if ($user == null){
				$interface->assign('error', 'Sorry, we could not log you in.  Please enter a valid barcode and pin number submit a materials request.');
				$processForm = false;
			}
		}
		if ($processForm){
			//Check to see if the user type is ok to submit a request
			$enableMaterialsRequest = true;
			if (isset($configArray['MaterialsRequest']['allowablePatronTypes'])){
				//Check to see if we need to do additonal restrictions by patron type
				$allowablePatronTypes = $configArray['MaterialsRequest']['allowablePatronTypes'];
				if (strlen($allowablePatronTypes) > 0 && $user){
					if (!preg_match("/^$allowablePatronTypes$/i", $user->patronType)){
						$enableMaterialsRequest = false;
					}
				}
			}
			if (!$enableMaterialsRequest){
				$interface->assign('success', false);
				$interface->assign('error', 'Sorry, only residents may submit materials requests at this time.');
			}else if ($_REQUEST['format'] == 'article' && $_REQUEST['acceptCopyright'] != 1){
				$interface->assign('success', false);
				$interface->assign('error', 'Sorry, you must accept the copyright agreement before submitting a materials request.');
			}else{
				//Check to see how many active materials request results the user has already.
				$materialsRequest = new MaterialsRequest();
				$materialsRequest->createdBy = $user->id;
				$statusQuery = new MaterialsRequestStatus();
				$statusQuery->isOpen = 1;
				$materialsRequest->joinAdd($statusQuery);
				$materialsRequest->selectAdd();
				$materialsRequest->selectAdd('materials_request.*, description as statusLabel');
				$materialsRequest->find();
				if ($materialsRequest->N >= $maxActiveRequests){
					$interface->assign('success', false);
					$interface->assign('error', "You've already reached your maximum limit of $maxActiveRequests requests open at one time. Once we've processed your existing requests, you'll be able to submit again. To check the status of your current requests, visit your <a href='{$accountPageLink}'>account page</a>.");
				}else{
					//Check the total number of requests created this year
					$lastYear = new DateTime();
					$lastYear->modify('-365 days');
					$materialsRequest = new MaterialsRequest();
					$materialsRequest->createdBy = $user->id;
					$materialsRequest->whereAdd('dateCreated >= ' . $lastYear->getTimestamp());
					//To be fair, don't include any requests that were cancelled by the patron
					$statusQuery = new MaterialsRequestStatus();
					$statusQuery->isPatronCancel = 0;
					$materialsRequest->joinAdd($statusQuery);
					$materialsRequest->find();
					if ($materialsRequest->N >= $maxRequestsPerYear){
						$interface->assign('success', false);
						$interface->assign('error', "We were unable to process your request because you have reached your $maxRequestsPerYear-item limit. Please search the catalog or visit your local Anythink to find a different item that might meet your needs. To check the status of your current requests, visit your <a href='{$accountPageLink}'>account page</a>.");
					}else{
						//Materials request can be submitted.
						$materialsRequest = new MaterialsRequest();
						$materialsRequest->phone = isset($_REQUEST['phone']) ? strip_tags($_REQUEST['phone']) : '';
						$materialsRequest->email = strip_tags($_REQUEST['email']);
						$materialsRequest->title = strip_tags($_REQUEST['title']);
						$materialsRequest->season = isset($_REQUEST['season']) ? strip_tags($_REQUEST['season']) : '';
						$materialsRequest->magazineTitle = isset($_REQUEST['magazineTitle']) ? strip_tags($_REQUEST['magazineTitle']) : '';
						$materialsRequest->magazineDate = isset($_REQUEST['magazineDate']) ? strip_tags($_REQUEST['magazineDate']) : '';
						$materialsRequest->magazineVolume = isset($_REQUEST['magazineVolume']) ? strip_tags($_REQUEST['magazineVolume']) : '';
						$materialsRequest->magazineNumber = isset($_REQUEST['magazineNumber']) ? strip_tags($_REQUEST['magazineNumber']) : '';
						$materialsRequest->magazinePageNumbers = isset($_REQUEST['magazinePageNumbers']) ? strip_tags($_REQUEST['magazinePageNumbers']) : '';
						$materialsRequest->author = strip_tags($_REQUEST['author']);
						$materialsRequest->format = strip_tags($_REQUEST['format']);
						if ($materialsRequest->format == 'ebook' && isset($_REQUEST['ebookFormat'])){
							$materialsRequest->subFormat = strip_tags($_REQUEST['ebookFormat']);
						}elseif ($materialsRequest->format == 'eaudio' && isset($_REQUEST['eaudioFormat'])){
							$materialsRequest->subFormat = strip_tags($_REQUEST['eaudioFormat']);
						}
						$materialsRequest->subFormat = isset($_REQUEST['subFormat']) ? strip_tags($_REQUEST['subFormat']) : '';
						$materialsRequest->ageLevel = isset($_REQUEST['ageLevel']) ? strip_tags($_REQUEST['ageLevel']) : '';
						$materialsRequest->bookType = isset($_REQUEST['bookType']) ? strip_tags($_REQUEST['bookType']) : '';
						$materialsRequest->isbn = isset($_REQUEST['isbn']) ? strip_tags($_REQUEST['isbn']) : '';
						$materialsRequest->upc = isset($_REQUEST['upc']) ? strip_tags($_REQUEST['upc']) : '';
						$materialsRequest->issn = isset($_REQUEST['issn']) ? strip_tags($_REQUEST['issn']) : '';
						$materialsRequest->oclcNumber = isset($_REQUEST['oclcNumber']) ? strip_tags($_REQUEST['oclcNumber']) : '';
						$materialsRequest->publisher = strip_tags($_REQUEST['publisher']);
						$materialsRequest->publicationYear = strip_tags($_REQUEST['publicationYear']);
						if (isset($_REQUEST['abridged'])){
							if ($_REQUEST['abridged'] == 'abridged'){
								$materialsRequest->abridged = 1;
							}elseif($_REQUEST['abridged'] == 'unabridged'){
								$materialsRequest->abridged = 0;
							}else{
								$materialsRequest->abridged = 2; //Not applicable
							}
						}
						$materialsRequest->about = strip_tags($_REQUEST['about']);
						$materialsRequest->comments = strip_tags($_REQUEST['comments']);
						if (isset($_REQUEST['placeHoldWhenAvailable'])){
							$materialsRequest->placeHoldWhenAvailable = $_REQUEST['placeHoldWhenAvailable'];
						}else{
							$materialsRequest->placeHoldWhenAvailable = 0;
						}
						if (isset($_REQUEST['holdPickupLocation'])){
							$materialsRequest->holdPickupLocation = $_REQUEST['holdPickupLocation'];
						}
						if (isset($_REQUEST['bookmobileStop'])){
							$materialsRequest->bookmobileStop = $_REQUEST['bookmobileStop'];
						}
						if (isset($_REQUEST['illItem'])){
							$materialsRequest->illItem = $_REQUEST['illItem'];
						}else{
							$materialsRequest->illItem = 0;
						}
						$defaultStatus = new MaterialsRequestStatus();
						$defaultStatus->isDefault = 1;
						if (!$defaultStatus->find(true)){
							$interface->assign('success', false);
							$interface->assign('error', 'There was an error submitting your materials request, could not determine the default status.');
						}else{
							$materialsRequest->status = $defaultStatus->id;
							$materialsRequest->dateCreated = time();
							$materialsRequest->createdBy = $user->id;
							$materialsRequest->dateUpdated = time();

							// We'd like to search the catalog for this particular material. If
							// it is available, we'll put it on hold for the user.
							if (!empty($materialsRequest->isbn)) {
								// Potentially save _REQUEST array during this period.
								$original_request = $_REQUEST;
								// Build dummy $_REQUEST. These classes are very poorly
								// designed and require superglobals.
								$_REQUEST = array(
									'module' => 'Search',
									'action' => 'Results',
									'bool0' => array(
										0 => 'AND'
									),
									'lookfor0' => array(
										0 => $materialsRequest->isbn,
									),
									'type0' => array(
										0 => 'ISN'
									),
									'join' => 'AND',
									'submit' => 'Find',
									'searchSource' => 'local',
									'type' => 'Keyword',
								);
								$searchObject = SearchObjectFactory::initSearchObject();
								$searchObject->init('local');
								$result = $searchObject->processSearch();
								// If record exists, redirect to hold screen.
								if ($searchObject->getResultTotal() == 1) {
									$recordSet = $searchObject->getResultRecordSet();
									$record = reset($recordSet);
									if ($record['recordtype'] == 'econtentRecord'){
										header('Location: ' . $interface->getUrl() . '/EcontentRecord/' . str_replace('econtentRecord', '', $record['id']) . '/Hold?mr=1');
									}
									else {
										header('Location: ' . $interface->getUrl() . '/Record/' . $record['id'] . '/Hold?mr=1');
									}
								}
								// Results were not constrained to single result, thus restore
								// _REQUEST array and continue.
								$_REQUEST = $original_request;
							}


							if ($materialsRequest->insert()){
								$interface->assign('success', true);
								$interface->assign('materialsRequest', $materialsRequest);
							}else{
								$interface->assign('success', false);
								$interface->assign('error', 'There was an error submitting your materials request.');
							}
						}
					}
				}
			}
		}

		$interface->setTemplate('submision-result.tpl');
		$interface->setPageTitle('Submission Result');
		$interface->display('layout.tpl');
	}
}
