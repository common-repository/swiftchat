<?php
/*
Plugin Name: 	SwiftChat
Plugin URI:  	https://www.swiftchat.io
Description: 	To integrate webiste anlytics to SwiftChat Dashboard.
Author:      	SwiftSales
Author URI:  	https://www.swiftsales.io/
Version:     	20200309
Text Domain: 	swiftchat.io
License:     	MIT
Tested Up To: 	5.5.1
*/


if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'swift_sales_integration_plugin_class.php';

$sSIplugin  = new SwiftSalesIntegrationPlugin(__FILE__);
$sSIplugin->registerActions();
