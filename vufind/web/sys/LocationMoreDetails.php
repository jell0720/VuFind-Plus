<?php
/**
 * Allows configuration of More Details for full record display
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/12/14
 * Time: 8:34 AM
 */

class LocationMoreDetails extends DB_DataObject{
	public $__table = 'location_more_details';
	public $id;
	public $locationId;
	public $source;
	public $collapseByDefault;
	public $weight;

	static function getObjectStructure(){
		//Load Libraries for lookup values
		require_once ROOT_DIR . '/RecordDrivers/Interface.php';
		$validSources = RecordInterface::getValidMoreDetailsSources();
		$structure = array(
				'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'),
				'source' => array('property'=>'source', 'type'=>'enum', 'label'=>'Source', 'values' => $validSources, 'description'=>'The source of the data to display'),
				'collapseByDefault' => array('property'=>'collapseByDefault', 'type'=>'checkbox', 'label'=>'Collapse By Default', 'description'=>'Whether or not the section should be collapsed by default', 'default' => true),
				'weight' => array('property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines how lists are sorted within the widget.  Lower weights are displayed to the left of the screen.', 'required'=> true),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}

	function getEditLink(){
		return '';
	}
}