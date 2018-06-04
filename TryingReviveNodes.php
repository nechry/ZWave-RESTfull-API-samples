// Setup
// Jeedom configuration/API/Clef API Z-Wave
$apizwave = 'yourZwaveAPIKey';
// End Setup

$url_health = 'http://localhost:8083/network?type=info&info=getHealth&apikey=' . $apizwave;
$content = (file_get_contents($url_health));
//$scenario->setLog($content);
$results = json_decode($content, true);
$success = $results["state"];
if ($success != 'ok') {
    $scenario->setLog('ZAPI network getHealth return an error: ' . $results["result"]);
} else {
    // get the full node list
    $devices = $results["result"]["devices"];
    $node_errors = array();
    foreach ($devices as $node_id => $node_values) {
        $isFailed = $node_values["data"]["isFailed"]["value"];
        // device can be disabled from jeedom
        $enabled = $node_values["data"]["is_enable"]["value"];
        if ($enabled & $isFailed) {
            if (count($node_errors) == 0) {
                $scenario->setLog('****** These nodes are presumed dead ******');
            }
            // get the name of the device
            $node_name = $node_values["data"]["description"]["name"];
            // add a log entry
            $scenario->setLog('NodeId ' . $node_id . ' ' . $node_name);
            // add nodeId to the node list
            $node_errors[] = $node_id;
        }
    }
    if (count($node_errors) != 0) {
        $scenario->setLog('*******************************************');
    }
    // save nodes list for external processing
    $scenario->setData("ZWave_Nodes_Death", implode(',', $node_errors));
}