<?php
require_once __DIR__ . '/../common/headSecure.php';

if (!$AUTH->instancePermissionCheck("LOCATIONS:LOCATION_BARCODES:VIEW")) die($TWIG->render('404.twig', $PAGEDATA));

$DBLIB->where("locations.instances_id", $AUTH->data['instance']['instances_id']);
$DBLIB->where("locations.locations_deleted", 0);
$DBLIB->join("clients","locations.clients_id=clients.clients_id","LEFT");
$DBLIB->where("locations.locations_id",$_GET['location']);
$PAGEDATA['location'] = $DBLIB->getOne('locations', ["locations.*", "clients.clients_name"]);
if (!$PAGEDATA['location']) die($TWIG->render('404.twig', $PAGEDATA));

$DBLIB->where("locations_id",$PAGEDATA['location']['locations_id']);
$DBLIB->where("locationsBarcodes_deleted",0);
$PAGEDATA['barcode'] = $DBLIB->getone("locationsBarcodes");
if (!$PAGEDATA['barcode']) die($TWIG->render('404.twig', $PAGEDATA));

echo $TWIG->render('location/location_barcode.twig', $PAGEDATA);
?>
