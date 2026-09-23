<?php

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Controllers/Admin/ConfiguracionController.php";

(new ConfiguracionController())->index();