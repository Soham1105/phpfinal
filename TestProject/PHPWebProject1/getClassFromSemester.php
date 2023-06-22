 <?php
 //session_start();
require_once("Database.php");
$db_handle = new Database();
 if (!empty($_GET['semesterId'])) {
     $semesterId = $_GET["semesterId"];
     $query = "SELECT class_id, class_name FROM class WHERE semester_Id = " . $semesterId;
     //if(isset($_SESSION['class_id'])) $query .= ' and class_id != ' . $_SESSION['class_id'];
     $results = $db_handle->getData($query);
     echo json_encode($results);
 }
 $db_handle->__destruct();
?>