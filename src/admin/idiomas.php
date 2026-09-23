<?php

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Controllers/Admin/IdiomasController.php";

(new IdiomasController())->index();