<?php
if (!isset($_POST) || empty($_POST)) {
    die("No direct access to this page!");
}
$includeScripts = array('__constants.php', '_db_query.php', 'class.back-end.php');
include_once(dirname(__FILE__) . "/../../__lib/bootstrap.php");
extract($_POST);
$pk = $_POST['key'];
$canEdit = (isset($_POST['edit']) && boolval($_POST['edit']) != false) ? $_POST['edit'] : false;
$canDelete = (isset($_POST['delete']) && boolval($_POST['delete']) != false) ? $_POST['delete'] : false;
$params = isset($_POST['params']) ? $_POST['params'] : false;
$formAction = $_POST['formAction'];
if (isset($_POST["t"])) {
    $table = base64_decode($_POST["t"]);
    $rawTable = $table;
    if (false !== strpos($rawTable, "AS")) {
        $tableNameParts = explode("AS", $rawTable);
        $rawTable = trim($tableNameParts[0]);
    }
    if (defined("TBL_PREFIX") && trim(TBL_PREFIX) != "") {
        if (false !== strpos($rawTable, TBL_PREFIX)) {
            $tableNameParts = explode(TBL_PREFIX, $rawTable);
            $rawTable = trim(end($tableNameParts));
        }
    }
    if (defined("TBL_SUFFIX") && trim(TBL_SUFFIX) != "") {
        if (false !== strpos($rawTable, TBL_SUFFIX)) {
            $tableNameParts = explode(TBL_SUFFIX, $rawTable);
            $rawTable = trim($tableNameParts[0]);
        }
    }
}

/**
 * Commonly used functions for client end
 */
function setTimestamp($cellData, $index, $rowData, $pk) {
    return date("jS F, Y \@ H:i:s", strtotime($cellData));
}
function hasEditOption($p) {
    global $canEdit, $canDelete;
    if (is_array($p) && isset($p[0]) && intval($p[0])) {
        $d = intval($p[0]);
    } else {
        $d = $p;
    }
    $actionBtn = "";
    if ($canEdit !== false) {
        $actionBtn = '<a href="' . $canEdit . '?edit=' . $d . '" class="btn btn-primary">Redigera</a>';
    }
    return $actionBtn;
}
function hasDeleteOption($p) {
    global $rawTable, $formAction, $pk, $canDelete;
    if (is_array($p) && isset($p[0]) && intval($p[0])) {
        $d = intval($p[0]);
    } else {
        $d = $p;
    }
    $actionBtn = "";
    if ($canDelete !== false) {
        $actionBtn = '<form action="' . $formAction . '" class="form-horizontal pull-right" method="post" onsubmit="return confirm(\'Are you sure to delete?\')">
                <input type="hidden" name="action" value="' . base64_encode('request-model') . '">
                <input type="hidden" name="keyName" value="' . $pk . '">
                <input type="hidden" name="excl_table" value="' . $rawTable . '">
                <input type="hidden" name="excl_operation" value="delete">
                <input type="hidden" name="keyValue" value="' . $d . '">
                <input type="hidden" name="proc" value="' . uniqid() . '">
                <button type="submit" name="deleteFields" class="btn btn-danger">Delete</button>
            </form>';
    }
    return $actionBtn;
}
// $_POST["fields"] = base64_encode(serialize($fieldsParts));
// SQL server connection information
$sql_details = array(
    'user' => DB_USER,
    'pass' => DB_PASS,
    'db'   => DB_NAME,
    'host' => DB_HOST
);
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * If you just want to use the basic configuration for DataTables with PHP
     * server-side, there is no need to edit below this line.
     */
require(dirname(__FILE__) . '/ssp.class.php');
echo json_encode(
    SSP::complex($_POST, $sql_details, $table, $pk)
    // SSP::complex( $_POST, $sql_details, $table, $pk, $columns, $whereCaluse, $joinedTables )
    //        SSP::simple( $_POST, $sql_details, $table, $pk, $columns )
);
