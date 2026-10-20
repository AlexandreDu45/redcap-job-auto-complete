<?php
/** @var \STPH\addressAutoComplete\addressAutoComplete $module */

if ($_REQUEST['action'] == 'mapResults') {

    if(empty($_POST["source"]) || empty($_POST["results"])) {
        header("HTTP/1.1 400 Bad Request");
        die("results or source missing!");
    }
    $source = htmlentities($_POST["source"], ENT_QUOTES);
    $results = json_decode($_POST["results"]);

    if (!is_array($results)) {
        $results = [$results];
    }

    $module->mapResults($source, $results);
}

else if($_REQUEST['action'] == 'getConfigDescription') {

    if(empty($_POST["pid"]) || empty($_POST["source"])) {
        header("HTTP/1.1 400 Bad Request");
        die("pid or source missing!");
    }    

    $pid = htmlentities($_POST["pid"], ENT_QUOTES);
    $source = htmlentities($_POST["source"], ENT_QUOTES);
    
    $module->getConfigDescription($pid,$source);
}


else if ($_REQUEST['action'] == 'soccerSearch') {

    if (empty($_POST["jobtitle"])) {
        header("HTTP/1.1 400 Bad Request");
        die("term missing");
    }

    $jobtitle = $_POST["jobtitle"];
    $jobtask  = $_POST["jobtask"] ?? "";

    $query = http_build_query([
        "JobTitle" => $jobtitle,
        "JobTask"  => $jobtask
    ]);

    $url = "http://soccerapi:8080/soccernet?" . $query;
    

    $opts = [
        'http' => [
            'method' => 'GET'
        ]
    ];

    $context = stream_context_create($opts);

    $response = file_get_contents(
        $url,
        false,
        $context
    );

    if ($response === false) {
        header("HTTP/1.1 502 Bad Gateway");
        die("SOCcer API request failed");
    }

    header('Content-Type: application/json');

    echo $response;
    exit;
}

else {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json; charset=UTF-8');    
    die("The action does not exist.");
}