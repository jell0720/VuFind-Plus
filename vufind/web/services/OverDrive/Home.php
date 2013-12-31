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

require_once ROOT_DIR . '/services/Record/UserComments.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
require_once ROOT_DIR . '/sys/SolrStats.php';

class OverDrive_Home extends Action{
	/** @var  SearchObject_Solr $db */
	private $id;
	private $isbn;
	private $issn;
	private $recordDriver;

	function launch(){
		global $interface;
		global $timer;
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

			$interface->assign('cleanDescription', strip_tags($overDriveDriver->getDescriptionFast(), '<p><br><b><i><em><strong>'));

			//$interface->assign('ratingData', $overDriveDriver->getRatingData($user, false));

			//Determine the cover to use
			//$bookCoverUrl = $configArray['Site']['coverUrl'] . "/bookcover.php?id={$eContentRecord->id}&amp;econtent=true&amp;issn={$eContentRecord->getIssn()}&amp;isn={$eContentRecord->getIsbn()}&amp;size=large&amp;upc={$eContentRecord->getUpc()}&amp;category=" . urlencode($eContentRecord->format_category()) . "&amp;format=" . urlencode($eContentRecord->getFirstFormat());
			//$interface->assign('bookCoverUrl', $bookCoverUrl);

			if (isset($_REQUEST['detail'])){
				$detail = strip_tags($_REQUEST['detail']);
				$interface->assign('defaultDetailsTab', $detail);
			}

			//Load the citations
			//$this->loadCitation($eContentRecord);

			// Retrieve User Search History
			$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ?
			$_SESSION['lastSearchURL'] : false);

			//Get Next/Previous Links
			$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init($searchSource);
			$searchObject->getNextPrevLinks();

			// Retrieve tags associated with the record
			$limit = 5;
			$resource = new Resource();
			$resource->record_id = $_GET['id'];
			$resource->source = 'eContent';
			$resource->find(true);
			$tags = $resource->getTags($limit);
			$interface->assign('tagList', $tags);
			$timer->logTime('Got tag list');

			//Load notes if any
			//$marcRecord = MarcLoader::loadEContentMarcRecord($eContentRecord);
//			if ($marcRecord){
//				$tableOfContents = array();
//				$marcFields505 = $marcRecord->getFields('505');
//				if ($marcFields505){
//					$tableOfContents = $this->processTableOfContentsFields($marcFields505);
//				}
//
//				$notes = array();
//				/*$marcFields500 = $marcRecord->getFields('500');
//				$marcFields504 = $marcRecord->getFields('504');
//				$marcFields511 = $marcRecord->getFields('511');
//				$marcFields518 = $marcRecord->getFields('518');
//				$marcFields520 = $marcRecord->getFields('520');
//				if ($marcFields500 || $marcFields504 || $marcFields505 || $marcFields511 || $marcFields518 || $marcFields520){
//					$allFields = array_merge($marcFields500, $marcFields504, $marcFields511, $marcFields518, $marcFields520);
//					$notes = $this->processNoteFields($allFields);
//				}*/
//
//				if ((isset($library) && $library->showTableOfContentsTab == 0) || count($tableOfContents) == 0) {
//					$notes = array_merge($notes, $tableOfContents);
//				}else{
//					$interface->assign('tableOfContents', $tableOfContents);
//				}
//				if (isset($library) && strlen($library->notesTabName) > 0){
//					$interface->assign('notesTabName', $library->notesTabName);
//				}else{
//					$interface->assign('notesTabName', 'Notes');
//				}
//
//        $additionalNotesFields = array(
//	                '520' => 'Description',
//	                '500' => 'General Note',
//	                '504' => 'Bibliography',
//	                '511' => 'Participants/Performers',
//	                '518' => 'Date/Time and Place of Event',
//                  '310' => 'Current Publication Frequency',
//                  '321' => 'Former Publication Frequency',
//                  '351' => 'Organization & arrangement of materials',
//                  '362' => 'Dates of publication and/or sequential designation',
//                  '501' => '"With"',
//                  '502' => 'Dissertation',
//                  '506' => 'Restrictions on Access',
//                  '507' => 'Scale for Graphic Material',
//                  '508' => 'Creation/Production Credits',
//                  '510' => 'Citation/References',
//                  '513' => 'Type of Report an Period Covered',
//                  '515' => 'Numbering Peculiarities',
//                  '521' => 'Target Audience',
//                  '522' => 'Geographic Coverage',
//                  '525' => 'Supplement',
//                  '526' => 'Study Program Information',
//                  '530' => 'Additional Physical Form',
//                  '533' => 'Reproduction',
//                  '534' => 'Original Version',
//                  '536' => 'Funding Information',
//                  '538' => 'System Details',
//                  '545' => 'Biographical or Historical Data',
//                  '546' => 'Language',
//                  '547' => 'Former Title Complexity',
//                  '550' => 'Issuing Body',
//                  '555' => 'Cumulative Index/Finding Aids',
//                  '556' => 'Information About Documentation',
//                  '561' => 'Ownership and Custodial History',
//                  '563' => 'Binding Information',
//                  '580' => 'Linking Entry Complexity',
//                  '581' => 'Publications About Described Materials',
//                  '586' => 'Awards',
//                  '590' => 'Local note',
//                  '599' => 'Differentiable Local note',
//        );
//
//				foreach ($additionalNotesFields as $tag => $label){
//					$marcFields = $marcRecord->getFields($tag);
//					foreach ($marcFields as $marcField){
//						$noteText = array();
//						foreach ($marcField->getSubFields() as $subfield){
//							$noteText[] = $subfield->getData();
//						}
//						$note = implode(',', $noteText);
//						if (strlen($note) > 0){
//							$notes[] = "<dt>$label</dt><dd>" . $note . '</dd>';
//						}
//					}
//				}
//
//				if (count($notes) > 0){
//					$interface->assign('notes', $notes);
//				}
//			}
//
//			//Load subjects
//			if ($marcRecord){
//				if (isset($configArray['Content']['subjectFieldsToShow'])){
//					$subjectFieldsToShow = $configArray['Content']['subjectFieldsToShow'];
//					$subjectFields = explode(',', $subjectFieldsToShow);
//
//					$subjects = array();
//					$standardSubjects = array();
//					$bisacSubjects = array();
//					$oclcFastSubjects = array();
//					foreach ($subjectFields as $subjectField){
//						/** @var File_MARC_Data_Field[] $marcFields */
//						$marcFields = $marcRecord->getFields($subjectField);
//						if ($marcFields){
//							foreach ($marcFields as $marcField){
//								$searchSubject = "";
//								$subject = array();
//								//Determine the type of the subject
//								$type = 'standard';
//								$subjectSource = $marcField->getSubfield('2');
//								if ($subjectSource != null){
//									if (preg_match('/bisac/i', $subjectSource->getData())){
//										$type = 'bisac';
//									}elseif (preg_match('/fast/i', $subjectSource->getData())){
//										$type = 'fast';
//									}
//								}
//
//								foreach ($marcField->getSubFields() as $subField){
//									/** @var File_MARC_Subfield $subField */
//									if ($subField->getCode() != '2' && $subField->getCode() != '0'){
//										$subFieldData = $subField->getData();
//										if ($type == 'bisac' && $subField->getCode() == 'a'){
//											$subFieldData = ucwords(strtolower($subFieldData));
//										}
//										$searchSubject .= " " . $subFieldData;
//										$subject[] = array(
//											'search' => trim($searchSubject),
//											'title'  => $subFieldData,
//										);
//									}
//								}
//								if ($type == 'bisac'){
//									$bisacSubjects[] = $subject;
//									$subjects[] = $subject;
//								}elseif ($type == 'fast'){
//									//Suppress fast subjects by default
//									$oclcFastSubjects[] = $subject;
//								}else{
//									$subjects[] = $subject;
//									$standardSubjects[] = $subject;
//								}
//
//							}
//						}
//						$interface->assign('subjects', $subjects);
//						$interface->assign('standardSubjects', $standardSubjects);
//						$interface->assign('bisacSubjects', $bisacSubjects);
//						$interface->assign('oclcFastSubjects', $oclcFastSubjects);
//					}
//				}
//			}else{
//				$rawSubjects = $eContentRecord->getPropertyArray('subject');
//				$subjects = array();
//				foreach ($rawSubjects as $subject){
//					$explodedSubjects = explode(' -- ', $subject);
//					$searchSubject = "";
//					$subject = array();
//					foreach ($explodedSubjects as $tmpSubject){
//						$searchSubject .= $tmpSubject . ' ';
//						$subject[] = array(
//							'search' => trim($searchSubject),
//							'title'  => $tmpSubject,
//						);
//					}
//					$subjects[] = $subject;
//				}
//				$interface->assign('subjects', $subjects);
//			}
//
//			$this->loadReviews($eContentRecord);

			//Build the actual view
			$interface->setTemplate('view.tpl');

			$interface->setPageTitle($overDriveDriver->getTitle());

			//Var for the IDCLREADER TEMPLATE
			$interface->assign('ButtonBack',true);
			$interface->assign('ButtonHome',true);
			$interface->assign('MobileTitle','&nbsp;');

			//Load Staff Details
			$interface->assign('staffDetails', $overDriveDriver->getStaffView());

			// Display Page
			$interface->display('layout.tpl');

		}
	}

	function loadReviews($eContentRecord){
		global $interface;

		//Load the Editorial Reviews
		//Populate an array of editorialReviewIds that match up with the recordId
		$editorialReview = new EditorialReview();
		$editorialReviewResults = array();
		$editorialReview->recordId = 'econtentRecord' . $eContentRecord->id;
		$editorialReview->find();
		$reviewTabs = array();
		$editorialReviewResults['reviews'] = array(
			'tabName' => 'Reviews',
			'reviews' => array()
		);
		if ($editorialReview->N > 0){
			$ctr = 0;
			while ($editorialReview->fetch()){
				$reviewKey = preg_replace('/\W/', '_', strtolower($editorialReview->tabName));
				if (!array_key_exists($reviewKey, $editorialReviewResults)){
					$editorialReviewResults[$reviewKey] = array(
						'tabName' => $editorialReview->tabName,
						'reviews' => array()
					);
				}
				$editorialReviewResults[$reviewKey]['reviews'][$ctr++] = get_object_vars($editorialReview);
			}
		}

		if ($interface->isMobile()){
			//If we are in mobile interface, load standard reviews
			$reviews = array();
			require_once ROOT_DIR . '/sys/Reviews.php';
			if ($eContentRecord->getIsbn()){
				$externalReviews = new ExternalReviews($eContentRecord->getIsbn());
				$reviews = $externalReviews->fetch();
			}

			if (count($editorialReviewResults) > 0) {
				foreach ($editorialReviewResults as $tabName => $reviewsList){
					foreach ($reviewsList['reviews'] as $key=>$result ){
						$reviews["editorialReviews"][$key]["Content"] = $result['review'];
						$reviews["editorialReviews"][$key]["Copyright"] = $result['source'];
						$reviews["editorialReviews"][$key]["Source"] = $result['source'];
						$reviews["editorialReviews"][$key]["ISBN"] = null;
						$reviews["editorialReviews"][$key]["username"] = null;


						$reviews["editorialReviews"][$key] = ExternalReviews::cleanupReview($reviews["editorialReviews"][$key]);
						if ($result['teaser']){
							$reviews["editorialReviews"][$key]["Teaser"] = $result['teaser'];
						}
					}
				}
			}
			$interface->assign('reviews', $reviews);
			$interface->assign('editorialReviews', $editorialReviewResults);
		}else{
			$interface->assign('reviews', $editorialReviewResults);
		}


	}

	/**
	 * Load information from the review provider and update the interface with the data.
	 *
	 * @return array       Returns array with review data, otherwise a
	 *                      PEAR_Error.
	 */
	function loadEnrichment(){
		global $interface;
		global $configArray;

		// Fetch from provider
		if (isset($configArray['Content']['enrichment'])) {
			$providers = explode(',', $configArray['Content']['enrichment']);
			foreach ($providers as $provider) {
				$provider = explode(':', trim($provider));
				$func = strtolower($provider[0]);
				$enrichment[$func] = $this->$func();

				// If the current provider had no valid reviews, store nothing:
				if (empty($enrichment[$func]) || PEAR_Singleton::isError($enrichment[$func])) {
					unset($enrichment[$func]);
				}
			}
		}

		if ($enrichment) {
			$interface->assign('enrichment', $enrichment);
		}

		return $enrichment;
	}

	function loadCitation($eContentRecord)
	{
		global $interface;

		$citationCount = 0;
		$formats = $this->recordDriver->getCitationFormats();
		foreach($formats as $current) {
			$interface->assign(strtolower($current), $this->recordDriver->getCitation($current));
			$citationCount++;
		}
		$interface->assign('citationCount', $citationCount);
	}

	function processNoteFields($allFields){
		$notes = array();
		foreach ($allFields as $marcField){
			foreach ($marcField->getSubFields() as $subfield){
				$note = $subfield->getData();
				if ($subfield->getCode() == 't'){
					$note = "&nbsp;&nbsp;&nbsp;" . $note;
				}
				$note = trim($note);
				if (strlen($note) > 0){
					$notes[] = $note;
				}
			}
		}
		return $notes;
	}

	function processTableOfContentsFields($allFields){
		$notes = array();
		foreach ($allFields as $marcField){
			$curNote = '';
			foreach ($marcField->getSubFields() as $subfield){
				$note = $subfield->getData();
				$curNote .= " " . $note;
				$curNote = trim($curNote);
				if (strlen($curNote) > 0 && in_array($subfield->getCode(), array('t', 'a'))){
					$notes[] = $curNote;
					$curNote = '';
				}
			}
		}
		return $notes;
	}
}