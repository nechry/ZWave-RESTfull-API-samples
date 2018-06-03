// Setup
// Jeedom configuration/API/Clef API Z-Wave
$apizwave = 'yourZwaveAPIKey';
// the node Id to perform the ping
$nodeId = 2;
// End Setup

$url = 'http://127.0.0.1:8083/node?node_id=' . $nodeId . '&type=info&info=getHealth&apikey=' . $apizwave;
$contents = utf8_encode(file_get_contents($url));
//$scenario->setLog('Contents :' . $contents);
$results = json_decode($contents);
$success = $results->state;
if ($success != 'ok') {
    $scenario->setLog('ZAPI network getHealth return an error: ' . $results["result"]);
} else {
    // read last notification attributes info
    $receiveTime = $results->result->last_notification->receiveTime;
    $notificationDescription = $results->result->last_notification->description;
    $scenario->setLog('Notification receive time :' . date("Y-m-j H:i:s", $receiveTime));
    $scenario->setLog('             description :' . $notificationDescription);
    // init title and message to empty
    $title = 'A ZWave node no longer seems to respond';
    $message = '';
    // check if node is presume dead
    if ($notificationDescription == 'Dead') {
        $message = 'The Z Wave controller marked the Node Id ' . $nodeId . ' as presumed dead';
    } else {
        $now = time();
        // check the delta
        $delta = $now - $receiveTime;
        $scenario->setLog('Last ping delta :' . $delta . 'sec.');
        // check if notification has occur more the 1 minute ago
        if ($timeout > 60) {
            // use a notification command action to send the warning message
            $message = 'No response received after node test after ' . $delta . ' seconds';
        }
    }
    if ($message != '') {
        // add log entry
        $scenario->setLog($message);
        $cmd = cmd::byString('#[Notifications][Telegram Bot][Tous]#');
        if (is_object($cmd)) {
            $option = array('title' => $title, 'message' => $message);
            $cmd->execCmd($option);
        } else {
            $scenario->setLog('Error: the notification command did not exist');
        }
    }
}