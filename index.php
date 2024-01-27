<?php
require_once 'Safilo.php';

// Create an instance of the spider and start scraping
$spider = new SafiloSpider();
$spider->startRequests();
$spider->saveDataToFile();
?>
