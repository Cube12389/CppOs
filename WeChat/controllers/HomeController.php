<?php
// controllers/HomeController.php

class HomeController {
    public function index() {
        global $LOCALE;
        require_once 'core/Storage.php';
        
        // Pass basic data to view
        $siteName = Storage::getSetting('site_name');
        $announcement = Storage::getSetting('site_announcement');
        
        require 'views/home.php';
    }
}
