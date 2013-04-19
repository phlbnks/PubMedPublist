<?php
////If Cache is good - use it
//define some variables.
$cache_time = 60*60*2; // 2 hours
$cache_file = dirname(__FILE__).'/pm_cache.xml';
$timedif = @(time() - filemtime($cache_file));
//check if cache is older that set above. if not then:
if (file_exists($cache_file) && $timedif < $cache_time && filesize($cache_file) > 200) {
	//use cache providing it has a reasonable size
}
////Otherwise create the page fresh
else {
		$nodes = array();
		//TODO: Make multi string compatible. Work out how to test how many then do appropriate for that many.
		if(isset($_POST["0"])) {
			$search1 = 'http://www.ncbi.nlm.nih.gov/pubmed?term='.$_POST["0"].'&report=xml';
			array_push($nodes, $search1);
		}
		if(isset($_POST["1"])) {
			$search2 = 'http://www.ncbi.nlm.nih.gov/pubmed?term='.$_POST["1"].'&report=xml';
			array_push($nodes, $search2);
		}
		if(isset($_POST["2"])) {
			$search3 = 'http://www.ncbi.nlm.nih.gov/pubmed?term='.$_POST["2"].'&report=xml';
			array_push($nodes, $search3);
		}
		if(isset($_POST["3"])) {
			$search4 = 'http://www.ncbi.nlm.nih.gov/pubmed?term='.$_POST["3"].'&report=xml';
			array_push($nodes, $search4);
		}
		if(isset($_POST["4"])) {
			$search5 = 'http://www.ncbi.nlm.nih.gov/pubmed?term='.$_POST["4"].'&report=xml';
			array_push($nodes, $search5);
		}
		if(isset($_POST["5"])) {
			$search6 = 'http://www.ncbi.nlm.nih.gov/pubmed?term='.$_POST["5"].'&report=xml';
			array_push($nodes, $search6);
		}


		//get PubMed data as XML using parallel cURL requests for speed.
		////$nodes = array($search1, $search2, $search3, $search4, $search5, $search6);
		$node_count = count($nodes);
		$curl_arr = array();
		$master = curl_multi_init();
		for($i = 0; $i < $node_count; $i++)	{
			$url =$nodes[$i];
			$curl_arr[$i] = curl_init($url);
			curl_setopt($curl_arr[$i], CURLOPT_RETURNTRANSFER, true);
			curl_multi_add_handle($master, $curl_arr[$i]);
		}
		do {
		    curl_multi_exec($master,$running);
		} while($running > 0);

		for($i = 0; $i < $node_count; $i++) {
			$num = $i+1;
			${"search$num"} = curl_multi_getcontent  ( $curl_arr[$i]  );
		}



		//function to clean PubMed data so it is valid XML.
		function cleanPubmed($dirtyPubmed) {
			$dirtyPubmed = preg_replace('/<!(.*?)<pre>/s',"<Document>", $dirtyPubmed);
			$dirtyPubmed = preg_replace('/<\/pre>/s',"</Document>", $dirtyPubmed);
			$dirtyPubmed = preg_replace('/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/',"\n", $dirtyPubmed);
			$cleanPubmed = htmlspecialchars_decode($dirtyPubmed);
			return $cleanPubmed;
		}
		//function to check 2 xml inputs for duplicate nodes
		function dedupeXML($xml1, $xml2) {
			$query = array();
			foreach ($xml1->PubmedArticle as $paper) {
		    	$query[] = sprintf('(MedlineCitation/PMID != %s)',$paper->MedlineCitation->PMID);
			}
			$query = implode('and', $query);

			$xmlClean = '<Document>';
			foreach ($xml2->xpath(sprintf('PubmedArticle[%s]', $query)) as $paper) {
		    	$xmlClean .= $paper->asXML();
			}
			$xmlClean .= '</Document>';
			$xmlClean = new SimpleXMLElement($xmlClean);
			return $xmlClean;
		}
		//function to merge 2 xml inputs
		function mergeXML (SimpleXMLElement &$xml1, SimpleXMLElement $xml2) {
			// convert SimpleXML objects into DOM ones
			$dom1 = new DomDocument();
			$dom2 = new DomDocument();
			$dom1->loadXML($xml1->asXML());
			$dom2->loadXML($xml2->asXML());
			// pull all child elements of second XML
			$xpath = new domXPath($dom2);
			$xpathQuery = $xpath->query('/*/*');
			for ($i = 0; $i < $xpathQuery->length; $i++) {
				// and pump them into first one
				$dom1->documentElement->appendChild(
				$dom1->importNode($xpathQuery->item($i), true));
			}
			$xml = simplexml_import_dom($dom1);
			return $xml;
		}


		//Loop through set nodes and process them
		for($i = 0; $i < $node_count; $i++) {
			$num = $i+1;
			//Clean search data
			${"search$num"} = cleanPubmed(${"search$num"});
			//turn search data into SimpleXML Elements
			${"xml$num"} = new SimpleXMLElement(${"search$num"});
		}

		$output = $xml1;
		if ($node_count > 1) {
			for($i = 0; $i < $node_count-1; $i++) {
				$num = $i+2;
				error_log($num);

				${"xml$num.clean"} = dedupeXML($output,${"xml$num"});
				$output = mergeXML($output,${"xml$num.clean"});
			}
		}

	//for next time
	//write cache file.
	$filenum=fopen($cache_file,"w");
	fwrite($filenum,$output->asXML());
	fclose($filenum);
}
?>
