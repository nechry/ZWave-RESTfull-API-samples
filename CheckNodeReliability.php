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
    $now = time();
    // check the delta
    $delta = $now - $receiveTime;
    $timeout = $delta / 60 % 60;
    $scenario->setLog('Last ping delta :' . $timeout);
    // check if notification has occur more the 1 minute ago
    if ($timeout > 1) {
        // add log entry
        $scenario->setLog('Notification stop working :' . $timeout);
        // use a notification command action to send the warning message
        $cmd = cmd::byString('#[Notifications][Telegram Bot][Tous]#');
        if (is_object($cmd)) {
            $option = array('title' => 'A ZWave node no longer seems to respond', 'message' => 'No response received after node test after ' . $timeout . ' minutes');
            $cmd->execCmd($option);
        }else {
            $scenario->setLog('Error: Notification Command Was Not Found');
        }
    }
}