<?php
session_start();
require_once("Database.php");
//require_once("Guid.php");
$database = new Database();

ini_set('display_errors', 1);

// Report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Reporting E_NOTICE can be good too (to report uninitialized
// letiables or catch letiable name misspellings ...)
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

// Report all errors except E_NOTICE
error_reporting(E_ALL & ~E_NOTICE);

// Report all PHP errors (see changelog)
error_reporting(E_ALL);

// Report all PHP errors
error_reporting(-1);

// Same as error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);


function getGUID()
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((float) microtime() * 10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = chr(123) // "{"
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125); // "}"

        $uuid = trim($uuid, "{");
        $uuid = trim($uuid, "}");
        return $uuid;
    }
}


function get_client_ip()
{
    $ipaddress = '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if (isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if (isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
//$test = getGUID();
$ip = get_client_ip();

if (isset($_SESSION['totalSubmit']) && $_SESSION['totalSubmit'] > 15) {
    header('Location:thankYou.php');
    exit();
}

$performance = array('very-good' => 5, 'good' => 4, 'ok' => 3, 'bad' => 2, 'very-bad' => 1);
$questions = array(
    "q1" => "",
    "q2" => "",
    "q3" => "",
    "q4" => "",
    "q5" => "",
    "q6" => "",
    "q7" => "",
    "q8" => "",
    "q9" => "",
    "q10" => "",
    "q11" => "",
    "q12" => ""
);
$not_valid_answer = false;
//$same_response = '';
$class_id = $classType_id = $subject_id = $faculty_id = $batch_id = $comments = '';
if (
    $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['class']) && isset($_POST['classType']) &&
    isset($_POST['subject']) && isset($_POST['faculty']) && $_POST['class'] != -1 && $_POST['classType'] != -1 &&
    $_POST['subject'] != -1 && $_POST['faculty'] != -1 && isset($_POST["q1"]) && isset($_POST["q2"]) &&
    isset($_POST["q3"]) && isset($_POST["q4"]) && isset($_POST["q5"]) && isset($_POST["q6"]) && isset($_POST["q7"])
    && isset($_POST["q8"]) && isset($_POST["q9"]) && isset($_POST["q10"]) && isset($_POST["q11"])
    && isset($_POST["q12"]) && isset($_POST['semester'])
) {
    $_SESSION['semester_id'] = $semester_id = (filter_var($_POST['semester'], FILTER_VALIDATE_INT)) ? (int) $_POST['semester'] : -1;
    $_SESSION['class_id'] = $class_id = (filter_var($_POST['class'], FILTER_VALIDATE_INT)) ? (int) $_POST['class'] : -1;
    $_SESSION['classType_id'] = $classType_id = (filter_var($_POST['classType'], FILTER_VALIDATE_INT)) ? (int) $_POST['classType'] : -1;
    $faculty_id = (filter_var($_POST['faculty'], FILTER_VALIDATE_INT)) ? (int) $_POST['faculty'] : -1;
    $subject_id = (filter_var($_POST['subject'], FILTER_VALIDATE_INT)) ? (int) $_POST['subject'] : -1;
    //print_r($_SESSION);

    $guid = getGUID();
    $not_valid_answer = false;
    foreach (array_keys($questions) as $question) {
        $temp = filter_var($_POST[$question], FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^(very-good|good|ok|bad|very-bad)$/i']]);
        if ($temp == -1) {
            $not_valid_answer = true;
            break;
        }
        $questions[$question] = $performance[$temp ? $temp : -1];
    }
    $_SESSION['totalSubmit'] = $total_submit = $database->getData(("SELECT COUNT(ip) AS total FROM feedback WHERE ip = '" . $ip . "'"))[0]['total'];


    $_SESSION['same_response'] = (bool)$database->getData('select if(count(*) > 0, 1,0) as result from feedback inner join faculty_class_subject as fcs 
                        on feedback.fcs_id =  fcs.fcs_id where fcs.class_id = ' . $class_id . " and fcs.subject_id = " . $subject_id .
        " and fcs.faculty_id = " . $faculty_id . " and fcs.classType_id = " . $classType_id . " and ip = '" . $ip . "'")[0]['result'];
    if (!$not_valid_answer && !$_SESSION['same_response']) {
        $sql = "select fcs_id from faculty_class_subject where class_id = " . $class_id . " and classType_id = " .
            $classType_id . " and faculty_id = " . $faculty_id . " and subject_id = " . $subject_id;
        if ($classType_id == 2 && isset($_POST['batch'])) {
            $_SESSION['batch_id'] = $batch_id = (filter_var($_POST['batch'], FILTER_VALIDATE_INT)) ? (int) $_POST['batch'] : -1;
            $sql .= " and batch_id = " . $divission_id;
        }
        $fcs_id = (int) $database->getData($sql)[0]['fcs_id'];
        $insert_performance = "insert into Feedback(feedback_id,fcs_id,ip) value(:feedback_id,:fcs_id,:ip)";
        $params_feedback = array(':fcs_id' => $fcs_id, ':feedback_id' => $guid, ':ip' => $ip);
        if (isset($_POST['comments'])) {
            $comments = filter_var($_POST['comments'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $insert_performance = "insert into Feedback(feedback_id,fcs_id,ip,comments) value(:feedback_id,:fcs_id,:ip,:comments)";
            $params_feedback[':comments'] = $comments;
        }

        $stmt = $database->prepare($insert_performance);
        $database->execute($stmt, $params_feedback);

        $insert_performance_Feedback = "insert into Performance_Feedback(performance_id,performance_grade,feedback_id)
                                        value(:performance_id,:performance_grade,:feedback_id)";
        $stmt_performance_Feedback = $database->prepare($insert_performance_Feedback);
        $params_performance_Feedback = array(':performance_id' => '', ':performance_grade' => '', ':feedback_id' => $guid);
        foreach (array_keys($questions) as $key) {
            $params_performance_Feedback[':performance_id'] = (int) substr($key, 1);
            $params_performance_Feedback[':performance_grade'] = $questions[$key];
            $database->execute($stmt_performance_Feedback, $params_performance_Feedback);
        }
    }
    header("Location:" . $_SERVER['PHP_SELF'] /*. "?semid=" . $_SESSION['semester_id']*/);
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/assets/images/logo.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Ysabeau:wght@300&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="shortcut icon" href="./assets/images/logo.png">
    <title>Faculty FeedBack Form</title>

    <script src="https://code.jquery.com/jquery-2.1.1.min.js" type="text/javascript"></script>
</head>

<body>
    <div class="img">
        <a href="http://www.rcti.cteguj.in/" target="_blank">
            <img src="./assets/images/logo.png" alt="Logo" style="height:100px;width:100px;">
        </a>
    </div>
    <nav>
        <div class="heading">
            <h2><b>Faculty FeedBack Form</b></h2>
        </div>
    </nav>
    <?php
    if ($not_valid_answer)
        echo '<p style="color : red; font-size : 20px; font-weight:bold;">Please Don\' manipulate with developer tools</p>';
    else if (isset($_SESSION['same_response']) && $_SESSION['same_response'])
        echo '<p style="color : red; font-size : 20px; font-weight:bold;">You can\'t submit same responce again. Kindly choose different subject</p>';
    ?>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER[" PHP_SELF"]); ?>" onload="checkOnLoad()">
        <div class="DropDown">
            <div class="selection">
                <div class="selec">
                    <select id="semester" name="semester" onchange="getClass()" required>
                        <!-- TODO :  -->
                        <option value="-1">Select a Semester</option>
                        <?php
                        $temp = '';
                        if (isset($_SESSION['semester_id'])) {
                            $sql_query_semester = "select semester_no from semester where semester_id = " . $_SESSION['semester_id'];
                            $semester_no = $database->getData($sql_query_semester)[0]['semester_no'];
                            echo "<option value='" . $_SESSION['semester_id'] . "'>" . $semester_no . "</option>";
                            $temp = $_SESSION['semester_id'];
                        } else
                            echo '<option value="-1">Select a Semester</option>';

                        $semesters = $database->getData("SELECT * FROM Semester");
                        foreach ($semesters as $semesterRow) {
                            if ($semesterRow['semester_id'] != $temp)
                                echo '<option value="' . $semesterRow['semester_id'] . '">' . $semesterRow['semester_no'] . "</option>";
                        }
                        ?>
                    </select>
                    <select id="class" name="class" onchange="getbatch()" required>
                        <?php
                        if (isset($_SESSION['class_id'])) {
                            $sql_query_class = "select class_name from class where class_id = " . $_SESSION['class_id'];
                            $class_name = $database->getData($sql_query_class)[0]['class_name'];
                            echo "<option value=" . $_SESSION['class_id'] . ">" . $class_name . "</option>";
                        } else
                            echo '';
                        ?>
                        <option value="-1">Select a class</option>
                    </select>
                    <select id="classType" name="classType" onchange="getbatch()">
                        <option value="-1">Select a Class Type</option>
                        <?php
                        $classTypes = $database->getData("SELECT * FROM ClassType order by classType_id");
                        foreach ($classTypes as $classType) {
                            echo '<option value="' . $classType['classType_id'] . '">' . $classType['classType_name'] . "</option>";
                        }
                        ?>
                    </select>
                    <!-- <div class="selec"> -->
                    <select id="batch" name="batch" class="none" required onchange="getSubject()">
                        <option value="-1">Select a batch</option>
                    </select>
                    <!-- </div> -->
                    <!-- <div class="selec"> -->
                    <select id="subject" name="subject" onchange="getFaculty()">
                        <option value="-1">Select a Subject</option>
                    </select>
                    <!-- </div> -->
                    <!-- <div class="selec"> -->
                    <select id="faculty" name="faculty">
                        <option value="-1">Select a faculty</option>
                    </select>
                    <!-- </div> -->
                </div>
            </div>
        </div>
        <div class="frm">
            <div class="questions">
                <div class="qu">
                    <p class="question">Has the faculty covered entire syllabus as prescribed by College/Board?</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q1" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q1" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q1" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q1" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q1" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>
                <div class="qu">
                    <p class="question">Has the teacher covered relevant topics beyond syllabus?</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q2" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q2" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q2" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q2" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q2" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>
                <div class="qu">
                    <p class="question">Effectiveness of Faculty in terms of Technical Content/Course content</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q3" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q3" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q3" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q3" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q3" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>
                <div class="qu">
                    <p class="question">Effectiveness of Faculty in terms of Communication Skills</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q4" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q4" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q4" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q4" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q4" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>

                <div class="qu">
                    <p class="question">Effectiveness of Faculty in terms of Use of Teaching aids</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q5" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q5" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q5" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q5" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q5" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>
                <div class="qu">
                    <p class="question">Pace on which content were covered</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q6" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q6" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q6" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q6" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q6" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>
                <div class="qu">
                    <p class="question">Motivation and inspiration for students to learn</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q7" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q7" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q7" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q7" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q7" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>
                <div class="qu">
                    <p class="question">Support for the development of students skill Practical demonstration</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q8" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q8" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q8" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q8" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q8" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>
                <div class="qu">
                    <p class="question">Support for the development of students skill Hands on training</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q9" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q9" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q9" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q9" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q9" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>
                <div class="qu">
                    <p class="question">Clarity of expectation of students</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q10" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q10" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q10" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q10" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q10" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>
                <div class="qu">
                    <p class="question">Feedback provided on students progress</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q11" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q11" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q11" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q11" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q11" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>

                <div class="qu">
                    <p class="question">Feedback provided on students progress</p>
                    <div class="answer-options">
                        <label>
                            <input type="radio" name="q12" value="very-good" id="very-good" required>
                            Very Good
                        </label>
                        <label>
                            <input type="radio" name="q12" value="good" id="good">
                            Good
                        </label>
                        <label>
                            <input type="radio" name="q12" value="ok" id="ok">
                            OK
                        </label>
                        <label>
                            <input type="radio" name="q12" value="bad" id="bad">
                            Bad
                        </label>
                        <label>
                            <input type="radio" name="q12" value="very-bad" id="very-bad">
                            Very Bad
                        </label>
                    </div>
                </div>
                <textarea id="comments" name="comments" placeholder="Enter any additional commnets"></textarea>
            </div>

        </div>

        <div class="submit">
            <button type="submit" class="btn" name="submit">Submit</button>
        </div>
    </form>

    <script>
        document.getElementById('classType').addEventListener("change", () => {
            const batch = document.getElementById("batch");
            batch.classList.add("none");
            if ($('#classType').val() == 2) {
                batch.classList.remove("none");
            }
        });

        function onClassType() {
            if ($('#classType').val() == 2) {
                getbatch();
            } else {
                getSubject();
            }
        }

        function getClass() {
            let semesterId = document.getElementById("semester").value;
            //alert(semesterId);
            let url = "getClassFromSemester.php";
            let params = "semesterId=" + semesterId;
            let http = new XMLHttpRequest();
            document.getElementById('class').innerHTML = "<option value='-1'>Select Class</option>";
            //document.getElementById('subject').innerHTML = "<option value='-1'>Select Subject</option>";
            document.getElementById('batch').innerHTML = "<option value='-1'>Select batch</option>";
            batch.classList.add("none");
            //document.getElementById('Faculty').innerHTML = "<option value='-1'>Select Faculty</option>";
            http.open("GET", url + "?" + params, true);
            http.onreadystatechange = () => {

                if (http.readyState == 4 && http.status == 200) {
                    let res = JSON.parse(http.responseText);
                    for (cnt = 0; cnt < res.length; cnt++) {
                        let opt = res[cnt].class_id;
                        let disp = res[cnt].class_name;
                        $('#class').append($('<option>').attr("value", opt).text(disp));
                    }
                    onClassType();
                }
            }
            http.send(null);
        }

        function getbatch() {
            let classId = document.getElementById("class").value;
            let url = "getbatchFromClass.php";
            let params = "class_id=" + classId;

            $('#batch').empty();
            document.getElementById('batch').innerHTML = "<option value='-1'>Select batch</option>"
            $('#faculty').empty();
            document.getElementById('faculty').innerHTML = "<option value='-1'>Select Faculty</option>";
            //  debugger;
            if ($("#classType").val() == 2) {
                let http = new XMLHttpRequest();
                http.open("GET", url + "?" + params, true);
                http.onreadystatechange = () => {
                    //debugger;

                    if (http.readyState == 4 && http.status == 200) {
                        //console.log(http.responseText);
                        let res = JSON.parse(http.responseText);
                        for (cnt = 0; cnt < res.length; cnt++) {
                            let opt = res[cnt].batch_id;
                            let disp = res[cnt].batch_name;
                            $('#batch').append($('<option>').attr("value", opt).text(disp));
                        }

                        $('#subject').empty();
                        document.getElementById('subject').innerHTML = "<option value='-1'>Select Subject</option>";


                        /*if ($('#classType').value == 2) getSubject();*/
                    }
                }
                http.send(null);
            } else {
                getSubject();
            }
        }

        function getSubject() {
            let classId = document.getElementById("class").value;
            let classTypeId = document.getElementById("classType").value;
            let url = "getSubjectFromSemester.php";
            // debugger;
            let params = "class_id=" + classId + "&classType_id=" + classTypeId;
            let http = new XMLHttpRequest();
            //console.log($('#classType').value);
            $('#faculty').empty();
            document.getElementById('faculty').innerHTML = "<option value='-1'>Select Faculty</option>";
            http.open("GET", url + "?" + params, true);
            http.onreadystatechange = () => {

                if (http.readyState == 4 && http.status == 200) {
                    //console.log(http.responseText);
                    //  debugger;
                    $('#subject').empty();
                    document.getElementById('subject').innerHTML = "<option value='-1'>Select Subject</option>";

                    if (http.responseText.length > 0) {
                        let res = JSON.parse(http.responseText);

                        //debugger;
                        //console.log(res);
                        for (cnt = 0; cnt < res.length; cnt++) {
                            $('#subject').append($('<option>').attr("value", res[cnt].subject_id).text(res[cnt].subject_name));
                        }
                    }

                }
            }
            http.send(null);
        }

        function getFaculty() {
            //  debugger;
            let cls = document.getElementById('class').value;
            let classType = document.getElementById('classType').value;
            let subject = document.getElementById('subject').value;
            let batch = document.getElementById('batch').value;

            let url = "getFaculty.php";
            let params = "class_id=" + cls + "&classType_id=" + classType + "&subject_id=" + subject;

            params += (classType == 2) ? "&batch_id=" + batch : "&batch_id=" + '-1';
            /*debugger;*/
            $('#faculty').empty();
            document.getElementById('faculty').innerHTML = "<option value='-1'>Select Faculty</option>";

            let http = new XMLHttpRequest();
            http.open("GET", url + "?" + params, true);
            http.onreadystatechange = () => {
                if (http.readyState == 4 && http.status == 200) {
                    //console.log(http.responseText);
                    //debugger;
                    let res = JSON.parse(http.responseText);
                    //debugger;
                    $('#faculty').empty();
                    for (cnt = 0; cnt < res.length; cnt++) {
                        //console.log(res[cnt]);
                        $('#faculty').append($('<option>').attr("value", res[cnt].faculty_id).text(res[cnt].faculty_name));
                    }
                }
            }
            http.send(null);
        }
    </script>
</body>

</html>