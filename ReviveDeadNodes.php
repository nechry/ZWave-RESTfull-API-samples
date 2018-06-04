// Setup
// Jeedom configuration/API/Clef API Z-Wave
$apizwave = 'yourZwaveAPIKey';
// list of nodes to perform a check
$nodeIds = array(107, 162, 141);
// End Setup

foreach ($nodeIds as $nodeId) {
    if (getNodeFailed($nodeId, $apizwave, $scenario)) {
        $scenario->setLog('Do a ping nodeid ' . $nodeId);
        //try first a ping
        $url_ping = 'http://localhost:8083/node?node_id=' . $nodeId . '&type=action&action=testNode&apikey=' . $apizwave;
        $content = file_get_contents($url_ping);
        $results = json_decode($content, true);
        $success = $results["state"];
        if ($success != 'ok') {
            $scenario->setLog('ZAPI node testNode return an error: ' . $results["result"]);
        } else {
            //sleep for 3 seconds
            sleep(3);
            if (getNodeFailed($nodeId, $apizwave, $scenario)) {
                $scenario->setLog('Do a hasNodeFailed nodeid ' . $nodeId);
                // use special zwave command hasNodeFailed to test
                $url_hasNodeFailed = 'http://localhost:8083/node?node_id=' . $nodeId . '&type=action&action=hasNodeFailed&apikey=' . $apizwave;
                $content = file_get_contents($url_hasNodeFailed);
                $results = json_decode($content, true);
                $success = $results["state"];
                if ($success != 'ok') {
                    $scenario->setLog('ZAPI node hasNodeFailed return an error: ' . $results["result"]);
                } else {
                    //sleep for 3 seconds
                    sleep(3);
                    getNodeFailed($nodeId, $apizwave, $scenario);
                }
            }
        }
    }
}


function getNodeFailed($nodeId, $apizwave, $scenario){
    $url_health = 'http://localhost:8083/node?node_id=' . $nodeId . '&type=info&info=getHealth&apikey=' . $apizwave;
    $content = file_get_contents($url_health);
    //$scenario->setLog($content);
    $results = json_decode($content, true);
    $success = $results["state"];
    if ($success != 'ok') {
        $scenario->setLog('ZAPI node getHealth return an error: ' . $results["result"]);
        //I can confirm anything, we assume is not failed.
        return false;
    } else {
        if ($results["result"]["data"]["isFailed"]["value"]) {
            $scenario->setLog('nodeid ' . $nodeId . ' is failed');
        }
        return $results["result"]["data"]["isFailed"]["value"];
    }
}
