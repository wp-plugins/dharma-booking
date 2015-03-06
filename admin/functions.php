<?php 
/* be nice to get rid of this file*/
function getRoomtypesDH ($forSelect = false) {
		global $wpdb;
		$roomtypes = array();
		$res = mysql_query('SELECT id, name, minimum, capacity, price, discription FROM '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes ORDER by menuorder');
		while ($row = mysql_fetch_assoc($res)) {
			$roomtypes[intval($row['id'])] = $forSelect ? "{$row['name']} ({$row['capacity']})" : $row;
			if (!$forSelect)
				unset($roomtypes[$row['id']]['id']);
		}
		return $roomtypes;
}


function findNonights($arive, $leave){
	return intval((strtotime($leave) - strtotime($arive))/86400);
}


?>
