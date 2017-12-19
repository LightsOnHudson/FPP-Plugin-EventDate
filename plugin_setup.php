<?php


include_once "/opt/fpp/www/common.php";
include_once 'functions.inc.php';
include_once 'commonFunctions.inc.php';


$pluginName = "EventDate";
include_once 'version.inc';

$PLAYLIST_NAME="";
$MAJOR = "98";
$MINOR = "01";
$eventExtension = ".fevt";


//arg0 is  the program
//arg1 is the first argument in the registration this will be --list
//$DEBUG=true;

$SMSEventFile = $eventDirectory."/".$MAJOR."_".$MINOR.$eventExtension;
$SMSGETScriptFilename = $scriptDirectory."/".$pluginName."_GET.sh";

$messageQueue_Plugin = "MessageQueue";
$MESSAGE_QUEUE_PLUGIN_ENABLED=false;


$logFile = $settings['logDirectory']."/".$pluginName.".log";



$messageQueuePluginPath = $settings['pluginDirectory']."/".$messageQueue_Plugin."/";

$messageQueueFile = urldecode(ReadSettingFromFile("MESSAGE_FILE",$messageQueue_Plugin));

if(file_exists($messageQueuePluginPath."functions.inc.php"))
{
	include $messageQueuePluginPath."functions.inc.php";
	$MESSAGE_QUEUE_PLUGIN_ENABLED=true;

} else {
	logEntry("Message Queue Plugin not installed, please install");
	echo "Message Queue Plugin not installed, please install and return to this plugin page";
	exit(0);
}


$gitURL = "https://github.com/LightsOnHudson/FPP-Plugin-EventDate.git";


$pluginUpdateFile = $settings['pluginDirectory']."/".$pluginName."/"."pluginUpdate.inc";


logEntry("plugin update file: ".$pluginUpdateFile);


if(isset($_POST['updatePlugin']))
{
	$updateResult = updatePluginFromGitHub($gitURL, $branch="master", $pluginName);

	logEntry("update result: ". $updateResult);//."<br/> \n";
	
	if(file_exists($settings['pluginDirectory']."/".$pluginName."/fpp_install.sh"))
	{
		$updateInstallCMD = $settings['pluginDirectory']."/".$pluginName."/fpp_install.sh";
		logEntry("running upgrade install script: ".$updateInstallCMD);
		exec($updateInstallCMD,$sysOutput);
		//echo $sysOutput;
	
	} else {
		logEntry("No fpp_install.sh upgrade script available");
	}
	
	
}
$DEBUG = $pluginSettings['DEBUG'];

if(isset($_POST['submit']))
{
	if($DEBUG)
	print_r($_POST);
	


//	echo "Writring config fie <br/> \n";
	WriteSettingToFile("MONTH",urlencode($_POST['MONTH']), $pluginName);
	WriteSettingToFile("DAY",urlencode($_POST['DAY']), $pluginName);
	WriteSettingToFile("YEAR",urlencode($_POST['YEAR']), $pluginName);
	
	WriteSettingToFile("MIN",urlencode($_POST['MIN']), $pluginName);
	WriteSettingToFile("HOUR",urlencode($_POST['HOUR']), $pluginName);
	WriteSettingToFile("HOUR_MODE",urlencode($_POST["HOUR_MODE"]),$pluginName);
	WriteSettingToFile("PRE_TEXT",urlencode($_POST["PRE_TEXT"]),$pluginName);
	WriteSettingToFile("POST_TEXT",urlencode($_POST["POST_TEXT"]),$pluginName);
	WriteSettingToFile("EVENT_NAME",urlencode($_POST["EVENT_NAME"]),$pluginName);
	
	WriteSettingToFile("LAST_READ",urlencode($_POST["LAST_READ"]),$pluginName);
	
	WriteSettingToFile("MATRIX_LOCATION",urlencode($_POST["MATRIX_LOCATION"]),$pluginName);
	
	
	
}

//THIS IS O COOL!
//set the variable names as necessary??? do we even need to do this???

foreach ($pluginSettings as $key => $value) {

	if($DEBUG) {
		logEntry("KEY: ".$key." = ".$value);
	}
	//	echo "Key: ".$key." " .$value."\n";

	${$key} = urldecode($value);

}
	
if($PRE_TEXT == "") {
	$PRE_TEXT = "It is ";
}

if($POST_TEXT =="") {
	$POST_TEXT = " until ";
}

if($EVENT_NAME == "") {
	$EVENT_NAME = " THE EVENT!";
	
}

	
	if((int)$LAST_READ == 0 || $LAST_READ == "") {
		$LAST_READ=0;
	}

	$Plugin_DBName = $settings['configDirectory']."/FPP.".$pluginName.".db";
	
	//echo "PLUGIN DB:NAME: ".$Plugin_DBName;
	
	$db = new SQLite3($Plugin_DBName) or die('Unable to open database');
	
	//create the default tables if they do not exist!
	createTables();
?>

<html>
<head>
</head>

<div id="EventDate" class="settings">
<fieldset>
<legend><?php echo $pluginName." Version: ".$pluginVersion;?> Support Instructions</legend>

<p>Known Issues:
<ul>
<li>None
</ul>

<p>Configuration:
<ul>
<li>Configure the date and time of your event</li>
<li>Enter in the PRE TEXT that will appear before your countdown</li>
<li>Configure 12 or 24 hour countdown mode</li>

<li>Schedule the event in your Playlist to send the countdown out your Matrix</li>
</ul>

<p/>
<b>This plugin requires ACCURATE time for its calculation. Please ensure RTC is working properly</b>

<p/>

<form method="post" action="http://<? echo $_SERVER['SERVER_NAME']?>/plugin.php?plugin=<?echo $pluginName;?>&page=plugin_setup.php">


<?
//will add a 'reset' to this later

echo "<input type=\"hidden\" name=\"LAST_READ\" value=\"".$LAST_READ."\"> \n";


$restart=0;
$reboot=0;

echo "ENABLE PLUGIN: ";


PrintSettingCheckbox("Plugin: ".$pluginName." ", "ENABLED", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");


echo "<p/> \n";


echo "Pre Text: (It is): \n";
echo "<input type=\"text\" value=\"".$PRE_TEXT."\" name=\"PRE_TEXT\"> \n";

echo "<p/> \n";


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
if ((int) $y >= 2){
	$messageText .= $y. " years ";
}
if ((int) $d >= 2 ){
	$messageText .= $d. " days ";
}
if ((int) $h >= 2 && $INCLUDE_HOURS == "ON"){
	$messageText .= $h. " hours ";
}
if ((int) $m >= 2 && $INCLUDE_MINUTES == "ON"){
	$messageText .= $m. " minutes ";
}
if ((int) $y == 1){
	$messageText .= $y. " year ";
}
if ((int) $d ==1 ){
	$messageText .= $d. " day ";
}
if ((int) $h == 1 && $INCLUDE_HOURS == "ON"){
	$messageText .= $h. " hour ";
}
if ((int) $m == 1 && $INCLUDE_MINUTES == "ON"){
	$messageText .= $m. " minute ";
}

$messageText .= " ".$POST_TEXT. " ".$EVENT_NAME;


echo "<p/> \n";
echo "EVENT DATE: \n";
printMonthSelection($MONTH, "MONTH");
printDaySelection($DAY, "DAY");
printYearSelection($YEAR, "YEAR");

echo "Hour: \n";
printHourSelection($HOUR, "HOUR");
echo "Min: \n";
printMinSelection($MIN, "MIN");

echo "<p/> \n";

echo "Post Text: (Until <Event Name>): \n";
echo "<input type=\"text\" value=\"".$POST_TEXT."\" name=\"POST_TEXT\"> \n";

echo "<p/> \n";

echo "Event Name: (Christmas, Halloween, Labor day): \n";
echo "<input type=\"text\" value=\"".$EVENT_NAME."\" name=\"EVENT_NAME\"> \n";


echo "<p/>";

echo "If Remaining time >= 1 day, include: \n";
echo "<br/> \n";
echo "Include Hours: \n";
PrintSettingCheckbox("Include Days ", "INCLUDE_HOURS", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");

echo "<br/> \n";
echo "Include Minutes: \n";
PrintSettingCheckbox("Include Hours", "INCLUDE_MINUTES", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");

echo "<p/> \n";
echo "Will appear as: \n";
echo "<hr/> \n";
echo "<marquee behavior=\"scroll\" scrollamount=\"5\" direction=\"left\" onmouseover=\"this.stop();\" onmouseout=\"this.start();\">\n";

echo preg_replace('!\s+!', ' ', $messageText);
echo "</marquee> \n";
echo "<hr/> \n";

echo "<p/> \n";
echo "Immediately output to Matrix (Run MATRIX plugin): ";

//if($IMMEDIATE_OUTPUT == "on" || $IMMEDIATE_OUTPUT == 1) {
//	echo "<input type=\"checkbox\" checked name=\"IMMEDIATE_OUTPUT\"> \n";
	PrintSettingCheckbox("Immediate output to Matrix", "IMMEDIATE_OUTPUT", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");
//} else {
	//echo "<input type=\"checkbox\"  name=\"IMMEDIATE_OUTPUT\"> \n";
//}
echo "<p/> \n";
?>
MATRIX Message Plugin Location: (IP Address. default 127.0.0.1);
<input type="text" size="15" value="<? if($MATRIX_LOCATION !="" ) { echo $MATRIX_LOCATION; } else { echo "127.0.0.1";}?>" name="MATRIX_LOCATION" id="MATRIX_LOCATION"></input>
<p/>


<input id="submit_button" name="submit" type="submit" class="buttons" value="Save Config">
<?
 if(file_exists($pluginUpdateFile))
 {
 	//echo "updating plugin included";
	include $pluginUpdateFile;
}
?>
</form>


<p>To report a bug, please file it against the sms Control plugin project on Git:<? echo $gitURL;?> 
</fieldset>
</div>
<br />
</html>
