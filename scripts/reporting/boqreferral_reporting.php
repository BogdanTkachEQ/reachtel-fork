#!/usr/bin/php
<?php

define("SKIP_IP_CHECK", true);

require_once("boqportals/api.php");

// Send reminders
if((date("N") < 6) AND (date("G") == 10)) {

	boqreferral_dailyreminders();
}

// End of day reporting
if(date("G") == 3){

        $periodstart = date("Y-m-d 00:00:00", strtotime("yesterday"));
        $periodfinish = date("Y-m-d 23:59:59", strtotime("yesterday"));

        boqreferral_dailyreporting($periodstart, $periodfinish);

}

// End of week reporting
if((date("G") == 3) AND (date("D") == "Sun")){

        $startdate = time() - 604800; // Yesterday and then a week ago
        $enddate = time() - 86400;

        $periodstart = date("Y-m-d", $startdate) . " 00:00:00";
        $periodfinish = date("Y-m-d", $enddate) . " 23:59:59";

        boqreferral_dailyreporting($periodstart, $periodfinish);
}

// End of month reporting
if((date("G") == 3) AND (date("j") == "1")){

        $date = time() - 86400;

        $periodstart = date("Y-m-01", strtotime("yesterday"));
        $periodfinish = date("Y-m-t", strtotime("yesterday"));

        $reportingaddress[] = "Jason.Ryan@boqfinance.com.au";

        boqreferral_dailyreporting($periodstart, $periodfinish);


}

?>
