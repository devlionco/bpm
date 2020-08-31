<?php
require_once(__DIR__ . "/../../../config.php");
require_once(__DIR__ . "/../config.php");

if (isset($_POST['quizId'])) {
    global $DB;
    $quiz_id = $_POST['quizId'];
    $hours = 2;
    $timestamp = (new DateTime())->modify("+{$hours} hours")->format('U');   
    
    $sql = "UPDATE mdl_quiz SET timeclose = $timestamp WHERE id = $quiz_id";
    
    if ($DB->execute($sql)) {
        $visible = "UPDATE mdl_course_modules SET visible = 1 WHERE module = 16 AND instance = $quiz_id";
        if ($DB->execute($visible)) {
            rebuild_course_cache($_POST['courseid'], false);
            echo 'all good';
        } else {
            echo 'error!';
        }
    } else {
        echo 'error!';
    }

} else {
    // var_dump($_SESSION['quizmodules']);
    $_SESSION['quizmodules'] = urlencode(json_encode($_SESSION['quizmodules']));
    $js_loader = "<script>window.addEventListener('load',  function() {var quiz_start = document.createElement('script');
                                        quiz_start.setAttribute('src','https://my.bpm-music.com/blocks/bpm_utils/start_quiz/start_quiz.js');
                                        quiz_start.setAttribute('quizmodules',\"" . $_SESSION['quizmodules'] . "\");
                                        quiz_start.setAttribute('cid',\"" . $COURSE->id . "\");
                                        document.head.appendChild(quiz_start);
                                        });
                                        </script>";
    echo $js_loader;
}
