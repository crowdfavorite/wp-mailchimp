<?php
require('sopresto.php');
$public = 'XXX';
$secret = 'YYY';
$version = '1.3'; // or '2.0'

$sopresto = new Sopresto_MailChimp($public, $secret, $version);

if ( $version == '2.0' )  {
    // First underscore will be changed for a slash. 
    // Remaning underscores will be changed by a dash
    // Ie.- to call 'helper/search-members' you should use $sopresto->helper_search_members()

    $response = $sopresto->campaigns_list(array(), 0, 2);
} else {
    $response = $sopresto->campaigns(array(), 0, 2);
}

print_r($response);

