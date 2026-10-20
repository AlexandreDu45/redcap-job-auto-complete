<?php
/** @var \STPH\addressAutoComplete\addressAutoComplete $module */

use \ExternalModules\ExternalModules;

if (
    isset($_REQUEST['action']) &&
    $_REQUEST['action'] === 'soccerSearch'
) {

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

//  Validate Request Method
$request_method = $_SERVER['REQUEST_METHOD'];
if( $request_method !== "POST") {
    header("HTTP/1.1 400 Bad Request");
    die("not a post request");
}

//  Check if empty
if(empty($_POST["session_id"]) || empty($_POST["survey_hash"])) {
    header("HTTP/1.1 401 Unauthorized");
    die("no session / survey hash");
}

//  Sanitize survey_hash and survey session
$session_id = htmlentities($_POST["session_id"], ENT_QUOTES);
$survey_hash = htmlentities($_POST["survey_hash"], ENT_QUOTES); //  htmlentities($_GET["s"], ENT_QUOTES);

//  dummy data
//$session_id = "njfions9hgssja7bfbt6u1n256"; //  comes from session_id() : $_POST["session_id"], ENT_QUOTES)
//$survey_hash = "34TJXJNCEWFX3CHW";   //  comes from $_GET["s"] : $_GET["s"], ENT_QUOTES)

//  Checks if sessions exists and is not expired
$sql = "select 1 from redcap_sessions where session_id = ? and session_expiration >= ? limit 1";
$result = $module->query($sql, [$session_id, date("Y-m-d H:i:s")]);
if(db_num_rows($result) == 0) {
    header("HTTP/1.1 401 Unauthorized");
    die("session not existing / expired");
}

//  Retrieve available pids for module
$available_pids = [];
$projects = ExternalModules::getEnabledProjects(ExternalModules::getPrefix());
while($project = $projects->fetch_assoc()){
    $available_pids[] = $project['project_id'];
}
//  Retrieve pid for current survey_hash
$survey_pid = "";
$sql = "select project_id from redcap_surveys where survey_id = (select survey_id from redcap_surveys_participants where hash = ?)";
$q = $module->query($sql, [$survey_hash]);
$result = db_fetch_assoc($q);
$survey_pid = $result["project_id"];

//  Checks if survey associated project_id is available for module
if(in_array($survey_pid, $available_pids) == false) {
    header("HTTP/1.1 401 Unauthorized");
    die("invalid survey hash");
}

//  Validate Request Body
if(empty($_POST["source"]) || empty($_POST["results"])) {
    header("HTTP/1.1 400 Bad Request");
    die("results or source missing!");
}

//  Sanitize Request Body
$source = htmlentities($_POST["source"], ENT_QUOTES);
$results = json_decode($_POST["results"]);

//  Map results from gateway
//  passing pid through
try {
    $module->mapResultsFromGateway($source, $results, $survey_pid);
    exit();
} catch (\Throwable $th) {
    header("HTTP/1.1 400 Bad Request");
    die("Error during mapping of results: ".$th->getMessage());
}