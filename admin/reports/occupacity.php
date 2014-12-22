<?php
class occupacity extends reports {
	function occupacity(){  }
	function setStartDate($input)	{ $this->startDate = $input; $this->startDateSQL = date('Y-m-d',strtotime($this->startDate)); }
	function setEndDate($input)		{ $this->endDate = $input; $this->endDateSQL = date('Y-m-d',strtotime($this->endDate)); }
	function javascriptGo($prefix){ ?><script> jQuery(document).ready(function(){	doOccupacityChart('<?=$prefix?>');});</script><?php  }

	function setup(){
		?> <link rel="stylesheet" type="text/css" href="<?=PLUGIN_ROOT_URL?>libs/jplot/jquery.jqplot.min.css" />

		<script type="text/javascript">
			var chartingData = new Array;
			chartUpdatePage = '<?=PLUGIN_ROOT_URL?>admin/ajax/chartUpdate.php';
			getDayDetailsAjax = '<?=PLUGIN_ROOT_URL?>admin/ajax/getDayDetailsAjax.php';
			getRentalDetailSmallsAjax = '<?=PLUGIN_ROOT_URL?>admin/ajax/getDayDetailsSmallAjax.php';

		</script>
		<div class="floatright popupup-box" id="dayDetailsBox"></div>
		<strong><?=__('Double click to reset',PLUGIN_TRANS_NAMESPACE)?></strong>
		<?php
	}


	function isetupVariables($prefix ){
		global $wpdb;
		$i=0;
		while(strtotime("+$i day", strtotime($this->startDate)) < strtotime($this->endDate)){
			$currentday = date('Y-m-d',strtotime("$i day", strtotime($this->startDate)));
			$v = $wpdb->get_var($wpdb->prepare('SELECT sum(beds) FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings 
																WHERE checkin <= DATE(%s) AND checkout > DATE(%s) AND idguest <> 0', $currentday, $currentday) );
			$this->occupacity .= '[\''.$currentday.' 23:59 \','.($v?$v:0).','.strtotime($currentday).'],';	
			$i++;
		}
		$sql = $wpdb->prepare('SELECT SUM(B.beds) as beds, B.idguest,B.invoice,B.checkout,B.checkin,I.arivaltime,B.invoice
					FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings B LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'invoices I ON B.invoice = I.invoice
					WHERE checkin > DATE(%s) OR checkout < DATE(%s) GROUP BY B.invoice ORDER BY checkin', $this->startDateSQL, $this->endDateSQL); 
		foreach($wpdb->get_results( $sql) as $item){
			if(strtotime($item->checkin) > strtotime($this->startDateSQL) && strtotime($item->checkin) < strtotime($this->endDateSQL) ){
				$this->checkin .= '[\''.date('Y-m-d',strtotime($item->checkin)).' '.$item->arivaltime.' \','.$item->beds.','.$item->invoice.'],';	
			}
			if(strtotime($item->checkout) < strtotime($this->endDateSQL) && strtotime($item->checkout) > strtotime($this->startDateSQL) ){
				$this->checkout .= '[\''.date('Y-m-d',strtotime($item->checkout)).' 11am \','.$item->beds.','.$item->invoice.'],';	
			}
		}
		?>
			<script>chartingData["<?=$prefix?>"]=[[<?=$this->occupacity?>],[<?=$this->checkout?>],[<?=$this->checkin?>]]</script>
		<div id="<?=$prefix?>chart" class="ocupancity-chart"></div>';
		<?php 
	}
}
/*	sql calls and formating for all reports and chats
		$sql ="SELECT D.date,	 DATEDIFF(D.date, '$from') AS day_no,	 R.capacity, SUM(B.beds) as sumbed,
		SUM(I.totaldue) as expected, SUM(I.payment) as payed,I.arivaltime
			FROM
				(SELECT	checkin AS date FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings
					UNION SELECT checkout FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings
					UNION SELECT DATE('".$this->startDate."')) D
				LEFT JOIN (SELECT invoice, id, idguest, idroomtype, beds, checkin, checkout FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings 
						UNION	SELECT 0, 0 , 0 , id , 0 , DATE('".$this->startDate."'), DATE('".$this->endDate."') FROM ".$wpdb->prefix.DATABASE_PREFIX."roomtypes) B
					ON D.date BETWEEN B.checkin AND ADDDATE(B.checkout, -1)
				LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."roomtypes R ON B.idroomtype = R.id
				LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."invoices I ON B.invoice = I.invoice
			WHERE
				D.date BETWEEN '".$this->startDate."' AND ADDDATE('".$this->endDate."', -1)
			GROUP BY
				D.date
			ORDER BY R.id,D.date";
			*/
?>
