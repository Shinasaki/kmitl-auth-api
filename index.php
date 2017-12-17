<?php
session_start();
header("Content-Type: application/json;charset=utf-8");
header("Access-Control-Allow-Origin: *");
// headers.append("Content-Type: application/json;charset=utf-8");
// headers.append('Access-Control-Allow-Origin: *');
// headers.append('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
// headers.append('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

// Spam limit
if (!spam(600)) { exit(); }
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
$request = 1;
// echo $contentType;

if(strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0){
    $des = 'Request method must be POST!';
    $code = 400;
    $detail = 'Bad request';
    $request = 0;
} elseif (strcasecmp(explode(";", $contentType)[0], 'application/json') != 0) {
    $des = 'Content type must be: application/json';
    $code = 400;
    $detail = 'Bad request';
    $request = 0;
}

// check pass
if ($request) {
    $userRaw = trim(file_get_contents("php://input"));
    echo $userRaw . "WTF";
    $userObj = json_decode($userRaw, true);
    if(is_array($userObj)){

        $username = $userObj['username'];
        $password = $userObj['password'];

        // check field empty
        if (empty($username) || empty($password)) {
            $des = "Field must not empty.";
            $code = "400";
            $detail = "Bad request";
        
        // check len string
        } elseif ( strlen($username) !=10 || strlen($password) != 8) {
            $des = "Field must have 8 characters.";
            $code = "400";
            $detail = "Bad request";
        
        // connect ldap
        } else {

            // connect to primary
            $ldap = ldap_connect('ldap://161.246.38.141');
    
            // try login to test connect with anonymous
            $anon = @ldap_bind ($ldap);
            if (!$anon) {
                $des = "Server down.";
                $code = 500;
                $detail = 'Internal Error';
            } else {
                // login with user & password
                if ( $login = ldap_bind($ldap, $username.'@it.kmitl.ac.th', $password)) {
                    // Login success
                    $des = "Logged in.";
                    $code = 200;
                    $detail = "Ok";
                }else{
                    // username/password invalid
                    $des = 'Username or Password incorrect.';
                    $code = 400;
                    $detail = 'Bad request';
                }
            }
        }



    // request must be json
    } else {
        $des = 'Received content contained invalid JSON!';
        $code = 400;
        $detail = 'Bad request';
    }
}

// respone
$respone = array(
    'code' => $code,
    'detail' => $detail,
    'description' => $des
);
echo json_encode($respone);
http_response_code($code);



// spam
function spam($limit) {
    // First viste
    if (!isset($_SESSION['expire'])) {
        $_SESSION['expire'] = (date('h') + 1) % 12;
        $_SESSION['count'] = 1;
        return true;
    } else {
        // echo $_SESSION['expire'] . " / " . $_SESSION['count'] . " / " . $_SESSION['unban'];
        // Reset
        if ($_SESSION['expire'] <= intval($date['h']) % 12 && !isset($_SESSION['unban'])) {
            $_SESSION['expire'] = (date('h') + 1) % 12;
            $_SESSION['count'] = 1;
            return true;
        } else {
            // Ban
            if ($_SESSION['count'] >= $limit) {
                $respone = array(
                    'code' => 429,
                    'detail' => 'Too many Request',
                    'description' => $_SERVER['REMOTE_ADDR'] . ' You are limited access.',
                    'admin' => "don't try lel."
                );
                echo json_encode($respone);
                http_response_code(429);
                $_SESSION['unban'] = (date('h') + 1) % 12;
                return false;
            // Unban
            } elseif ((date('h') + 1) % 12 == $_SESSION['unban']) {
                session_destroy();
                return true;
            // Count
            } else {
                $_SESSION['count'] = $_SESSION['count'] + 1;
                return true;
            }
        }
    }
}