// Setup
// Jeedom configuration/API/Clef API Z-Wave
$apizwave = 'yourZwaveAPIKey';
// End Setup

// call the network health endpoint
$url_health = 'http://localhost:8083/controller?type=action&action=healNetwork&apikey=' . $apizwave;
$content = file_get_contents($url_health);
//$scenario->setLog($content);
// get result as json
$results = json_decode($content, true);
$success = $results["state"];
if ($success != 'ok') {
    $scenario->setLog('ZAPI network getHealth return an error: ' . $results["result"]);
}