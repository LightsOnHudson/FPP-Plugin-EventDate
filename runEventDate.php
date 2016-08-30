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
include_once("profanity.inc.php");

// this line loads the library
//require('Twilio/Services/Twilio.php');
require ('Twilio/autoload.php');



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

require ("lock.helper.php");

define('LOCK_DIR', '/tmp/');
define('LOCK_SUFFIX', $pluginName.'.lock');

$pluginConfigFile = $settings['configDirectory'] . "/plugin." .$pluginName;
if (file_exists($pluginConfigFile))
	$pluginSettings = parse_ini_file($pluginConfigFile);

	$logFile = $settings['logDirectory']."/".$pluginName.".log";
	$DEBUG=urldecode($pluginSettings['DEBUG']);
	

	$MATRIX_MESSAGE_PLUGIN_NAME = "MatrixMessage";
	//page name to run the matrix code to output to matrix (remote or local);
	$MATRIX_EXEC_PAGE_NAME = "matrix.php";

	$PLAYLIST_NAME = urldecode($pluginSettings['PLAYLIST_NAME']);
	$WHITELIST_NUMBERS = urldecode($pluginSettings['WHITELIST_NUMBERS']);
	$CONTROL_NUMBERS = urldecode($pluginSettings['CONTROL_NUMBERS']);
	$REPLY_TEXT = urldecode($pluginSettings['REPLY_TEXT']);
	$VALID_COMMANDS = urldecode($pluginSettings['VALID_COMMANDS']);
	$IMMEDIATE_OUTPUT = urldecode($pluginSettings['IMMEDIATE_OUTPUT']);
	$MATRIX_LOCATION = urldecode($pluginSettings['MATRIX_LOCATION']);
	$API_KEY = urldecode($pluginSettings['API_KEY']);
	$API_USER_ID = urldecode($pluginSettings['API_USER_ID']);
	$PROFANITY_ENGINE = urldecode($pluginSettings['PROFANITY_ENGINE']);

	$TSMS_account_sid = urldecode($pluginSettings['TSMS_ACCOUNT_SID']);
	$TSMS_auth_token = urldecode($pluginSettings['TSMS_AUTH_TOKEN']);
	$TSMS_phoneNumber = urldecode($pluginSettings['TSMS_PHONE_NUMBER']);
	
	$playCommands = urldecode($pluginSettings['PLAY_COMMANDS']);
	$stopCommands = urldecode($pluginSettings['STOP_COMMANDS']);
	$repeatCommands = urldecode($pluginSettings['REPEAT_COMMANDS']);
	$statusCommands = urldecode($pluginSettings['STATUS_COMMANDS']);
	
	$REMOTE_FPP_ENABLED = urldecode($pluginSettings['REMOTE_FPP_ENABLED']);
	$REMOTE_FPP_IP = urldecode($pluginSettings['REMOTE_FPP_IP']);
	
	
	$ENABLED = urldecode($pluginSettings['ENABLED']);
	//$COMMAND_ARRAY = explode(",",trim(strtoupper($VALID_COMMANDS)));
	
	$CONTROL_NUMBER_ARRAY = explode(",",$CONTROL_NUMBERS);
	
	
	$WHITELIST_NUMBER_ARRAY = explode(",",$WHITELIST_NUMBERS);
	
	

	$TSMS_from = "";
	$TSMS_body = "";
	$TSMS_BODY_CONTAINED_HEX = false;
	
	//	$TSMS_from = "+16195666240";
	//	$TSMS_from = "+16198840018";
		//$TSMS_body = "test";
		
	
	
	//	$TSMS_from = "+16195666240";
	//	$TSMS_from = "+16198840018";
	//	$TSMS_body = "test";
	
	//arg0 is  the program
	//arg1 is the first argument in the registration this will be --list
	//$DEBUG=true;
	//echo "Enabled: ".$ENABLED."<br/> \n";

	if(isset($_POST['From']) || $TSMS_from != "") {
	
		$TSMS_from = $_POST['From'];
	} else {
		logEntry("No Post data in FROM: Exiting");
		lockHelper::unlock();
		exit(0);
	}
	if(isset($_POST['Body']) || $TSMS_body != "") {
		$TSMS_body = $_POST['Body'];
	} else {
		logEntry("No Post data in BODY: Exiting");
		lockHelper::unlock();
		exit(0);
	}
	
	if($DEBUG) {
		logEntry("Twilio account_sid: ".$TSMS_account_sid);
		logEntry("Twilio account pass: ".$TSMS_auth_token);
	
		logEntry("TSMS message from: ".$TSMS_from);
		logEntry("TSMS Message body: ".$TSMS_body);
	
		//logEntry("TSMS Message body contain hex: ".$TSMS_BODY_CONTAINED_HEX);
	}
	logEntry("TSMS Message body contain hex: ".$TSMS_BODY_CONTAINED_HEX);
	//remove emoticon stuff
	$TSMS_body_NEW = stripHexChars(trim($TSMS_body));
	
	$lenOriginal = strlen(trim($TSMS_body));
	$lenNew = strlen(trim($TSMS_body_NEW));
	
	if($DEBUG) {
		logEntry("String length of original body: ".$lenOriginal);
		logEntry("string length of new body after processing: ".$lenNew);
	}
	
	
	
	if($lenNew !== $lenOriginal) {
		
		$TSMS_BODY_CONTAINED_HEX = true;
	}
	
	$TSMS_body = $TSMS_body_NEW;
	
	logEntry("TSMS Message body contain hex: ".$TSMS_BODY_CONTAINED_HEX);
	
	if(in_array($TSMS_from,$CONTROL_NUMBER_ARRAY)) {
		if($DEBUG) {
			logEntry("Inside checking for enable / disable");
		}
		if(trim(strtoupper($TSMS_body)) == "ENABLE" && $ENABLED != "ON") {
			$messageText = "ENABLING VIA CONTROL NUMBER";
	
			foreach($CONTROL_NUMBER_ARRAY as $NOTIFY_NUMBER) {
				$TSMS_from = $NOTIFY_NUMBER;
				logEntry("Sending notification to number: ".$TSMS_from);
				sendTSMSMessage($messageText);
			}
	
			WriteSettingToFile("ENABLED",urlencode("ON"),$pluginName);
			logEntry($messageText);
			lockHelper::unlock();
			exit(0);
		}
		if(trim(strtoupper($TSMS_body)) == "DISABLE" && $ENABLED == "ON") {
			$messageText = "DISABLING VIA CONTROL NUMBER";
	
			foreach($CONTROL_NUMBER_ARRAY as $NOTIFY_NUMBER) {
				$TSMS_from = $NOTIFY_NUMBER;
				logEntry("Sending notification to number: ".$TSMS_from);
				sendTSMSMessage($messageText);
			}
	
			WriteSettingToFile("ENABLED",urlencode("OFF"),$pluginName);
			logEntry($messageText);
			lockHelper::unlock();
			exit(0);
		}
		
		if(trim(strtoupper($TSMS_body)) == "DISABLE" && $ENABLED == "OFF") {
			$messageText = "The SMS request of DISABLE was not processed, the system is currently DISABLED";
		
		//	foreach($CONTROL_NUMBER_ARRAY as $NOTIFY_NUMBER) {
			//	$TSMS_from = $NOTIFY_NUMBER;
				logEntry("Sending notification to number: ".$TSMS_from);
				sendTSMSMessage($messageText);
			//}
		
			//WriteSettingToFile("ENABLED",urlencode("OFF"),$pluginName);
			logEntry($messageText);
			lockHelper::unlock();
			exit(0);
		}
		
		if(trim(strtoupper($TSMS_body)) == "ENABLE" && $ENABLED == "ON") {
			$messageText = "The SMS request of ENABLE was not processed, the system is currently ENABLED";
		
			//	foreach($CONTROL_NUMBER_ARRAY as $NOTIFY_NUMBER) {
			//	$TSMS_from = $NOTIFY_NUMBER;
			logEntry("Sending notification to number: ".$TSMS_from);
			sendTSMSMessage($messageText);
			//}
		
			//WriteSettingToFile("ENABLED",urlencode("OFF"),$pluginName);
			logEntry($messageText);
			lockHelper::unlock();
			exit(0);
		}
		
	}
	
//	$TSMS_from = "+16195666240";
	//$TSMS_from = "+16198840018";
	//$TSMS_body = "test";
	

		
	
	if(strtoupper($ENABLED) != "ON") {
		$REPLY_TEXT_PLUGIN_DISABLED = "We're sorry, the system is not accepting SMS at this time";
		sendTSMSMessage($REPLY_TEXT_PLUGIN_DISABLED);
		logEntry("Plugin Status: DISABLED Please enable in Plugin Setup to use");
		lockHelper::unlock();
		exit(0);
	}
		
	
	//want to reply even if locked / disabled
	if(($pid = lockHelper::lock()) === FALSE) {
		exit(0);
	
	}
	
	
	//if the command values do not have anything, set some defaults
	if(trim($playCommands) == "") {
		$playCommands = "PLAY";
		
	}
	
	if(trim($stopCommands) == "") {
		$stopCommands = "TERMINATE";
	
	}
	if(trim($repeatCommands) == "") {
		$repeatCommands = "REPEAT";
	
	}
	if(trim($statusCommands) == "") {
		$statusCommands = "STATUS";
	
	}
	
	


	if($DEBUG)
		print_r($pluginSettings);

		$playCommandsArray = explode(",",trim(strtoupper($playCommands)));
		$stopCommandsArray = explode(",",trim(strtoupper($stopCommands)));
		$repeatCommandsArray = explode(",",trim(strtoupper($repeatCommands)));
		$statusCommandsArray = explode(",",trim(strtoupper($statusCommands)));

		


			

			
						if($DEBUG)
							logEntry("processing message: from: ".$TSMS_from." Message: ".$TSMS_body);

						$messageText= preg_replace('/\s+/', ' ', $TSMS_body);
						$messageParts = explode(" ",$messageText);
						
						//need to reformat from ISO +1 format to local number format or use +1 in control numbers

						if(in_array($TSMS_from,$CONTROL_NUMBER_ARRAY))
						{
							///message used is to make sure that we do not process a message twice if it is from a number that is both a whitelist AND control numbers
							$MESSAGE_USED=true;
							logEntry("Control number found: ".$TSMS_from);
				

									
							if(in_array(trim(strtoupper($messageParts[0])),$playCommandsArray)) {
								logEntry( "SMS play cmd FOUND!!!");
								$CMD = "PLAY";
								
							}
									
								
							if(in_array(trim(strtoupper($messageParts[0])),$stopCommandsArray)) {
								logEntry( "SMS stop cmd FOUND!!!");
								$CMD = "STOP";
							
							} 
							
							if(in_array(trim(strtoupper($messageParts[0])),$repeatCommandsArray)) {
								logEntry( "SMS repeat cmd FOUND!!!");
								$CMD = "REPEAT";
								
							} 
							
							if(in_array(trim(strtoupper($messageParts[0])),$statusCommandsArray)) {
								logEntry( "SMS status cmd FOUND!!!");
								$CMD = "STATUS";
							}

							
						//	if(in_array(trim(strtoupper($messageParts[0])),$COMMAND_ARRAY)) {
						if($CMD != "") {
								logEntry("Command request: ".$messageText. " in uppercase is in control array");
								//do we have a playlist name?
								if($messageParts[1] != "") {
									processSMSCommand($TSMS_from,$CMD,$messageParts[1]);
									//processSMSCommand($from,$messageParts[0],$messageParts[1]);
								} else {

									//play the configured playlist@!!!! from the plugin
									processSMSCommand($TSMS_from,$CMD,$PLAYLIST_NAME);
									//processSMSCommand($from,$messageParts[0],$PLAYLIST_NAME);
								}
								
								
								$REPLY_TEXT_CMD = "Thank you - your command has been accepted from control number: ".$TSMS_from;
								sendTSMSMessage($REPLY_TEXT_CMD);
								
						
								//we do not want to do any more besides commands here
								logEntry("Exiting because command executed");
								lockHelper::unlock();
								exit(0);
									
							} else {
								//generic message to display from control number just like a regular user
								processSMSMessage($TSMS_from,$messageText);
								logEntry("Back from Control number adding new message");
				
									sendTSMSMessage($REPLY_TEXT);
									
								
							
							}
								
						}

						if(in_array($TSMS_from,$WHITELIST_NUMBER_ARRAY) && !$MESSAGE_USED)

						{
							$MESSAGE_USED=true;
							logEntry($messageText. " is from a white listed number");
							
							
							processSMSMessage($TSMS_from,$messageText);
							
						

						} else if(!$MESSAGE_USED){

							//not from a white listed or a control number so just a regular user
							//need to check for profanity
							//profanity checker API
							switch($PROFANITY_ENGINE) {
									
								case "NEUTRINO":
									$profanityCheck = check_for_profanity_neutrinoapi($messageText);
									break;

								case "WEBPURIFY":
									$profanityCheck = check_for_profanity_WebPurify($messageText);
									break;

								default:
									//default turn off profanity check
									$profanityCheck == false;
									break;
							}
							
							if(!$profanityCheck) {

								logEntry("Message: ".$messageText. " PASSED");
						
					
								processSMSMessage($TSMS_from,$messageText);
								sendTSMSMessage($REPLY_TEXT);
					

							} else {
								logEntry("message: ".$messageText." FAILED");
								$REPLY_TEXT = "Your message contains Profanity, Sorry. More messages like this will ban your phone number";


								sendTSMSMessage($REPLY_TEXT);
					

							}
						

						}
					

					if($IMMEDIATE_OUTPUT != "ON") {
						logEntry("NOT immediately outputting to matrix");
					} else {
						logEntry("IMMEDIATE OUTPUT ENABLED");
						logEntry("Matrix location: ".$MATRIX_LOCATION);
						logEntry("Matrix Exec page: ".$MATRIX_EXEC_PAGE_NAME);

						if($MATRIX_LOCATION != "127.0.0.1") {
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
						} else {
							$IMMEDIATE_CMD = $settings['pluginDirectory']."/".$MATRIX_MESSAGE_PLUGIN_NAME."/matrix.php";
							logEntry("LOCAL command: ".$IMMEDIATE_CMD);
						//	$forkResult = fork($IMMEDIATE_CMD);
							exec($IMMEDIATE_CMD);
							//forkExec($IMMEDIATE_CMD);
							//lockHelper::unlock();
							//exit(0);
						}
					}

			//	sleep(1);
				
	lockHelper::unlock();
	exit(0);
	
	
?>
