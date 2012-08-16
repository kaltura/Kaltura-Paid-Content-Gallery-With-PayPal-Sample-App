<?php
//Used to track the user's ID to keep purchases specific to one person
require_once('kalturaConfig.php');
function verifyUser($userId = 0) {
	global $USER_ID;
    $YourSessionUserId = $USER_ID;
    $returnVal = ($userId == $YourSessionUserId) ? true : false;
    return $returnVal;
}

function getUserId() {
	global $USER_ID;
    $result = $USER_ID;
    return $result;
}

function recordPayment($paymentObj = "") {
    $userId = $paymentObj["userId"];
    $itemId = $paymentObj["itemId"];
    //$transactionId = $paymentObj["transactionId"];
    $paymentStatus = $paymentObj["paymentStatus"];
    //$orderTime = $paymentObj["orderTime"];
}
    
function verifyPayment($userId = 0, $itemId = 0) {
    $result = false;    
    return $result;
}

function getPayment($userId = 0, $itemId = 0) {
	global $USER_ID;    
    $returnObj = array("success" => true,
                       "error" => "",
                       "transactionId" => "12345678",
                       "orderTime" => "2011-09-29T04:47:51Z",
                       "paymentStatus" => "Pending",
                       "itemId" => "123",
                       "userId" => $USER_ID);
    return $returnObj;
}