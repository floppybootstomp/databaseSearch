<?PHP
    include "databaseSearch.php";

    // clear output
    writeOutputResults("");

    if($_SERVER["REQUEST_METHOD"] == "GET")
    {
        $database;
        $resultString = "<div>";
        $searchRequest = "";
        $searchRequestUnderscore = "";
        
        $pageNumber = 1;
        $pageLength = 25;
        $showPageNumber = true;
        $numRows = 0;

        // set correct page number
        if(!empty($_GET['pageNumber'])){
            $pageNumber = $_GET['pageNumber'];
        }
        else{
            $pageNumber = 1;
        }

        $searchRequest = $_GET['searchRequest'];
        // process search query string
        cleanInputs($searchRequest, $resultString, $showPageNumber);
        $searchRequestUnderscore = addUnderscore($searchRequest);

        // query database for search
        if(!empty($searchRequestUnderscore)){
            $database = new SQLite3('THE_DATABASE.sqlite') or die ("unable to open database");
            $results = search($database, $searchRequestUnderscore, $pageNumber, $pageLength, "table", "title", "date", "title", "date", "size", "urlOne", "category", "urlTwo");

            if(!empty($results)){
                // parse the returned entries to html and add page numbers
                parsePageEntries($results, $resultString, $showPageNumber, $searchRequest, $pageNumber, $pageLength, "title", "urlOne", "date", "size", "category", "urlTwo");
            }
            $database->close();
        }

        // write output to file to display
        writeOutputResults($resultString);
    }
?>

<html lang="en">

<head>
    <title>Da Database Search</title>
    <link rel='stylesheet' type='text/css' href='style.css'>
    <link charset='utf-8'>
</head>

<body>

<form name="searchForm" method="get">
Search: <input name="searchRequest" type="text"><input type="submit" value="Go!">
<?PHP include "./results.txt" ?>
</form>

</body>
</html>
