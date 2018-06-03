// time of the testNode command
$send_time = $scenario->getData("ZAPI_NodePingTime");
$scenario->removeData(ZAPI_NodePingTime);
if (is_object($send_time)== false) {
    $send_time = strtotime("-" . $timeout . " second", time());
}
$url = 'http://127.0.0.1:8083/node?node_id=' . $nodeId . '&type=info&info=getHealth&apikey=' . $apizwave;
$contents = utf8_encode(file_get_contents($url));
//$scenario->setLog('Contents :' . $contents);
$results = json_decode($contents);
$success = $results->state;
if ($success != 'ok') {
    $scenario->setLog('ZAPI node getHealth return an error: ' . $results["result"]);
} else {
    // read last notification attributes info
    $receiveTime = $results->result->last_notification->receiveTime;
    $description = $results->result->last_notification->description;
    $scenario->setLog('Receive ' . $description . ' notification at time :' . date("Y-m-j H:i:s", $receiveTime));
    // init message to empty
    $message = '';
    // check if node is presume dead
    if ($description == 'Dead') {
        $message = 'The Z Wave controller marked the Node Id ' . $nodeId . ' as presumed dead';
    } else {
        // check the delta
        $delta = $receiveTime - $send_time;
        $scenario->setLog('Recive a echo in :' . $delta . ' seconds.');
        // check if notification has occur more the 1 minute ago
        if ($delta > $timeout) {
            // use a notification command action to send the warning message
            $message = 'No response received after node test after ' . $delta . ' seconds';
        }
    }
    if ($message != '') {
        // add log entry
        $scenario->setLog($message);
        $cmd = cmd::byString('#[Notifications][Telegram Bot][Tous]#');
        if (is_object($cmd)) {
            $option = array('title' => 'A ZWave node no longer seems to respond', 'message' => $message);
            $cmd->execCmd($option);
        } else {
            $scenario->setLog('Error: the notification command did not exist');
        }
    }
}
