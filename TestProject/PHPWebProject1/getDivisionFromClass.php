 <?php
require_once("Database.php");
$db_handle = new Database();
 if (isset($_GET['class_id'])) {
     $var = $_GET["class_id"];
     $query = "SELECT division_id, division_name FROM Division WHERE class_Id = " . $var;
     $results = $db_handle->getData($query);
     echo json_encode($results);
 }
 $db_handle->__destruct();
?>