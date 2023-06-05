<?PHP

/*  Input Formatting    */
function cleanInputs(&$input, &$resultstr, &$showpn){
    // do not accept strings with non alphanumeric characters
    if(!(preg_match('/[a-zA-Z]/', $input[0]) || preg_match('/\d/', $input[0]))){
        $input= "";
    }

    // process invalid query
    if(empty($input)){
        $showpn = false;
    }
    elseif(strlen($input) <= 5){
        $showpn = false;
        $input= "";
        $resultstr .= "please search a longer query";
    }
}

function addUnderscore(&$input){
    // create a duplicate with dots to also search
    return str_replace(" ", "_", $input);
}

/* Important functions */
function search($db, $rq, $pnum, $plen, $table, $searchItem, $order, ...$selection){
    // set cache size
    $pragmaStatement = $db->prepare("
PRAGMA cache_size = 16384;");
    $pragmaResult = $pragmaStatement->execute();

    // build select for query
    $qst = "SELECT ";
    $selectionLength = count($selection);
    for($i = 0; $i < $selectionLength-1; $i++)
        $qst .= $selection[$i] . ", ";

    // add rest of query
    $qst .= $selection[$selectionLength-1] . " FROM " . $table . " WHERE " . $searchItem . " LIKE :srd ORDER BY " . $order . " DESC ";

    // add page limit
    $qst .= "LIMIT ".$plen." OFFSET ".($pnum-1)*$plen.";";

    // search database
    $statement = $db->prepare($qst);
    $statement->bindValue(':srd', '%'.$rq.'%');
    $r = $statement->execute();

    return $r;
}

function countResults(&$r){
    $nr = 0;
    $r->reset();
    while($col = $r->fetchArray()){
        $nr++;
    }

    return $nr;
}

/*  PAGE FORMATTING     */
// creates the page entries
function parsePageEntries(&$res, &$resultstr, &$showpn, $searchreq, $pn, $pl, $titlecol, $linkcol, $datecol, $fscol, $catcol, $linkcol2){
    // count number of results
    $numrows = countResults($res);

    // no results
    if($numrows == 0){
        $showpn = false;
        $resultstr .= "No results found for ".htmlspecialchars($searchreq)." :(";
    }

    $i = ($pn-1)*$pl;
    $res->reset();
    while($col = $res->fetchArray()){
        $i ++;

        $title = $col[$titlecol];
        $link1 = formatLink1($col[$linkcol]);
        $date = formatDate($col[$datecol]);
        $size = formatFileSize($col[$fscol]);
        $cat = $col[$catcol];
        $link2 = formatLink2($col[$linkcol2]);

        $resultstr .= "<p>" . $i . ". " . $title . "<br />";
        $resultstr .= $link1."<br />";
        $resultstr .= "<em class='searchInfo'>Date: ".$date." | Size: ".$size." | Category: ".$cat." | ".$link2."</em></p>";
    }

    $resultstr .= "</div>";

    formatPageNumbers($resultstr, $searchreq, $showpn, $numrows, $pn, $pl);
}

// formats magnet link for entry
function formatLink1(&$lnk){
    $theLink = "https://".$lnk;
    return "<a href='".$theLink."'>".$theLink."</a>";
}

// formats date for entry
function formatDate(&$dt){
    $date = substr($dt, 0, 10);
    return $date;
}

// formats file size for entry
function formatFileSize(&$rawSize){
    $sizeFormat = array('B', 'KB', 'MB', 'GB', 'TB');
    $pow = floor(($rawSize ? log($rawSize):0) / log(1024));
    $pow = min($pow, count($sizeFormat)-1);
    $rawSize /= (1 << (10 * $pow));
    $size = round($rawSize, 4) . " " . $sizeFormat[$pow];

    return $size;
}

// formats IMDB link for entry
function formatLink2(&$lnk){
    $imdb = "<a class='searchInfo' href='https://".$lnk."' target='_blank'>Link 2</a>";
    return $imdb;
}

// formats the page numbers at the bottom of the page
function formatPageNumbers(&$resultstr, $searchreq, $showpn, $nr, $pn, $pl){
    $lastPage=false;
    $resultstr .= "</div>";

    if($nr < $pl-1){
        $lastPage=true;
    }

    if($showpn){
        $resultstr .= "Page: ".$pn;
    }
    if($pn > 1){
        $resultstr .= " <a href='?searchRequest=".$searchreq."&pageNumber=1'>First</a> |";
        $resultstr .= " <a href='?searchRequest=".$searchreq."&pageNumber=".($pn-1)."'>Prev</a> |";
    }
    if(!$lastPage){
        $resultstr .= " <a href='?searchRequest=".$searchreq."&pageNumber=".($pn+1)."'>Next</a>";
    }
}

/*  HANDLE OUTPUT RESULTS   */
// write output results
function writeOutputResults($output){
    // write output to file to display
    $outputHandler = fopen("./results.txt", "w") or die ("unable to open file");
    fwrite($outputHandler, $output);
    fclose($outputHandler);
}


?>
