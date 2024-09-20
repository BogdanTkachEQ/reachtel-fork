<?php

require_once("Morpheus/api.php");
require_once("Morpheus/api_queue_gearman.php");

array_shift($argv); // The first element is the script name so shift that off the front

api_queue_gearman_worker($argv);

?>