<?php

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Controllers/Admin/DashboardController.php";

(new DashboardController())->index();