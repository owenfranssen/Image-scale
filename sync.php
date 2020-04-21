<?php
	error_reporting(E_WARNING|E_ERROR);
	ini_set('display_errors', 'On');

require './aws/aws-autoloader.php';

/*
//
//	2016-10-25
//	WARNING!
//
//	Don't run this code regularly, as this seems to upload everything every time.
//	The issue with this is that eats into S3 PUT requests and costs money, so use liberally until we have a better set of routine code to use :)
//
//	Cheers,
//
//	Peter
//
*/

use Aws\S3\S3Client;
$client = S3Client::factory(array(
	'region' => 'eu-west-1',
	'version' => '2006-03-01',
	'signature_version' => 'v4',
	'credentials' => array(
		'key' => 'AKIAI6EQZMARXOEZXKIQ', // photos.bnbowners.com-S3-CRUD
		'secret' => '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', // photos.bnbowners.com-S3-CRUD
	)
));

$dir = './images/'; // Path the images are stored in, everything located at this path is dumped into the root path of the defined bucket
$bucket = 'photos.bnbowners.com';
$keyPrefix = '';

/*$client->uploadDirectory($dir, $bucket, $keyPrefix, array(
	'params' => array('ACL' => 'public-read'),
	'concurrency' => 20,
	'force' => false,
	'debug' => true
));*/

?>..etc