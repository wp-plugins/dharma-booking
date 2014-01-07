<?php    
include_once('../../../../../wp-config.php');
		$sqlStart = date('y-m-d',strtotime($_GET['startDate']));
		$sqlEnd = date('y-m-d',strtotime($_GET['endDate']));
	$sql =
    'SELECT
        B.checkin,
        B.checkout,
        R.name AS roomtype,
        R.id AS idroomtype,
        R.capacity,
        B.beds,
			sum(B.beds) as sumbed,
        B.idguest,
        B.id AS bookingid,
		  R.price
    FROM
				'.$wpdb->prefix.DATABASE_PREFIX.'bookings B
        LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes R ON B.idroomtype = R.id
		        LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'guests G ON G.id = B.idguest  
    WHERE
		(B.checkin >= \''.$sqlStart.'\' 
		AND B.checkin <= \''.$sqlEnd.'\') '.
		($_GET['noadmin']?' AND G.id != 0':'').'
    GROUP BY
        B.checkin
    ORDER BY
        B.checkin desc';
				foreach($wpdb->get_results( $sql) as $item){
					$last = $item->checkin;
					
					$data[] =  '[\''.$item->checkin.' 11:50pm\','.$item->sumbed.']';
	}
	//echo '['.implode(',',$data).']';
	echo "[['2012-11-20 11:50pm',99],['2012-11-17 11:50pm',2],['2012-11-11 11:50pm',2],['2012-11-02 11:50pm',3],['2012-10-17 11:50pm',2]]";
?>
