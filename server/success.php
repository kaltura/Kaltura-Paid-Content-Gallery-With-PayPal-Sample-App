<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Thank you</title>
	<script src="https://www.paypalobjects.com/js/external/dg.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
	<script src="../client/pptransact.js"></script>
	
	<?php
	require_once("pptransact.php");
	$transact = new pptransact();
	$data = explode("|", $_GET["data"]);
	$returnObj = $transact->commitPayment($data[1], $_GET["PayerID"], $_GET["token"], $data[0], $data[2]);
	?>
	
	<script>
		function parentExists() {
		 	return (parent.location == window.location)? false : true;
		}
		
		function closeFlow(param) {
			pptransact.init('cf',true);
			if(!parentExists()) {
				var jsonData = $.parseJSON('<?= $returnObj ?>');
				pptransact.saveToLocalStorage(jsonData.userId,<?= $returnObj ?>,null);
				$.ajax({
					type: "POST",
					url: "savePurchase.php",
					data: {id: "<?php echo $data[2]; ?>"}
				}).done(function(msg) {
					setTimeout ( forceCloseFlow, '3000' );
				});
			} else {
				parent.pptransact.releaseDG(<?= $returnObj ?>);
			}
		}
		
		function forceCloseFlow() {
			//The page you want to redirect the user after successfully storing data in local storage.
			//window.location.href = '../../index.html';
			
			// This case is for iPhone - we're closing the purchase window to go back to the main gallery
			window.close();
		}
	</script>
</head>
<body onload="closeFlow(false)">
	<h1>Thank You for purchasing at our store!</h1>
</body>
</html>