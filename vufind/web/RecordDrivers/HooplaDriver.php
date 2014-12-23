<?php
/**
 * Record Driver to handle loading data for Hoopla Records
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/18/14
 * Time: 10:50 AM
 */

require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
class HooplaRecordDriver extends MarcRecord {
	/**
	 * Constructor.  We build the object using data from the Hoopla records stored on disk.
	 * Will be similar to a MarcRecord with slightly different functionality
	 *
	 * @param array|File_MARC_Record|string $record
	 * @access  public
	 */
	public function __construct($record) {
		if ($record instanceof File_MARC_Record){
			$this->marcRecord = $record;
		}elseif (is_string($record)){
			require_once ROOT_DIR . '/sys/MarcLoader.php';
			$this->id = $record;

			$this->valid = MarcLoader::marcExistsForHooplaId($record);
		}else{
			// Call the parent's constructor...
			parent::__construct($record);

			// Also process the MARC record:
			require_once ROOT_DIR . '/sys/MarcLoader.php';
			$this->marcRecord = MarcLoader::loadMarcRecordFromRecord($record);
			if (!$this->marcRecord) {
				$this->valid = false;
			}
		}
		parent::loadGroupedWork();
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getShortId()
	{
		return $this->id;
	}

	/**
	 * @return File_MARC_Record
	 */
	public function getMarcRecord(){
		if ($this->marcRecord == null){
			$this->marcRecord = MarcLoader::loadMarcRecordByHooplaId($this->id);
			global $timer;
			$timer->logTime("Finished loading marc record for hoopla record {$this->id}");
		}
		return $this->marcRecord;
	}

	protected function getRecordType(){
		return 'hoopla';
	}

	private $relatedRecords = null;
	function getRelatedRecords(){
		if ($this->relatedRecords == null){
			global $configArray;
			global $timer;
			global $interface;
			$relatedRecords = array();
			$recordId = $this->getUniqueID();

			$url = $this->getRecordUrl();

			//Load data needed for the related record
			if ($this->detailedRecordInfoFromIndex){
				//This is fast because it is already loaded within the index
				$format = $this->detailedRecordInfoFromIndex[1];
				$edition = $this->detailedRecordInfoFromIndex[2];
				$language = $this->detailedRecordInfoFromIndex[3];
				$publisher = $this->detailedRecordInfoFromIndex[4];
				$publicationDate = $this->detailedRecordInfoFromIndex[5];
				$physicalDescription = $this->detailedRecordInfoFromIndex[6];
				$timer->logTime("Finished loading information from indexed info for $recordId");
			}else{
				//This is slow because we need to load from marc record
				$publishers = $this->getPublishers();
				$publisher = count($publishers) >= 1 ? $publishers[0] : '';
				$publicationDates = $this->getPublicationDates();
				$publicationDate = count($publicationDates) >= 1 ? $publicationDates[0] : '';
				$physicalDescriptions = $this->getPhysicalDescriptions();
				$physicalDescription = count($physicalDescriptions) >= 1 ? $physicalDescriptions[0] : '';
				$format = reset($this->getFormat());
				$edition = $this->getEdition(true);
				$language = $this->getLanguage();
				$timer->logTime("Finished loading MARC information in getRelatedRecords $recordId");
			}

			$formatCategory = mapValue('format_category_by_format', $format);

			//TODO: Load items?

			//Setup our record
			$relatedRecord = array(
				'id' => $recordId,
				'url' => $url,
				'format' => $format,
				'formatCategory' => $formatCategory,
				'edition' => $edition,
				'language' => $language,
				'publisher' => $publisher,
				'publicationDate' => $publicationDate,
				'physical' => $physicalDescription,
				'callNumber' => '',
				'available' => true,
				'availableOnline' => true,
				'availableLocally' => false,
				'availableHere' => false,
				'inLibraryUseOnly' => false,
				'availableCopies' => 'Unlimited',
				'copies' => 'Unlimited',
				'onOrderCopies' => 0,
				'localAvailableCopies' => 'Unlimited',
				'localCopies' => 'Unlimited',
				'numHolds' => 0,
				'hasLocalItem' => true,
				'holdRatio' => 999999,
				'locationLabel' => 'Hoopla Digital',
				'shelfLocation' => 'Hoopla Digital',
				//'itemSummary' => $this->getItemSummary(),
				'groupedStatus' => 'Available Online',
				'source' => 'Hoopla',
				'actions' => $this->getActions()
			);

			$this->relatedRecords[] = $relatedRecord;
		}
		return $this->relatedRecords;
	}

	function getRecordUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		return $configArray['Site']['path'] . '/Hoopla/' . $recordId;
	}

	private function getActions() {
		//TODO: If this is added to the related record, pass in the value
		$actions = array();
		$title = 'Access Online';

		$marcRecord = $this->getMarcRecord();
		/** @var File_MARC_Data_Field[] $linkFields */
		$linkFields = $marcRecord->getFields('856');
		$fileOrUrl = null;
		foreach($linkFields as $linkField){
			if ($linkField->getIndicator(1) == 4 && $linkField->getIndicator(2) == 0){
				$linkSubfield = $linkField->getSubfield('u');
				$fileOrUrl = $linkSubfield->getData();
				break;
			}
		}
		if ($fileOrUrl != null){
			$actions[] = array(
				'url' => $fileOrUrl,
				'title' => $title,
				'requireLogin' => false,
			);
		}

		return $actions;
	}

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();

		//Load table of contents
		$tableOfContents = $this->getTOC();
		$interface->assign('tableOfContents', $tableOfContents);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);
		//Other editions if applicable (only if we aren't the only record!)
		$relatedRecords = $this->getGroupedWorkDriver()->getRelatedRecords();
		if (count($relatedRecords) > 1){
			$interface->assign('relatedManifestations', $this->getGroupedWorkDriver()->getRelatedManifestations());
			$moreDetailsOptions['otherEditions'] = array(
				'label' => 'Other Editions',
				'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
				'hideByDefault' => false
			);
		}

		$notes = $this->getNotes();
		if (count($notes) > 0){
			$interface->assign('notes', $notes);
		}

		$moreDetailsOptions['moreDetails'] = array(
			'label' => 'More Details',
			'body' => $interface->fetch('Hoopla/view-more-details.tpl'),
		);
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = array(
			'label' => 'Subjects',
			'body' => $interface->fetch('Record/view-subjects.tpl'),
		);
		$moreDetailsOptions['citations'] = array(
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		);
		if ($interface->getVariable('showStaffView')){
			$moreDetailsOptions['staff'] = array(
				'label' => 'Staff View',
				'body' => $interface->fetch($this->getStaffView()),
			);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}
}