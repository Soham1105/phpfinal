 <?php
require_once("Database.php");
$db_handle = new Database();
 if (isset($_GET['class_id']) && isset($_GET["classType_id"])) {
     $class_id = $_GET["class_id"];
     $classType = $_GET["classType_id"];
     if ($classType == 1)
         $query = "select fcs.subject_id, Subject.subject_name
                from faculty_class_subject as fcs
                inner join Subject on fcs.subject_id = Subject.subject_id
                where class_id = ".$class_id." and classType_id = " . $classType;
     else
          $query = "select distinct fcs.subject_id, Subject.subject_name
                    from faculty_class_subject as fcs
                    inner join Subject on fcs.subject_id = Subject.subject_id
                    where class_id = ".$class_id." and classType_id = ". $classType;
     $results = $db_handle->getData($query);
     echo json_encode($results);
 }
 $db_handle->__destruct();
?>