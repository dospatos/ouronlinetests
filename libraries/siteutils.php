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


}

?>