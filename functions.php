<?php
/**
 * [back_up description]
 * @param  Platform array consist of all records from platform orders or items 
 * @param  Table name contains the name of platform
 * @return None
 */
function back_up($pf_arr, $tbl_name, $path='zip_files/')
{
    $tmp = [];
    
    // multi_dimensional array to single array conversion
    foreach ($pf_arr as $rec) {
        $tmp[] = implode("\t", $rec);
    }
    
    // Array ti string conversion  
    $dat_rec_str = implode("\n", $tmp);
    
    // Add contents to .dat file
    $file = $path.'api_orders_backup_'. $tbl_name;
    if (file_put_contents($file.'.dat', $dat_rec_str)) {
        echo 'file created for '. $tbl_name.'<br>';
    }
     
    // Add .dat file to zip archive
    $zip = new ZipArchive;
    $status = $zip->open($file.'.zip',  ZipArchive::CREATE);
    
    if ($zip->addFile($file.'.dat')) {
        echo 'file zipped for ' . $tbl_name.'<br>';
    }
    
    $zip->close();
    
    // Delete .dat file
    unlink($file.'.dat');
}