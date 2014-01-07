<?
include_once('../../../../../wp-config.php');
include_once('../functions.php');

global $wpdb;

$Vars = get_option('Dharma_Vars');

$sql = $wpdb->prepare('SELECT G.name as Gname, G.phone,G.email, G.id,B.checkin,B.checkout,
					I.payment, I.comment,I.clubcard,I.bookingdata,I.arivaltime,I.totaldue,B.numberofnights
         FROM `'.$wpdb->prefix.DATABASE_PREFIX.'bookings` B 
             LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'guests G ON B.idguest = G.id 
             LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'invoices I ON B.invoice = I.invoice 
         Where I.invoice=%s',$_POST['invoice']);
$data = $wpdb->get_row( $sql);$sql = $wpdb->prepare('SELECT R.name,B.beds
         FROM `'.$wpdb->prefix.DATABASE_PREFIX.'bookings` B 
             LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes R ON R.id = B.idroomtype 
         Where B.invoice=%s',$_POST['invoice']);
$rentals = $wpdb->get_results( $sql);


$balance = ($data->payment - $data->totaldue);
$class = ($balance < 0 ? 'warning':'success');

?>
<h2><?=$data->Gname?>
<?php if($data->phone):?>
	<a href="tell:<?=$data->phone?>"><img src="<?=PLUGIN_ROOT_URL?>/img/phone.png" alt="<?=__('call phone',PLUGIN_TRANS_NAMESPACE)?>" /></a>
<?php endif?>
<?php if($data->email):?>
	<a href="mailto:<?=$data->email?>">	<img src="<?=PLUGIN_ROOT_URL?>/img/email.png" alt="<?=__('send email',PLUGIN_TRANS_NAMESPACE)?>" /></a>
<?php endif?>
</h2>
<big>
	<?php if($_POST['type'] == 1 ):?>
		<img src="<?=PLUGIN_ROOT_URL?>/img/checkout.png" alt="<?=__('checking out',PLUGIN_TRANS_NAMESPACE)?>" />
		<?=date('jS M',strtotime($data->checkout))?>
	<?php endif ?>
	<?php if($_POST['type'] == 2 ):?>
	<img src="<?=PLUGIN_ROOT_URL?>/img/checkin.png" alt="<?=__('checking out',PLUGIN_TRANS_NAMESPACE)?>" />
	<?=date('jS M',strtotime($data->checkin))?> <?=$data->arivaltime?>
	: <?=$data->numberofnights?> 	<img src="<?=PLUGIN_ROOT_URL?>/img/nights.png" alt="<?=__('total nights',PLUGIN_TRANS_NAMESPACE)?>" />

	<?php endif ?>
</big>
	
<br />
balance : <span class="<?=$class?>"><?=$Vars['currancySymbol'].money_format('%.2n',$balance)?></span><br />


<ul><?php foreach($rentals as $rental) :?>
	<li>
		<?=$rental->name?> : <?=$rental->beds?> 
		<img src="<?=PLUGIN_ROOT_URL?>/img/guest<?=($rental->beds < 3?$rental->beds:'s')?>.png" alt="" title="<?=__('Guests',PLUGIN_TRANS_NAMESPACE)?>" />
	</li>
<?php endforeach ?></ul>


