<?php
/**
 * Handles searching DPLA and returning results
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/9/15
 * Time: 3:09 PM
 */

class DPLA {
	public function getDPLAResults($searchTerm){
		global $configArray;
		$results = array();
		if ($configArray['DPLA']['enabled']){
			$queryUrl = "http://api.dp.la/v2/items?api_key={$configArray['DPLA']['apiKey']}&page_size=5&q=" . urlencode($searchTerm);

			$responseRaw = file_get_contents($queryUrl);
			$responseData = json_decode($responseRaw);

			//Extract, title, author, source, and the thumbnail

			foreach($responseData->docs as $curDoc){
				$curResult = array();
				$curResult['id'] = @$this->getDataForNode($curDoc->id);
				$curResult['link'] = @$this->getDataForNode($curDoc->isShownAt);
				$curResult['object'] = @$this->getDataForNode($curDoc->object);
				$curResult['image'] = @$this->getDataForNode($curDoc->object);
				$curResult['title'] = @$this->getDataForNode($curDoc->sourceResource->title);
				$curResult['label'] = @$this->getDataForNode($curDoc->sourceResource->title);
				$curResult['description'] = @$this->getDataForNode($curDoc->sourceResource->description);
				$results[] = $curResult;
			}
		}
		return $results;
	}

	public function getDataForNode($node){
		if (empty($node)){
			return "";
		}else if (is_array($node)){
			return $node[0];
		}else{
			return $node;
		}
	}


	public function formatResults($results) {
		$formattedResults = "";
		if (count($results) > 0){
			global $interface;
			$interface->assign('searchResults', $results);
			$formattedResults = $interface->fetch('Search/dplaResults.tpl');
		}
		return $formattedResults;
	}
} 