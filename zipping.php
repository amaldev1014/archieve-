<?php

// http://localhost/archives/zipping.php
// C:\xampp\htdocs\archives\search_archive

/**
 * Retrieve orderIds from {PLATFORM}_orders tables if dates older than 'X' months.
 * Using orderIds, SELECT records for {PLATFORM}_orders and {PLATFORM}_items.
 * Save to *.dat files and insert orderID, buyer, date, postcode to search_archive.db3.
 * Compress (zip) file, then delete file.
 * 
 * System Fetch all records from platform_orders for last 3 months using ids from platform_orders
 */



/*
amazon_orders
amazon_items
ebay_orders
ebay_items
ebay_prosalt_orders
ebay_prosalt_items
etc
 */

/*=========================================================================
| FUNCTIONS
|========================================================================*/

include_once('functions.php');

// Object creation and path definition
$path = 'C:/xampp/htdocs/';
$db_search_archive = new PDO('sqlite:'.$path.'archives/search_archive.db3');
$db_sa = new PDO('sqlite:search_archive.db3');
$db_api = new PDO('sqlite:api_orders.db3');

// platform names
$platforms = [
    'amazon_items' => 'amazon_orders',
    'ebay_items' => 'ebay_orders',
    'ebay_prosalt_items' =>  'ebay_prosalt_orders',
    'floorworld_items' =>  'floorworld_orders',
    'onbuy_items' => 'onbuy_orders',
    'website_items' =>  'website_orders'
];

//operation to be performed
$operation = [
    'backup' => TRUE,
    // 'remove_cache_orders' => TRUE,
];


$items_rec;
$item = [];
$order = [];
if($operation['backup']){
    
    // date from which records will be deleted
    $startDate = date('Y-m-d', strtotime(date('Y-m-1'). ' - 3 month') );

    // Fetch all OrderIds from platform orders, records from platform orders and platform items    
    foreach ($platforms as $pf_items => $pf_orders) {
        
        // Query to bring orderIds
        $sql = "SELECT orderId FROM '$pf_orders' WHERE date < '$startDate'";  
        $order_ids = $db_api->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        $order_ids_str = implode("','",$order_ids);
        
        // Query to bring all records from platform orders
        $sql = "SELECT * FROM '$pf_orders' WHERE orderId IN ('$order_ids_str')";
        $order = $db_api->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
        back_up($order,$pf_orders);
        
        //Saving platform items for database insertion
        $items_rec = $item; 
        
        // Query to bring all records from platform items 
        $sql = "SELECT * FROM '$pf_items' WHERE orderId IN ('$order_ids_str')";
        $item = $db_api->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        back_up($item,$pf_items);    
    }

    $dat_rec = [];
    $stmt = $db_search_archive->prepare("INSERT INTO `search_archive` (`id`,`orderID`,`buyer`,`date`,`postcode`) VALUES (?,?,?,?,?)");

    // Assign required data to variables and finally execute insert operation to 'search_archive' table
    $db_search_archive->beginTransaction();
    foreach ($items_rec as $rec) {
        $orderID = $rec['orderId'];
        
        $date = substr($rec['date'],0,10); 
        $date = str_replace('-', '', $date);
        
        $year_month = substr($date, 0,6);
        $year_month = substr($startDate, 0,7);
        $year_month = str_replace('-', '', $year_month);
        
        $buyer = $rec['buyer'];
        $buyer = preg_replace('/\s+/', ' ', $buyer);
         
        $postCode = $rec['postcode'];
        $postCode = preg_replace('/\s+/', '', $postCode);
        
        $dat_rec[] = "$orderID\t$buyer\t$date\t$postCode";
        
        $insert_arr = [ $year_month, $orderID, $buyer, $date, $postCode ];
        
        // $stmt->execute($insert_arr);
    }
    $db_search_archive->commit();
}
