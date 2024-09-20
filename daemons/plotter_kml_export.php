<?php

require_once(__DIR__ . '/../api.php');
require_once(__DIR__ . '/../api_queue_gearman.php');

api_queue_gearman_worker([QUEUE_NAME_PLOTTER_KML_EXPORT]);
