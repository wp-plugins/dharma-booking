<?php
//dummy gateway, should include finaly page return 
function makePaymentForm($acount,$currency,$payment,$depositPersent = 0){
   ?>
<form action="https://secure-test.worldpay.com/wcc/purchase" method="POST" class="paymentgateway">
<input type="hidden" name="testMode" value="100">
<input type="hidden" name="instId" value="<?=$acount?>"> 
<input type="hidden" name="cartId" value="id" class="gatewayInvoiceID">
<input type="hidden" name="amount" value="99.00" id="gatewayprice<?=$payment?>">
<input type="hidden" name="currency" value="<?=$currency?>">
<button type="submit">
	<img src="<?=PLUGIN_ROOT_URL;?>img/gateways/worldpay.png" />
	<h2><?=($payment=='full'?__('Pay full amount.',PLUGIN_TRANS_NAMESPACE):sprintf( __( 'Pay deposit of %d%%.', PLUGIN_TRANS_NAMESPACE) , $depositPersent ));?></h2>
</button>	
</form>		
<?
}
?>
