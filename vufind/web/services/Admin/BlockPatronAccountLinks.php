<?php

/**
 * Pika
 *
 * Author: Pascal Brammeier
 * Date: 7/30/2015
 *
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Administration/BlockPatronAccountLink.php'; // Database object
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_BlockPatronAccountLinks extends ObjectEditor
{

	function getAllowableRoles()
	{
		return array('opacAdmin', 'libraryAdmin', 'libraryManager', 'locationManager');
	}

	/**
	 * The class name of the object which is being edited
	 */
	function getObjectType()
	{
		return 'BlockPatronAccountLink';
	}

	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	function getToolName()
	{
		return 'BlockPatronAccountLinks';
	}

	/**
	 * The title of the page to be displayed
	 */
	function getPageTitle()
	{
		return 'Block Patron Account Links';
	}

	/**
	 * Load all objects into an array keyed by the primary key
	 */
	function getAllObjects()
	{
		$object = new BlockPatronAccountLink();
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	/**
	 * Define the properties which are editable for the object
	 * as well as how they should be treated while editing, and a description for the property
	 */
	function getObjectStructure()
	{
		return BlockPatronAccountLink::getObjectStructure();
	}

	/**
	 * The name of the column which defines this as unique
	 */
	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	/**
	 * The id of the column which serves to join other columns
	 */
	function getIdKeyColumn()
	{
		return 'id';
	}

	function getInstructions(){
		return '<p>To block a patron from viewing the information of another patron by linking accounts:
 		<ul>
 		<li>First enter the barcode of the user you want to prevent from seeing the other account as the <b>Primary Account Barcode</b></li>
 		<li>Next enter the barcode of the user you want to prevent from seen by the other account as the <b>Account Barcode to Block Links to</b></li>
 		<li>If the user should not be able to see any other accounts, check <b>Block Linking to All </b></li>
 		<li>Now select a Save Changes button</li>
 		</ul>
 		</p>
 		<p>
 		Blocking a patron from linking accounts will not prevent a user from manually logging in to other accounts.
 		If you suspect that someone has been accessing other accounts incorrectly, you should issue new cards or change PINs for the accounts they have accessed in addition to blocking them.
		</p>';
	}
}