<?php
function makePaymentForm($acount,$currency,$payment,$depositPersent = 0){
   ?>
	<button> 
		<?=($payment=='full'?__('Pay full amount.',PLUGIN_TRANS_NAMESPACE):sprintf( __( 'Pay %d%% deposit.', PLUGIN_TRANS_NAMESPACE) , $depositPersent ));?>
	</button> 
<?php
}
?>
