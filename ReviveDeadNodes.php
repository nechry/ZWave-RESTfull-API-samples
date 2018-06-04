// Setup
// Jeedom configuration/API/Clef API Z-Wave
$apizwave = 'yourZwaveAPIKey';
// the node Id to perform the ping
$nodeIds = array(2, 5, 31);
// End Setup

foreach (nodeIds as &nodeId) {    
    if (getNodeFailed(&nodeId)) {
        //try first a ping
        $url_ping = 'http://localhost:8083/node?node_id=' . $nodeId . '&type=action&action=testNode&apikey=' . $apizwave;
        $content = file_get_contents($url_ping);            
        $results = json_decode($content, true);
        $success = $results["state"];
        if ($success != 'ok') {
           $scenario->setLog('ZAPI node testNode return an error: ' . $results["result"]);
        }
        else{
          if (getNodeFailed(&nodeId)) {
            //sleep for 3 seconds
            sleep(3);          
            // use special zwave command hasNodeFailed to test
            $url_hasNodeFailed = 'http://localhost:8083/node?node_id=' . $nodeId . '&type=action&action=hasNodeFailed&apikey=' . $apizwave;
            $content = file_get_contents($url_hasNodeFailed);            
            $results = json_decode($content, true);
            $success = $results["state"];
            if ($success != 'ok') {
               $scenario->setLog('ZAPI node hasNodeFailed return an error: ' . $results["result"]);
            }
          }
       }
   }
}


function getNodeFailed($nodeId) {
    $url_health = 'http://localhost:8083/node?node_id=' . $nodeId . '&type=info&info=getHealth&apikey=' . $apizwave;
    $content = (file_get_contents($url_health));
    //$scenario->setLog($content);
    $results = json_decode($content, true);
    $success = $results["state"];
    if ($success != 'ok') {
        $scenario->setLog('ZAPI node getHealth return an error: ' . $results["result"]);
        //I can confirm anything, we assume is not failed.
        return false;
    } else {
        return $results["data"]["isFailed"]["value"];
    }
}
