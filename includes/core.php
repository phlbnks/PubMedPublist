<?php
/**
 * Main functionality
 **/



/** Hook plugin's action and filters **/
//function pm_publist_init(){
//}
//add_action('plugins_loaded', 'pm_publist_init');*/


add_shortcode("recentpublications", "pm_publist_recent_pubs");
/*
*************Recently published papers shortcode********
*/
function pm_publist_recent_pubs($atts, $content = null) {
    global $show;
    global $total;
    global $layout;
    extract(shortcode_atts(array(
            "show" => '5',
            "extra" => '',
            "class" => '',
            "layout" => false
    ), $atts));

    $total = $show+$extra;

    $transient = 'pm_pubmedlist'.$show.$extra.$class.$layout;
    $pubs = get_transient( $transient );
    if ( false === $pubs ) {

        date_default_timezone_set('Europe/London');
        function lmbpubs(){
            //main function to process the data and produce the array of results
            function processpapers($xmlpapers) {
                //Create array to store values
                $allpapers = array();
                //traverse xml, find each PubMedArticle node
                foreach ($xmlpapers->PubmedArticle as $article) {
                    //First build authors
                    $authors = array();
                    foreach($article->MedlineCitation->Article->AuthorList->Author as $author){
                        $anauthor = array (
                            'last' => $author->LastName .', ',
                            'initials' => $author->Initials .'.',
                        );
                        array_push($authors, implode($anauthor));
                    }
                    $authorlist = implode(', ', $authors);

                    //Next set Sort Date usign relevant field for Electronic vs Print.
                    //TODO: Fall back dates are not checked for format...!
                    if ($article->MedlineCitation->Article->attributes() == "Electronic") {
                        //Electronic pubs
                        //Grab from "proper" field - ArticleDate - try fall back to Print date if missing.
                        if($article->MedlineCitation->Article->ArticleDate->Year) : $year = $article->MedlineCitation->Article->ArticleDate->Year; else : $year = $article->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year; endif;

                        if($article->MedlineCitation->Article->ArticleDate->Month) : $month = $article->MedlineCitation->Article->ArticleDate->Month; else : $month = $article->MedlineCitation->Article->Journal->JournalIssue->PubDate->Month; endif;

                        if($article->MedlineCitation->Article->ArticleDate->Day) : $day = $article->MedlineCitation->Article->ArticleDate->Day; else : $day = $article->MedlineCitation->Article->Journal->JournalIssue->PubDate->Day; endif;
                        //ArticleDate format = 2012 09 1(or 01)
                    } else {
                        //Print pubs
                        //Grab from "proper" field - PubDate - try fall back to Electronic date if missing.
                        if($article->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year) : $year = $article->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year; else : $year = $article->MedlineCitation->Article->ArticleDate->Year; endif;

                        if($article->MedlineCitation->Article->Journal->JournalIssue->PubDate->Month) : $month = $article->MedlineCitation->Article->Journal->JournalIssue->PubDate->Month; else : $month = $article->MedlineCitation->Article->ArticleDate->Month; endif;

                        if($article->MedlineCitation->Article->Journal->JournalIssue->PubDate->Day) : $day = $article->MedlineCitation->Article->Journal->JournalIssue->PubDate->Day; else : $day = $article->MedlineCitation->Article->ArticleDate->Day; endif;
                        //PubDate format = 2012 Sep 1(or 01)
                        // Convert to standard & consistent date format acceptible to strtotime.
                        //If no year then stick in 70s to "junk" it.
                        if(strlen($year) < 4){ $year = "1970"; };
                        //Most dates in PubDate are words so grab and convert to numeric
                        if(strlen($month) > 2){ $month = date( 'm', strtotime($month) ); };
                        //The odd day lacks a leading zero so fix that
                        if(strlen($day) < 2){ $day = '0'.$day; };
                    }

                    //Next Build Journal ref from parts.
                    $journalRef = '';
                    if($article->MedlineCitation->Article->Journal->JournalIssue->Volume) : $journalRef .= '<b>'.$article->MedlineCitation->Article->Journal->JournalIssue->Volume.'</b>'; endif;
                    if($article->MedlineCitation->Article->Journal->JournalIssue->Issue) : $journalRef .= '('.$article->MedlineCitation->Article->Journal->JournalIssue->Issue.')'; endif;
                    //MedlinePgn can exist but hold no data so check not empty.
                    if($article->MedlineCitation->Article->Pagination->MedlinePgn != '') : $journalRef .= ': '.$article->MedlineCitation->Article->Pagination->MedlinePgn; endif;
                    //Define papers as ahead of print if missing Journal Ref / listed as such.
                    //TODO: Check blank pagination always mean aheadofprint
                    if($journalRef == "" | $article->PubmedData->PublicationStatus == "aheadofprint"){
                        $journalRef .= " [Epub ahead of print]";
                    };

                    //create sub array containing the data for a single paper
                    $onepaper = array (
                        'title' => $article->MedlineCitation->Article->ArticleTitle,
                        'link' => $article->MedlineCitation->PMID,
                        'author' => $authorlist,
                        'journal' => $article->MedlineCitation->Article->Journal->ISOAbbreviation,
                        //'volume' => $article->MedlineCitation->Article->Journal->JournalIssue->Volume,
                        //'issue' => $article->MedlineCitation->Article->Journal->JournalIssue->Issue,
                        //'pages' => $article->MedlineCitation->Article->Pagination->MedlinePgn,
                        'journalRef' => $journalRef,
                        'sortdate' => $year.'-'.$month.'-'.$day,
                        'fulldate' => $day.'/'.$month.'/'.$year,
                        'year' => $year,
                        //'published' => $article->PubmedData->PublicationStatus,
                    );
                    //merge each $onepaper into $allpapers
                    array_push($allpapers, $onepaper);
                }

                //Sort array so in date order
                function cmp($a, $b) {
                    if (strtotime($a['sortdate']) == strtotime($b['sortdate'])) {
                        return 0;
                    }
                    return (strtotime($a['sortdate']) > strtotime($b['sortdate'])) ? -1 : 1; // > determines sort order
                }
                usort($allpapers, "cmp");

                //Concatinate long author fields
                function authorlength($checkme){
                    if (strlen($checkme) > 100) {
                        $splitauthors = explode(".,", $checkme);
                        $checkme = $splitauthors[0].'., et al.';
                        return $checkme;
                    } else {
                        return $checkme;
                    }
                }

                //Get globals from shortcode
                global $show;
                global $total;
                global $layout;

                if ($layout) {
                    //Do alternative layout.
                    //Build array of output <li>s - split into "show" and "total" arrays
                    $i = 1; $today = strtotime(date('Y-M-d'));
                    foreach ($allpapers as $paper) {
                        //until 20 rows have been used
                        if($i<=$show && (strtotime($paper['sortdate']) < $today) ){
                            $lmbnewsarray[0][] = '<li><b>'.authorlength($paper['author']).'</b> ('.date("jS F Y", strtotime($paper['sortdate'])).')<br/><a href="http://www.ncbi.nlm.nih.gov/pubmed/'.$paper['link'].'" target="_blank">'.$paper['title'].'</a><br/><i>'.$paper['journal'].'</i> '.$paper['journalRef'].'</li>';
                            $i++;
                        } elseif ($i<=$total && (strtotime($paper['sortdate']) < $today) ){
                            $lmbnewsarray[1][] = '<li><b>'.authorlength($paper['author']).'</b> ('.date("jS F Y", strtotime($paper['sortdate'])).')<br/><a href="http://www.ncbi.nlm.nih.gov/pubmed/'.$paper['link'].'" target="_blank">'.$paper['title'].'</a><br/><i>'.$paper['journal'].'</i> '.$paper['journalRef'].'</li>';
                            $i++;
                        }
                    }
                } else {
                    //Do default layout
                    //Build array of output <li>s - split into "show" and "total" arrays
                    $i = 1; $today = strtotime(date('Y-M-d'));
                    foreach ($allpapers as $paper) {
                        //until 20 rows have been used
                        if($i<=$show && (strtotime($paper['sortdate']) < $today) ){
                            $lmbnewsarray[0][] = '<li><a href="http://www.ncbi.nlm.nih.gov/pubmed/'.$paper['link'].'" target="_blank">'.$paper['title'].'</a><br/>'.authorlength($paper['author']).'<br/><b>'.$paper['journal'].'</b> '.$paper['journalRef'].'.  <span class="small">('.date("jS F Y", strtotime($paper['sortdate'])).')</span></li>';
                            $i++;
                        } elseif ($i<=$total && (strtotime($paper['sortdate']) < $today) ){
                            $lmbnewsarray[1][] = '<li><a href="http://www.ncbi.nlm.nih.gov/pubmed/'.$paper['link'].'" target="_blank">'.$paper['title'].'</a><br/>'.authorlength($paper['author']).'<br/><b>'.$paper['journal'].'</b> '.$paper['journalRef'].'.  <span class="small">('.date("jS F Y", strtotime($paper['sortdate'])).')</span></li>';
                            $i++;
                        }
                    }
                }
                return $lmbnewsarray;
            }

            $xmlCache = pm_publist_DIR.'pm_cache.xml';
            //TODO: Sort caching out. Remove age chack from pubmedrequest.php and ue just this???
            //Easier to get var from settings here.
            $xmlcache_time = 60*60; // 1 hour
            $timedif = @(time() - filemtime($xmlCache));
            ////check if cache is older that set above. if not then use
            if (file_exists($xmlCache) && $timedif < $xmlcache_time && filesize($xmlCache) > 2120) {
                //use cache providing it has a reasonable size
                $xmlpapers = simplexml_load_file($xmlCache);
                $processedpapers = processpapers($xmlpapers);
            }
            ////Otherwise create the page fresh
            else {
                //function to trigger PubMed request in background by requesting script then dropping the connection.
                function backgroundPost($url){
                    $parts=parse_url($url);

                    $fp = fsockopen($parts['host'],
                            isset($parts['port'])?$parts['port']:80,
                            $errno, $errstr, 30);

                    if (!$fp) {
                        return false;
                    } else {
                        $strings = get_option('pm_publist_settings');
                        $data = http_build_query($strings);

                        $out = "POST ".$parts['path']." HTTP/1.1\r\n";
                        $out.= "Host: ".$parts['host']."\r\n";
                        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
                        $out.= "Content-Length: ".strlen($data)."\r\n";
                        $out.= "Connection: Close\r\n\r\n";
                        fwrite($fp, $out);
                        fwrite($fp, $data);
                        fclose($fp);
                        return true;
                    }
                }
                //If the cache file is there, but old, display it for now - update the cache in the background
                if (file_exists($xmlCache)) {
                    $xmlpapers = simplexml_load_file($xmlCache);
                    $processedpapers = processpapers($xmlpapers);
                    backgroundPost(plugins_url( 'pm_pubmedrequest.php' , dirname(__FILE__) ));
                //otherwise if the cache file doesnt exist then generate it and display an error
                } else {
                    $processedpapers[0][] = '<li>There was a problem getting the data - please try refreshing.</li>';
                    backgroundPost(plugins_url( 'pm_pubmedrequest.php' , dirname(__FILE__) ));
                }
            }

            return $processedpapers;
        }
        $lmbpubs = lmbpubs();
        ////Create page
        $pubs = '<ul class="pm_publist '.$class.'">'.implode($lmbpubs[0]).'</ul>';
        global $show;
        global $total;
        if($show != $total) {
            //TODO: Add CSS / JS / Shortcode for this:
            //$pubs .= do_shortcode('[toggle title="More papers"]<ul class="'.$class.'>'.implode($lmbpubs[1]).'</ul>[/toggle]');
            $pubs .= '<ul class="pm_publist_more '.$class.'">'.implode($lmbpubs[1]).'</ul>';
        };

        //for next time write transient cache file.
        //but only if no error (basic check using length)
        //TODO: Use Options to set this.
        if (strlen($pubs) > 500){
            set_transient($transient, $pubs, 60*30);
        }
    }

    return $pubs;
}

?>
