<?php
//Generates the configuration file for the admin, automatically places the file in the
//correct directory and also downloads as a seperate file in case it is wanted
ob_start();
//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration($_REQUEST['partnerId']);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$client->setKs($_REQUEST['session']);

//These headers allow the AJAX download to occur
header("Content-Type: application/octet-stream; charset=UTF-8");
header("Content-Disposition: inline; filename=\"kalturaConfig.php.backup\"");
header("Set-Cookie: fileDownload=true; path=/");
echo "<?php \n";
//Retrieves the entry metadata profile
$filter = new KalturaMetadataProfileFilter();
$filter->metadataObjectTypeEqual = 1;
$filter->nameEqual = 'PayPal (Entries)';
$pager = new KalturaFilterPager();
$pager->pageSize = 1;
$pager->pageIndex = 1;
$entryMetadata = $client->metadataProfile->listAction($filter, $pager)->objects[0]->id;
//Retrieves the category metadata profile
$filter = new KalturaMetadataProfileFilter();
$filter->metadataObjectTypeEqual = 2;
$filter->nameEqual = 'PayPal (Categories)';
$pager = new KalturaFilterPager();
$pager->pageSize = 1;
$pager->pageIndex = 1;
$categoryMetadata = $client->metadataProfile->listAction($filter, $pager)->objects[0]->id;
//Retrieves the user metadata profile
$filter = new KalturaMetadataProfileFilter();
$filter->metadataObjectTypeEqual = 3;
$filter->nameEqual = 'PayPal (Users)';
$pager = new KalturaFilterPager();
$pager->pageSize = 1;
$pager->pageIndex = 1;
$userMetadata = $client->metadataProfile->listAction($filter, $pager)->objects[0]->id;
?>
// To get started with Kaltura, you need an acccount.
// Get a free trial at: http://corp.kaltura.com
// In your Kaltura account, get the partner Id and API Admin Secret from:
// http://www.kaltura.com/index.php/kmc/kmc4#account|integration
define("PARTNER_ID", '<?php echo $_REQUEST['partnerId']; ?>');
define("ADMIN_SECRET",'<?php echo $client->partner->get($_REQUEST['partnerId'])->adminSecret; ?>');
define("USER_SECRET", '<?php echo $client->partner->get($_REQUEST['partnerId'])->secret; ?>');
define("PLAYER_UICONF_ID", <?php echo $_REQUEST['default']; ?>);
define("BUY_BUTTON_PLAYER_UICONF_ID", <?php echo $_REQUEST['paid']; ?>);
define("PAYPAL_METADATA_PROFILE_ID", <?php echo $entryMetadata; ?>);
define("PAYPAL_CATEGORY_METADATA_PROFILE_ID", <?php echo $categoryMetadata; ?>);
define("PAYPAL_USER_METADATA_PROFILE_ID", <?php echo $userMetadata; ?>);
//Generates a USER ID based on the machine name and IP address.
function getRealIpAddr() {
	if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
	{
		$ip=$_SERVER['HTTP_CLIENT_IP'];
	}
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
	{
		$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	else
	{
		$ip=$_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}
if(isset($_COOKIE['kaypaluserid']) && $_COOKIE['kaypaluserid'] != "") {
	$USER_ID = $_COOKIE['kaypaluserid'];
}
else {
	$expire=time()+60*60*24*365;
	$user = implode('_', explode(':','demo_user_'.mt_rand(1, 9999999).getRealIpAddr()));
	setcookie('kaypaluserid', $user, $expire);
	$USER_ID = $user;
}
<?php 
file_put_contents('../server/kalturaConfig.php', ob_get_contents());
ob_end_flush();