<?php
require_once __DIR__ . '/../../apiHeadSecure.php';

if (!$AUTH->instancePermissionCheck(43) or !isset($_POST['assetsAssignments'])) die("404");

$assignmentsSetDiscount = new assetAssignmentSelector($_POST['assetsAssignments']);
$assignmentsSetDiscount = $assignmentsSetDiscount->getData();
if (!$assignmentsSetDiscount['projectid']) finish(false,["message"=>"Cannot find projectid"]);
$DBLIB->where("projects.projects_id", $assignmentsSetDiscount["projectid"]);
$DBLIB->where("projects.instances_id IN (" . implode(",", $AUTH->data['instance_ids']) . ")");
$DBLIB->where("projects.projects_deleted", 0);
$project = $DBLIB->getone("projects",["projects_id","projects_dates_deliver_start","projects_dates_deliver_end"]);
if (!$project) finish(false,["message"=>"Cannot find project"]);

$projectFinanceHelper = new projectFinance();
$priceMaths = $projectFinanceHelper->durationMaths($project['projects_dates_deliver_start'],$project['projects_dates_deliver_end']);
$projectFinanceCacher = new projectFinanceCacher($project['projects_id']);

foreach ($assignmentsSetDiscount["assignments"] as $assignment) {
    $DBLIB->where("assetsAssignments_id", $assignment['assetsAssignments_id']);
    if (!$DBLIB->update("assetsAssignments", ["assetsAssignments_discount" => $_POST['assetsAssignments_discount']])) finish(false);
    else {
        $bCMS->auditLog("EDIT-DISCOUNT", "assetsAssignments", $assignment['assetsAssignments_id'], $AUTH->data['users_userid'],null, $assignment['projects_id']);

        if ($assignment['assetsAssignments_customPrice'] > 0) {
            $price = $assignment['assetsAssignments_customPrice'];
        } else {
            $priceChange = 0.0;
            $priceChange += ($priceMaths['days'] * $assignment['assetTypes_dayRate']);
            $priceChange += ($priceMaths['weeks'] * $assignment['assetTypes_weekRate']);
            $price = round($priceChange, 2, PHP_ROUND_HALF_UP);
        }
        if ($assignment['assetsAssignments_discount'] > 0) {
            //If there was already a discount, remove it
            $projectFinanceCacher->adjust('projectsFinanceCache_equiptmentDiscounts', -1*($price-(round(($price * (1 - ($assignment['assetsAssignments_discount'] / 100))), 2, PHP_ROUND_HALF_UP))));
        }
        if ($_POST['assetsAssignments_discount'] > 0) {
            //If there is now a discount, set it up
            $projectFinanceCacher->adjust('projectsFinanceCache_equiptmentDiscounts', $price-(round(($price * (1 - ($_POST['assetsAssignments_discount'] / 100))), 2, PHP_ROUND_HALF_UP)));
        }
    }
}
if ($projectFinanceCacher->save()) finish(true);
else finish(false,["message"=>"Finance Cacher Save failed"]);