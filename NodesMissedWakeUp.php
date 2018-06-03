<?php
// Setup
// Jeedom configuration/API/Clef API Z-Wave
$apizwave = 'yourZwaveAPIKey';
// End Setup

$time_now = time();
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
        // listening devices work on sector
        $isListening = $node_values["data"]["isListening"]["value"];
        // device can be disabled from jeedom
        $enabled = $node_values["data"]["is_enable"]["value"];
        // test only if node is enable and is battery powered
        if ($enabled & $isListening == 0) {
            // get the wake up interval
            $wakeup_interval = $node_values["data"]["wakeup_interval"]["value"];
            if ($wakeup_interval == 0) {
                // this device never wakeup by itself, continue
                continue;
            }
            // check last notification received for this node
            $next_wakeup = $node_values["data"]["wakeup_interval"]["next_wakeup"];
            // check if node didn't wakeup as expected.
            if ($next_wakeup < $time_now) {
                // special case if the device is currently mark as awake
                $isAwake = $node_values["data"]["isAwake"]["value"];
                if ($isAwake) {
                    $last_notification = $node_values["last_notification"]["receiveTime"]["value"];
                    // check if the node has been awake for more than 5 minutes
                    if ($last_notification + 300 < $time_now) {
                        // this node seems awake for too long, we're going to ping
                        $url = 'http://localhost:8083/node?node_id=' . $node_id . '&type=action&action=testNode&apikey=' . $apizwave;
                        file_get_contents($url);
                        continue;
                    }
                }
                if (count($node_errors) == 0) {
                    $scenario->setLog('****** Battery modules that have not woken up as expected ******');
                }
                // get the name of the device
                $node_name = $node_values["data"]["description"]["name"];
                // add a log entry
                $scenario->setLog('NodeId ' . $node_id . ' ' . $node_name);
                // add nodeId to the node list
                $node_errors[] = $node_id;
            }
        }
    }
    if (count($node_errors) != 0) {
        $scenario->setLog('****************************************************************');
    }
    // save nodes list for external processing
    $scenario->setData("ZWave_Nodes_Wakeup_Error", implode(',', $node_errors));
}