// Setup
// Jeedom configuration/API/Clef API Z-Wave
$apizwave = 'yourZwaveAPIKey';
// a mimimum report date tolerate
$minimum_date = strtotime("-45 day", time());
// End Setup

// call the network health endpoint
$url_health = 'http://localhost:8083/network?type=info&info=getHealth&apikey=' . $apizwave;
$content = file_get_contents($url_health);
//$scenario->setLog($content);
// get result as json
$results = json_decode($content, true);
$success = $results["state"];
if ($success != 'ok') {
    $scenario->setLog('ZAPI network getHealth return an error: ' . $results["result"]);
} else {
    // get the full node list
    $devices = $results["result"]["devices"];
    $node_errors = array();
    foreach ($devices as $node_id => $node_values) {
        // listening devices work on sector
        $isListening = $node_values["data"]["isListening"]["value"];
        // device can be disabled from jeedom
        $enabled = $node_values["data"]["is_enable"]["value"];
        // test only if node is enable and is battery powered
        if ($enabled & $isListening == 0) {
            $can_wake_up = $node_values["data"]["can_wake_up"]["value"];
            // check if device can wakeup
            if ($can_wake_up) {
                // get the last battery report date
                $last_battery_report_date = $node_values["instances"]["1"]["commandClasses"]["128"]["data"]["0"]["updateTime"];
                // check if the report occure after the minimum date allowed
                if ($last_battery_report_date < $minimum_date) {
                    if (count($node_errors) == 0) {
                        $scenario->setLog('******* Battery level not updated *******');
                    }
                    // get the name of the device
                    $node_name = $node_values["data"]["description"]["name"];
                    // add a log entry
                    $scenario->setLog('NodeId ' . $node_id . ' ' . $node_name . ' ' . gmdate("d.m.Y", $last_battery_report_date));
                    // add nodeId to the node list
                    $node_errors[] = $node_id;
                    // then we ask for a manuel refresh of the battery level
                    $url_refresh = 'http://localhost:8083/node?type=refreshData&node_id=' . $node_id . '&instance_id=1&cc_id=128&index=0&apikey=' . $apizwave;
                    $content = file_get_contents($url_refresh);
                    $results = json_decode($content, true);
                    $success = $results["state"];
                    if ($success != 'ok') {
                        $scenario->setLog('ZAPI node refreshData return an error: ' . $results["result"]);
                    } else {
                        $scenario->setLog('   -> Request for updating the battery level');
                    }
                }
            }
        }
    }
    if (count($node_errors) != 0) {
        $scenario->setLog('****** Find ' . count($node_errors) . ' nodes ******');
        $scenario->setLog('*****************************************');
    }
    //save nodes list for external processing
    $scenario->setData("ZWave_Nodes_Battery_Report", implode(',', $node_errors));
}