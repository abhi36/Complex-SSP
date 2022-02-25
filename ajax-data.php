<?php
    if(!isset($_POST) || empty($_POST)){
        die("No direct access to this page!");
    }

    /**
     * A few constants to manage DB connection and params
     */
    define("DB_HOST", "localhost");
    define("DB_USER", "root");
    define("DB_PASS", "");
    define("DB_NAME", "my_database");

    define("TABLE_PRE", "tbl_");
    define("TABLE_POST", "_tbl");

    define("FORM_ACTION", $_SERVER["REQUEST_URI"]);


    $pk = $_POST['key'];
    $canEdit = (isset($_POST['edit']) && boolval($_POST['edit']) != false) ? $_POST['edit'] : false;
    $canDelete = (isset($_POST['delete']) && boolval($_POST['delete']) != false) ? $_POST['delete'] : false;
    $params = isset($_POST['params']) ? $_POST['params'] : false;
    $formAction = FORM_ACTION;

    if(isset($_POST["t"])){
        $table = base64_decode($_POST["t"]);
        $rawTable = $table;
        if(false !== strpos($rawTable, "AS")){
            $tableNameParts = explode("AS", $rawTable);
            $rawTable = trim($tableNameParts[0]);
        }
        if(defined("TABLE_PRE") && trim(TABLE_PRE) != ""){
            if(false !== strpos($rawTable, TABLE_PRE)){
                $tableNameParts = explode(TABLE_PRE, $rawTable);
                $rawTable = trim(end($tableNameParts));
            }
        }
        if(defined("TABLE_POST") && trim(TABLE_POST) != ""){
            if(false !== strpos($rawTable, TABLE_POST)){
                $tableNameParts = explode(TABLE_POST, $rawTable);
                $rawTable = trim($tableNameParts[0]);
            }
        }
    }
    

    /**
     * Commonly used functions for client end
     * 
     *  @param  string  $celldata       Data of the cell for which the callback is placed
     *  @param  int     $index          Index of the column of the fields list
     *  @param  array   $rowData        Data of the current row
     *  @param  string  $pk             Primary key provided to the SSP class
     * 
     *  @return string      Anything after formatting that particular cell data
     */
    function setTimestamp($cellData, $index, $rowData, $pk){
        return date("jS F, Y \@ H:i:s", strtotime($cellData));
    }

    function setEditOption($p){
        global $rawTable, $formAction, $backEnd, $pk, $canEdit, $canDelete;
        if(isset($p[0]) && intval($p[0])){
            $d = intval($p[0]);
        }
        $actionBtn = "";
        if($canEdit !== false){
            $actionBtn = '<a href="'.($canEdit).'?edit='.$d.'" class="btn btn-primary">Edit</a>';
        }
        return $actionBtn;
    }

    function setDeleteOption($p){
        global $rawTable, $formAction, $backEnd, $pk, $canEdit, $canDelete;
        if(isset($p[0]) && intval($p[0])){
            $d = intval($p[0]);
        }
        $actionBtn = "";
        if($canDelete !== false){
            $actionBtn = '<form action="'.$formAction.'" class="form-horizontal pull-right" method="post" onsubmit="return confirm(\'Are you sure to delete?\')">
                <input type="hidden" name="id" value="'.$pk.'">
                <input type="hidden" name="idVal" value="'.$d.'">
                <button type="submit" name="deleteData" class="btn btn-danger">Delete</button>
            </form>';
        }
        return $actionBtn;
    }

    // SQL server connection information
    $sql_details = array(
        'user' => DB_USER,
        'pass' => DB_PASS,
        'db'   => DB_NAME,
        'host' => DB_HOST
    );


    /* 
     * here is no need to edit below this line.
     */

    require( dirname(__FILE__).'/ssp.class.php' );

    echo json_encode(
        SSP::complex( $_POST, $sql_details, $table, $pk )
    );
?>
