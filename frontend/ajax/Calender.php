<?php
require_once('../../../../../wp-config.php');
require_once('../calender.php');
$calendar = new matrixCalender();
$calendar->showCalendarMatrix($startStamp,$endingStamp, getRentalFullDetails(),$_GET['noNights']);
?>
