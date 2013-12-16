<?php

class siteutils {
    /*-------------------------------------------------------------------------------------------------
    Class contains any utils that need to be globally available
    -------------------------------------------------------------------------------------------------*/

    //Remove HTML tags from text
    public static function clean_html($data) {

        if(is_array($data)){

            foreach($data as $k => $v){
                if(is_array($v)){
                    $data[$k] = strip_tags($v);
                } else {
                    $data[$k] = strip_tags($v);
                }
            }

        } else {
            $data = strip_tags($data);
        }

        return $data;
    }

    //Get the data for the user
    public static function getuserprofile($id) {
        $q = "SELECT U.user_id, U.first_name, U.last_name, U.email
        , J.job_title, A.account_name
            FROM users U
            INNER JOIN jobs J ON J.job_id = U.job_id
            INNER JOIN accounts A ON A.account_id = U.account_id
            WHERE U.user_id = ".$id;
        return DB::instance(DB_NAME)->select_row($q);
    }

    //For use on pages that don't need all functions secured, and hence to not inherit secure_controller
    public static function redirectnonloggedinuser($sessionuserobject) {
        if (!$sessionuserobject) {
            Router::redirect("/users/login/Not_logged_in");
        }
    }

    public static function Truncate($string, $length, $stopanywhere=false) {
        //truncates a string to a certain char length, stopping on a word if not specified otherwise.
        if (strlen($string) > $length) {
            //limit hit!
            $string = substr($string,0,($length -3));
            if ($stopanywhere) {
                //stop anywhere
                $string .= '...';
            } else{
                //stop on a word.
                $string = substr($string,0,strrpos($string,' ')).'...';
            }
        }
        return $string;
    }

    //Return all the users with the given account
    public static function getUsersWithAccount($account_id) {
        $q = "SELECT U1.user_id, U1.created, U1.modified, U1.last_login, U1.time_zone
        , U1.first_name, U1.last_name, U1.email, U1.job_id, U1.account_id, U1.is_admin
        FROM users U1 WHERE U1.account_id = ".$account_id;
        $users_list = DB::instance(DB_NAME)->select_rows($q);

        return $users_list;
    }

    //Return the job_id - adding a new title if required
    public static function getJobId($job_title, $account_id) {
        $q = "SELECT job_id FROM jobs WHERE job_title = '".trim($job_title)."' AND account_id = ".$account_id;
        $job_id = DB::instance(DB_NAME)->select_field($q);
        if(!$job_id) {//add the job
            $job_data = array();
            $job_data['account_id'] =$account_id;
            $job_data['department_name'] ='';
            $job_data['job_title'] =$job_title;
            $job_id = DB::instance(DB_NAME)->insert('jobs', $job_data);
        }
        return $job_id;
    }

    public static function createUser($account_id,$job_id,$first_name,$last_name,$email,$password,$is_admin,$token=null ) {
        $user_data = array();
        # More data we want stored with the user
        $user_data['created']  = Time::now();
        $user_data['modified'] = Time::now();
        $user_data['account_id'] = $account_id;
        $user_data['is_admin'] = $is_admin;
        $user_data['job_id'] = $job_id;
        $user_data['email'] = $email;
        $user_data['first_name'] = $first_name;
        $user_data['last_name'] = $last_name;

        # Encrypt the password
        $user_data['password'] = sha1(PASSWORD_SALT.$password);

        # Create an encrypted token via their email address and a random string
        if ($token == null) {
            $token = sha1(TOKEN_SALT.$email.Utils::generate_random_string());
        }
        $user_data['token'] = $token;

        # Insert this user into the database
        $user_id = DB::instance(DB_NAME)->insert('users', $user_data);

    }
    //For a given test (and optionaly user) send back the assigned status
    public static function getTestAssignStatus($test_id, $user_id = null, $assign_status_id = null) {
        $q = "SELECT U.user_id, U.first_name, U.last_name, U.email, TA.assigned_on_dt
            , TA.due_on_dt,TA.test_assign_id, TA.test_assign_status_id
            , S.test_assign_status_descr
            FROM users U
            LEFT JOIN test_assign_user TA ON TA.user_id = U.user_id AND TA.test_id = ".$test_id."
            LEFT JOIN test_assign_status S ON S.test_assign_status_id = TA.test_assign_status_id
            WHERE U.account_id = (SELECT account_id FROM tests WHERE test_id = ".$test_id.")";
            if ($user_id != null) {
               $q = $q." AND U.user_id = ".$user_id;
            }
            if ($assign_status_id != null) {
                $q = $q." AND TA.assign_status_id = ".$assign_status_id;
            }
        $q = $q." ORDER BY U.last_name, U.first_name";
        $assign_status = DB::instance(DB_NAME)->select_rows($q);

        return $assign_status;
    }

    //For a given test (and optionaly user) send back the assigned status
    public static function getTestsAssigedToUser($user_id, $assign_status_id = null) {
        $q = "SELECT U.user_id, U.first_name, U.last_name, U.email, TA.assigned_on_dt
            , TA.due_on_dt,TA.test_assign_id, TA.test_assign_status_id
            , S.test_assign_status_descr
            , T.test_name, T.test_descr, T.test_category
            FROM users U
            INNER JOIN test_assign_user TA ON TA.user_id = U.user_id
            INNER JOIN test_assign_status S ON S.test_assign_status_id = TA.test_assign_status_id
            INNER JOIN tests T ON T.test_id = TA.test_id
            WHERE U.user_id =".$user_id." AND T.deleted <> 1";
        if ($assign_status_id != null) {
            $q = $q." AND TA.test_assign_status_id <= ".$assign_status_id;
        }
        $q = $q." ORDER BY U.last_name, U.first_name";
        $assign_status = DB::instance(DB_NAME)->select_rows($q);
        return $assign_status;
    }

    //for a test instance get the summary information
    public static function getTestInstanceSummary($test_instance_id) {
        $q = "SELECT  TI.test_instance_id, TA.assigned_on_dt
            , TA.due_on_dt,TA.test_assign_id, TA.test_assign_status_id
            , T.test_name, T.test_descr, T.test_category, T.minutes_to_complete
            , Q.question_id, Q.question_text, Q.question_order
            FROM test_instance TI
            INNER JOIN test_assign_user TA ON TA.test_assign_id = TI.test_assign_id
            INNER JOIN tests T ON T.test_id = TA.test_id
            INNER JOIN questions Q ON Q.test_id = T.test_id
            WHERE TI.test_instance_id =".$test_instance_id." AND T.deleted <> 1
            ORDER BY Q.question_order";

        $instance_details = DB::instance(DB_NAME)->select_rows($q);

        return $instance_details;
    }

    //For the given test_assign_id, get the details
    public static function getTestAssignmentDetails($test_assign_id) {
        $q = "SELECT U.user_id, U.first_name, U.last_name, U.email, TA.assigned_on_dt
            , TA.due_on_dt,TA.test_assign_id, TA.test_assign_status_id
            , S.test_assign_status_descr
            , T.test_name, T.test_descr, T.test_category, T.minutes_to_complete
            , COUNT(*) AS question_count
            FROM users U
            INNER JOIN test_assign_user TA ON TA.user_id = U.user_id
            INNER JOIN test_assign_status S ON S.test_assign_status_id = TA.test_assign_status_id
            INNER JOIN tests T ON T.test_id = TA.test_id
            INNER JOIN questions Q ON Q.test_id = T.test_id
            WHERE TA.test_assign_id =".$test_assign_id." AND T.deleted <> 1
            GROUP BY U.user_id, U.first_name, U.last_name, U.email, TA.assigned_on_dt
            , TA.due_on_dt,TA.test_assign_id, TA.test_assign_status_id
            , S.test_assign_status_descr
            , T.test_name, T.test_descr, T.test_category";

        $assign_details = DB::instance(DB_NAME)->select_row($q);

        return $assign_details;
    }

    //Get the details for the assigned question
    //Including 0. question text, 1. all answers, 2. the test instance answers
    public static function getQuestionDetails($test_instance_id, $question_id = null) {

        //If the question ID is null we need to get the first question
        if ($question_id == null) {
            $q = "SELECT MIN(question_id) FROM questions Q
            INNER JOIN tests T ON T.test_id = Q.test_id
            INNER JOIN test_assign_user TA ON TA.test_id = T.test_id
            INNER JOIN test_instance I ON I.test_assign_id = TA.test_assign_id
            WHERE I.test_instance_id = ".$test_instance_id;

            $question_id = DB::instance(DB_NAME)->select_field($q);
        }
        $q = "SELECT
        TI.test_instance_id,TI.start_dt,TI.finish_dt,TI.grade,TI.graded
        ,TI.seconds_elapsed,TI.review_override_grade,TI.review_override_user_id,TI.review_override_comment
        ,TA.test_assign_id,TA.test_id,user_id,TA.test_assign_status_id,TA.assigned_by_user_id,TA.assigned_on_dt,TA.due_on_dt
        ,T.test_id,T.account_id,T.test_name,T.test_descr,T.minutes_to_complete,T.test_category
        ,Q.question_id,Q.question_order,Q.question_text,Q.question_type_id,Q.question_image
        ,Qprior.question_id AS prior_question_id,Qnext.question_id AS next_question_id
        ,A.answer_id,A.answer_text,A.answer_order,A.correct
        ,IA.is_selected, IA.answer_text AS instance_answer_text
            FROM test_instance TI
            INNER JOIN test_assign_user TA ON TA.test_assign_id = TI.test_assign_id
            INNER JOIN tests T ON T.test_id = TA.test_id
            INNER JOIN questions Q ON Q.question_id = ".$question_id."
            LEFT JOIN questions Qnext ON Qnext.test_id = Q.test_id AND Qnext.question_order = (Q.question_order + 1)
            LEFT JOIN questions Qprior ON Qprior.test_id = Q.test_id AND Qprior.question_order = (IF(Q.question_order=0,null,Q.question_order) - 1)
            INNER JOIN answers A ON A.question_id = Q.question_id
            LEFT JOIN test_instance_answer IA ON IA.answer_id = A.answer_id
            WHERE TI.test_instance_id=".$test_instance_id;

        $question_details = DB::instance(DB_NAME)->select_rows($q);

        return $question_details;
    }

}

?>