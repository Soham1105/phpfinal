 <?php
require_once("Database.php");
$db_handle = new Database();
 if (isset($_GET['class_id']) && isset($_GET['subject_id']) && isset($_GET['classType_id'])){
     $classId = $_GET['class_id'];
     $subject_id = $_GET['subject_id'];
     $classType_id = $_GET['classType_id'];
     $query = "SELECT fcs.faculty_id, Faculty.faculty_name FROM 
            Faculty_Class_Subject as fcs inner join Faculty on fcs.faculty_id = Faculty.faculty_id
            WHERE class_Id = " . $classId . " and fcs.subject_id=" . $subject_id . " and classType_id=" . $classType_id;
     if ($classType_id == 2 && isset($_GET['division_id'])) {
         $division_id = $_GET['division_id'];
         $query .= " and division_id=" . $division_id;
     }
     $results = $db_handle->getData($query);
     echo json_encode($results);
 }
 $db_handle->__destruct();
?>