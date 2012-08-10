<?php
require_once "client/KalturaClient.php";
if(array_key_exists('entryId', $_REQUEST)) {
	require_once('kalturaConfig.php');
	global $USER_ID;
	getSessionOnce($_REQUEST['entryId'], $USER_ID);
}

//Generates a Kaltura session if a video has been paid for
function getSession($itemId,$userId){
	$conf = new KalturaConfiguration(PARTNER_ID);
	$client = new KalturaClient($conf);
	$session = $client->session->start(USER_SECRET, $userId, KalturaSessionType::USER, PARTNER_ID, 86400, 'sview:'.$itemId);
	if (!isset($session)) {
		die("Could not establish Kaltura session with OLD session credentials. Please verify that you are using valid Kaltura partner credentials.");
	}
	$client->setKs($session);
	return $session;
}

//Generates a Kaltura session for an entry that has been purchased as part of a channel
function getSessionOnce($itemId,$userId){
	//Create a session
	$conf = new KalturaConfiguration(PARTNER_ID);
	$client = new KalturaClient($conf);
	$session = $client->session->start(USER_SECRET, $userId, KalturaSessionType::USER, PARTNER_ID, 86400, 'sview:'.$itemId);
	if (!isset($session)) {
		die("Could not establish Kaltura session with OLD session credentials. Please verify that you are using valid Kaltura partner credentials.");
	}
	$client->setKs($session);
	echo $session;
}