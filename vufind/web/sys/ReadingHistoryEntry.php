<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class ReadingHistoryEntry extends DB_DataObject 
{
	public $__table = 'user_reading_history_work';   // table name
	public $id;
	public $userId;
	public $groupedWorkPermanentId;
	public $source;
	public $sourceId;
	public $title;
	public $author;
	public $format;
	public $checkOutDate;
	public $checkInDate;
	public $deleted;

	/* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('ReadingHistoryEntry',$k,$v); }
    
	function keys() {
	    return array('id');
 	}
}
