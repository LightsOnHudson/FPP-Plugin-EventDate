<?php

//send response function
function sendResponse($from,$REPLY_TEXT,$GMAIL_ADDRESS,$subject) {
	global $DEBUG, $gv, $EMAIL, $RESPONSE_METHOD;

	logEntry("Sending response using: ".$RESPONSE_METHOD);

	switch ($RESPONSE_METHOD) {

		case "SMS":
			$gv->sendSMS($from,$REPLY_TEXT);
				
			break;
				
		case "EMAIL":
			sendMail($GMAIL_ADDRESS, $EMAIL, $subject, $REPLY_TEXT);
			break;
	}



}
//sendmail using phpmailer function
function sendMail($to, $from, $subject, $body) {
	global $DEBUG, $EMAIL, $PASSWORD;

	date_default_timezone_set('Etc/UTC');

	if($DEBUG) {
		echo "To: ".$to."\n";
		echo "From: ".$from."\n";
		echo "subject: ".$subject."\n";
		echo "body: ".$body."\n";
	}

	//Create a new PHPMailer instance
	$mail = new PHPMailer;

	//Tell PHPMailer to use SMTP
	$mail->isSMTP();

	//Enable SMTP debugging
	// 0 = off (for production use)
	// 1 = client messages
	// 2 = client and server messages
	if($DEBUG) {
		$mail->SMTPDebug = 2;
	} else {

		$mail->SMTPDebug = 0;
	}

	//Ask for HTML-friendly debug output
	if($DEBUG)
		$mail->Debugoutput = 'html';

		//Set the hostname of the mail server
		$mail->Host = 'smtp.gmail.com';
		// use
		// $mail->Host = gethostbyname('smtp.gmail.com');
		// if your network does not support SMTP over IPv6

		//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
		$mail->Port = 587;

		//Set the encryption system to use - ssl (deprecated) or tls
		$mail->SMTPSecure = 'tls';

		//Whether to use SMTP authentication
		$mail->SMTPAuth = true;

		//Username to use for SMTP authentication - use full email address for gmail
		$mail->Username = $EMAIL;

		//Password to use for SMTP authentication
		$mail->Password = $PASSWORD;

		//Set who the message is to be sent from
		$mail->setFrom($EMAIL, 'Holiday');

		//Set an alternative reply-to address
		$mail->addReplyTo($EMAIL, 'Holiday');

		//Set who the message is to be sent to
		$mail->addAddress($to, $from);

		//Set the subject line
		$mail->Subject = $subject;

		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		$mail->msgHTML($body);

		//Replace the plain text body with one created manually
		$mail->AltBody = $body;

		//Attach an image file
		//$mail->addAttachment('images/phpmailer_mini.png');

		//send the message, check for errors
		if (!$mail->send()) {
			logEntry( "Mailer Error: " . $mail->ErrorInfo);
		} else {
			logEntry( "Message sent!");
		}

}

//fork a non blocking fppd process

function forkExec($cmd) {
	global $DEBUG;

	if($DEBUG)
		logEntry("Forking command: ".$cmd);
	
	
	//$safe_arg["arg_2"] = escapeshellarg($arg_2);
	$pid = pcntl_fork();

	if ( $pid == -1 ) {
		// Fork failed
		if($DEBUG)
			logEntry("fork failed");

			exit(1);
	} else if ( $pid ) {
		// We are the parent
		if($DEBUG) {
			logEntry("------------");
			logEntry("fork parent");
			logEntry("------------");
		}
		return "Parent";

		// Can no longer use $db because it will be closed by the child
		// Instead, make a new MySQL connection for ourselves to work with
	} else {
		if($DEBUG){
			logEntry("------------");
			logEntry("fork child");
			logEntry("------------");
		}
		//logEntry("sleeping 5 seconds, processing, thensleeping agin");

		exec($cmd);
		
		return "Child";
	}
}
//fork a non blocking fppd process

function fork($argv) {
	global $DEBUG;

	$safe_arg = escapeshellarg($argv[4]);
	//$safe_arg["arg_2"] = escapeshellarg($arg_2);
	$pid = pcntl_fork();

	if ( $pid == -1 ) {
		// Fork failed
		if($DEBUG)
			logEntry("fork failed");

			exit(1);
	} else if ( $pid ) {
		// We are the parent
		if($DEBUG) {
			logEntry("------------");
			logEntry("fork parent");
			logEntry("------------");
		}
		return "Parent";

		// Can no longer use $db because it will be closed by the child
		// Instead, make a new MySQL connection for ourselves to work with
	} else {
		if($DEBUG){
			logEntry("------------");
			logEntry("fork child");
			logEntry("------------");
		}
		//logEntry("sleeping 5 seconds, processing, thensleeping agin");

		processCallback($argv);
		return "Child";
	}
}
//get the string between two characters
function get_string_between ($str,$from,$to) {

	$string                                         = substr($str,strpos($str,$from)+strlen($from));

	if (strstr ($string,$to,TRUE) != FALSE) {

		$string                                     =   strstr ($string,$to,TRUE);

	}

	return $string;

}
//update plugin

function updatePluginFromGitHub($gitURL, $branch="master", $pluginName) {
	
	
	global $settings;
	logEntry ("updating plugin: ".$pluginName);
	
	logEntry("settings: ".$settings['pluginDirectory']);
	
	//create update script
	//$gitUpdateCMD = "sudo cd ".$settings['pluginDirectory']."/".$pluginName."/; sudo /usr/bin/git git pull ".$gitURL." ".$branch;

	$pluginUpdateCMD = "/opt/fpp/scripts/update_plugin ".$pluginName;

	logEntry("update command: ".$pluginUpdateCMD);


	exec($pluginUpdateCMD, $updateResult);

	//logEntry("update result: ".print_r($updateResult));

	//loop through result	
	return;// ($updateResult);
	
	
	
}
//create script to randmomize
function createScriptFile($scriptFilename,$scriptCMD) {


	global $scriptDirectory,$pluginName;

	$scriptFilename = $scriptDirectory."/".$scriptFilename;

	logEntry("Creating  script: ".$scriptFilename);
	
	$ext = pathinfo($scriptFilename, PATHINFO_EXTENSION);

	
	$data = "";

	$data .="#!/bin/sh\n";

	
	$data .= "\n";
	$data .= "#Script to run randomizer\n";
	$data .= "#Created by ".$pluginName."\n";
	$data .= "#\n";
	$data .= "/usr/bin/php ".$scriptCMD."\n";
	
	logEntry($data);


	$fs = fopen($scriptFilename,"w");
	fputs($fs, $data);
	fclose($fs);

}
//return the next event file available for use

//get the next available event filename
function getNextEventFilename() {

	$MAX_MAJOR_DIGITS=2;
	$MAX_MINOR_DIGITS=2;
	global $eventDirectory;

	//echo "Event Directory: ".$eventDirectory."<br/> \n";

	$MAJOR=array();
	$MINOR=array();

	$MAJOR_INDEX=0;
	$MINOR_INDEX=0;

	$EVENT_FILES = directoryToArray($eventDirectory, false);
	//print_r($EVENT_FILES);

	foreach ($EVENT_FILES as $eventFile) {

		$eventFileParts = explode("_",$eventFile);

		$MAJOR[] = (int)basename($eventFileParts[0]);
		//$MAJOR = $eventFileParts[0];

		$minorTmp = explode(".fevt",$eventFileParts[1]);

		$MINOR[] = (int)$minorTmp[0];

		//echo "MAJOR: ".$MAJOR." MINOR: ".$MINOR."\n";
		//print_r($MAJOR);
		//print_r($MINOR);

	}

	$MAJOR_INDEX = max(array_values($MAJOR));
	$MINOR_INDEX = max(array_values($MINOR));

	//echo "Major max: ".$MAJOR_INDEX." MINOR MAX: ".$MINOR_INDEX."\n";



	if($MAJOR_INDEX <= 0) {
		$MAJOR_INDEX=1;
	}
	if($MINOR_INDEX <= 0) {
		$MINOR_INDEX=1;

	} else {

		$MINOR_INDEX++;
	}

	$MAJOR_INDEX = str_pad($MAJOR_INDEX, $MAX_MAJOR_DIGITS, '0', STR_PAD_LEFT);
	$MINOR_INDEX = str_pad($MINOR_INDEX, $MAX_MINOR_DIGITS, '0', STR_PAD_LEFT);
	//for now just return the next MINOR index up and keep the same Major
	$newIndex=$MAJOR_INDEX."_".$MINOR_INDEX.".fevt";
	//echo "new index: ".$newIndex."\n";
	return $newIndex;
}


function directoryToArray($directory, $recursive) {
	$array_items = array();
	if ($handle = opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if (is_dir($directory. "/" . $file)) {
					if($recursive) {
						$array_items = array_merge($array_items, directoryToArray($directory. "/" . $file, $recursive));
					}
					$file = $directory . "/" . $file;
					$array_items[] = preg_replace("/\/\//si", "/", $file);
				} else {
					$file = $directory . "/" . $file;
					$array_items[] = preg_replace("/\/\//si", "/", $file);
				}
			}
		}
		closedir($handle);
	}
	return $array_items;
}


//check all the event files for a string matching this and return true/false if exist
function checkEventFilesForKey($keyCheckString) {
	global $eventDirectory;

	$keyExist = false;
	$eventFiles = array();

	$eventFiles = directoryToArray($eventDirectory, false);
	foreach ($eventFiles as $eventFile) {

		if( strpos(file_get_contents($eventFile),$keyCheckString) !== false) {
			// do stuff
			$keyExist= true;
			break;
			// return $keyExist;
		}
	}

	return $keyExist;

}
?>
