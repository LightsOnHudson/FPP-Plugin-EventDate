#!/usr/bin/php
<?
//error_reporting(0);
//
//Version 1 for release
$pluginName ="EventDate";
$myPid = getmypid();

$messageQueue_Plugin = "MessageQueue";
$MESSAGE_QUEUE_PLUGIN_ENABLED=false;



$skipJSsettings = 1;
include_once("/opt/fpp/www/config.php");
include_once("/opt/fpp/www/common.php");
include_once("functions.inc.php");
include_once("commonFunctions.inc.php");



$logFile = $settings['logDirectory']."/".$pluginName.".log";

$messageQueuePluginPath = $pluginDirectory."/".$messageQueue_Plugin."/";

$messageQueueFile = urldecode(ReadSettingFromFile("MESSAGE_FILE",$messageQueue_Plugin));

if(file_exists($messageQueuePluginPath."functions.inc.php"))
{
	include $messageQueuePluginPath."functions.inc.php";
	$MESSAGE_QUEUE_PLUGIN_ENABLED=true;

} else {
	logEntry("Message Queue Plugin not installed, some features will be disabled");
}


$MATRIX_MESSAGE_PLUGIN_NAME = "MatrixMessage";
//page name to run the matrix code to output to matrix (remote or local);
$MATRIX_EXEC_PAGE_NAME = "matrix.php";

require ("lock.helper.php");

define('LOCK_DIR', '/tmp/');
define('LOCK_SUFFIX', $pluginName.'.lock');

$pluginConfigFile = $settings['configDirectory'] . "/plugin." .$pluginName;
if (file_exists($pluginConfigFile))
	$pluginSettings = parse_ini_file($pluginConfigFile);

	$logFile = $settings['logDirectory']."/".$pluginName.".log";
	$DEBUG=urldecode($pluginSettings['DEBUG']);
	


//THIS IS O COOL!
//set the variable names as necessary??? do we even need to do this???

foreach ($pluginSettings as $key => $value) {

	if($DEBUG) {
		logEntry("KEY: ".$key." = ".$value);
	}
	//	echo "Key: ".$key." " .$value."\n";

	${$key} = urldecode($value);

}



if(strtoupper($ENABLED) != "ON") {
	$REPLY_TEXT_PLUGIN_DISABLED = "We're sorry, the system is not accepting SMS at this time";
	
	logEntry("Plugin Status: DISABLED Please enable in Plugin Setup to use");
	lockHelper::unlock();
	exit(0);
}


//want to reply even if locked / disabled
if(($pid = lockHelper::lock()) === FALSE) {
	exit(0);

}

$strEventDate = $YEAR."-".$MONTH."-".$DAY." ".$HOUR.":".$MIN.":00";

logEntry( "event date: ".$strEventDate);

//$date1 = strtotime('2013-07-03 18:00:00');
$date1 = strtotime($strEventDate);

$date2 = time();
$subTime = $date1 - $date2;
//$subTime = $date2 - $date1;

$y = ($subTime/(60*60*24*365));
$d = ($subTime/(60*60*24))%365;
$h = ($subTime/(60*60))%24;
$m = ($subTime/60)%60;

logEntry( "Difference between ".date('Y-m-d H:i:s',$date1)." and ".date('Y-m-d H:i:s',$date2)." is:".$y." years ".$d." days ".$h." hours ".$m." minutes");
//echo $y." years\n";
//echo $d." days\n";
//echo $h." hours\n";
//echo $m." minutes\n";

$messageText = $PRE_TEXT;
if ((int) $y > 0){
	$messageText .= $y. " years ";
}
if ((int) $d > 0){
	$messageText .= $d. " days ";
}
if ((int) $h > 0){
	$messageText .= $h. " hours ";
}
if ((int) $m > 0){
	$messageText .= $m. " minutes ";
}

$messageText .= " ".$POST_TEXT. " ".$EVENT_NAME;

$messageText = preg_replace('!\s+!', ' ', $messageText);

logEntry("Adding message ".$messageText. " to message queue");
if($MESSAGE_QUEUE_PLUGIN_ENABLED) {
	addNewMessage($messageText,$pluginName,$EVENT_NAME);
} else {
	logEntry("MessageQueue plugin is not enabled/installed: Cannot add message: ".$messageText);
}

if($IMMEDIATE_OUTPUT != "ON") {
	logEntry("NOT immediately outputting to matrix");
} else {
	logEntry("IMMEDIATE OUTPUT ENABLED");
	logEntry("Matrix location: ".$MATRIX_LOCATION);
	logEntry("Matrix Exec page: ".$MATRIX_EXEC_PAGE_NAME);

//	if($MATRIX_LOCATION != "127.0.0.1") {
		$remoteCMD = "/usr/bin/curl -s --basic 'http://".$MATRIX_LOCATION."/plugin.php?plugin=".$MATRIX_MESSAGE_PLUGIN_NAME."&page=".$MATRIX_EXEC_PAGE_NAME."&nopage=1'";// > /dev/null";
		$curlURL = "http://".$MATRIX_LOCATION."/plugin.php?plugin=".$MATRIX_MESSAGE_PLUGIN_NAME."&page=".$MATRIX_EXEC_PAGE_NAME."&nopage=1";
		logEntry("REMOTE MATRIX TRIGGER: ".$remoteCMD);
			
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$curlURL);
		//curl_setopt($ch,CURLOPT_POST,count($fields));
		//curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'do_nothing');
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		//curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
		$result = curl_exec($ch);
		logEntry("Curl result: ".$result);// $result;
		curl_close ($ch);
		//	forkExec($remoteCMD);
		//	lockHelper::unlock();
		//	exit(0);
		//exec($remoteCMD);
	//} else {
	//	$IMMEDIATE_CMD = $settings['pluginDirectory']."/".$MATRIX_MESSAGE_PLUGIN_NAME."/matrix.php";
	//	logEntry("LOCAL command: ".$IMMEDIATE_CMD);
		//	$forkResult = fork($IMMEDIATE_CMD);
	//	exec($IMMEDIATE_CMD);
		//forkExec($IMMEDIATE_CMD);
		//lockHelper::unlock();
		//exit(0);
	//}
}

	lockHelper::unlock();
	exit(0);
	
	
?>
