// Setup
// Jeedom configuration/API/Clef API Z-Wave
$apizwave = 'yourZwaveAPIKey';
// the node Id to perform the ping
$nodeId = 2;
// End Setup

$url = 'http://127.0.0.1:8083/node?node_id=' . $nodeId . '&type=action&action=testNode&apikey=' . $apizwave;
$contents = file_get_contents($url);
//$scenario->setLog('Contents :'.$contents);
$results = json_decode($contents);
$success = $results->state;
if ($success != 'ok') {
    $scenario->setLog('ZAPI TestNode return an error: ' . $results->result);
}
