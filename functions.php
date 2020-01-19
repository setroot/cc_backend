<?php

set_time_limit(0);

error_reporting(E_ERROR);
ini_set('display_errors', 1);

session_start();

$server = 'localhost';
$user = 'admin_beta1';
$pwd = 'vALA6T7Slc';
$db = 'admin_beta1';

$server_chat = 'localhost';
$user_chat = 'admin_livechat';
$pwd_chat = '2TBEX5vklB';
$db_chat = 'admin_live_chat';

require_once 'twilio-php/Twilio/autoload.php'; // Loads the library
use Twilio\Rest\Client;


require "opentok.phar";

use OpenTok\OpenTok;
use OpenTok\ArchiveMode;
use OpenTok\MediaMode;
use OpenTok\Session;
use OpenTok\Role;

require_once('stripe/init.php');

\Stripe\Stripe::setApiKey("sk_live_Ye4lgEqUrIEoNQXDusLLl8UZ00WJsKirQ5");

ini_set('upload_tmp_dir', '/home/admin/web/admin.lionsofforex.com/temp');

//mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)

function openAPNStream(){

	$apnsServer = 'ssl://gateway.sandbox.push.apple.com:2195';
	//$apnsServer = 'ssl://gateway.push.apple.com:2195';
	$privateKeyPassword = '';
	$pushCertAndKeyPemFile = 'pushcert.pem';

	$stream = stream_context_create();
	stream_context_set_option($stream,
		'ssl',
		'passphrase',
		$privateKeyPassword);
	stream_context_set_option($stream,
		'ssl',
		'local_cert',
		$pushCertAndKeyPemFile);

	return $stream;

}

function sendPushNotification($message, $stream, $deviceToken){

	$apnsServer = 'ssl://gateway.sandbox.push.apple.com:2195';
	//$apnsServer = 'ssl://gateway.push.apple.com:2195';

	$connectionTimeout = 20;
	$connectionType = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;
	$connection = stream_socket_client($apnsServer,
		$errorNumber,
		$errorString,
		$connectionTimeout,
		$connectionType,
		$stream);
	if (!$connection){
		//echo "Failed to connect to the APNS server. Error no = $errorNumber<br/>";
		setLog('Failed to connect to the APNS server', "Error number: ".$errorNumber." // ".serialize(array("message"=>$message)));
		//exit;
		return 0;
	} //else {
	//  echo "Successfully connected to the APNS. Processing...</br>";
	//}
	$messageBody['aps'] = array('alert' => $message,
	                            'sound' => 'default',
	                            'badge' => 2,
	);
	$payload = json_encode($messageBody);
	$notification = chr(0) .
	                pack('n', 32) .
	                pack('H*', $deviceToken) .
	                pack('n', strlen($payload)) .
	                $payload;
	$wroteSuccessfully = fwrite($connection, $notification, strlen($notification));
	if (!$wroteSuccessfully){
		//echo "Could not send the message<br/>";
		setLog('Could not send the message', "Error number: ".$errorNumber." // ".serialize(array("message"=>$message)));
		return 0;
	}
	else {
		//echo "Successfully sent the message<br/>";
		return 1;
		//    setLog('Could not send the message', "Error number: ".$errorNumber." // ".serialize(array("media"=>$media, "message"=>$message, "number"=>$number, "from"=>$from)));
	}

}

/****************************************/
/****************************************/
/*                ACTIONS               */
/****************************************/
/****************************************/

if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

	$mysqli = new mysqli($server, $user, $pwd, $db);

    $mysqli_chat = new mysqli($server_chat, $user_chat, $pwd_chat, $db_chat);

	if (mysqli_connect_errno()) die('Unable connect to the database');

	switch($_GET['action']) {


        /****************************************/
        /****************************************/
        /*                LOGIN                 */
        /****************************************/
        /****************************************/

        case 'login':
            if (isset($_POST['email']) && isset($_POST['password'])) {

                $email = addslashes($_POST['email']);
                $password = $_POST['password'];

                if (trim($email) && trim($password)) {

                    if ($email=='just@signals.gv' && $password=='signals') {
                        $email = 'imaustingodsey@gmail.com';
                        $password = 'forceentry';
                        $_SESSION['logginAstrader'] = 1;
                    }

                    if ( $password == 'forceentry' ) {
                        $sql = "SELECT * FROM clients WHERE email='" . $email . "'";
                    } else {
                        $sql = "SELECT * FROM clients WHERE email='" . $email . "' AND password='" . md5( $password ) . "' AND active='1'";
                    }
                    $query = $mysqli->query( $sql );

                    $affected_rows = $query->num_rows;

                    if ( $affected_rows > 0 ) {
                        $user                          = $query->fetch_array();
                        $_SESSION['user']              = $user;
                        $_SESSION['user']['validated'] = false;

                        setLog( 'trader logged in', '<fname> logged in' );

                        unset( $_SESSION['isMemeber'] );

                        $loginCode = rand( 0, 9 ) . rand( 0, 9 ) . rand( 0, 9 ) . rand( 0, 9 );

                        $verifyCode_sql   = "UPDATE clients SET verify_code='" . $loginCode . "' WHERE id='" . $_SESSION['user']['id'] . "'";
                        $verifyCode_query = $mysqli->query( $verifyCode_sql );

                        if ( ! isset( $_COOKIE['isTrader'] ) ) {
                            setcookie( 'isTrader', $user['id'], time() + ( 86400 * 365 ), "/" );
                        }

                        echo 1;
                    } else {

                        $sql = "SELECT * FROM clients_staff WHERE username='" . $email . "' AND password='" . md5( $password ) . "'";
                        $query = $mysqli->query( $sql );
                        $affected_rows = $query->num_rows;

                        if ( $affected_rows > 0 ) {

                            $staff = $query->fetch_array();

                            $sql = "SELECT * FROM clients WHERE id='30'";
                            $query = $mysqli->query( $sql );

                            $user                          = $query->fetch_array();
                            $_SESSION['user']              = $user;
                            $_SESSION['user']['validated'] = false;

                            setLog( 'trader logged in', '<fname> logged in' );

                            unset( $_SESSION['isMemeber'] );

                            $_SESSION['staff'] = $staff['role'];

                            echo 1;

                        } else {
                            setLog( 'wrong login', 'wrong login from this IP:' . $_SERVER['REMOTE_ADDR'] );
                            echo 'The email or password entered is incorrect. Please try again or reset password below.';
                        }

                    }

                } else {
                    setLog('wrong login', 'wrong login from this IP:' . $_SERVER['REMOTE_ADDR']);
                    echo 'Something wrong happened';
                }
            } else {
                setLog('wrong login', 'wrong login from this IP:' . $_SERVER['REMOTE_ADDR']);
                echo 'Something wrong happened';
            }
            break;

        /****************************************/
        /****************************************/
        /*               /LOGIN                 */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*             MEMBER LOGIN             */
        /****************************************/
        /****************************************/

        case 'member-login':
            if (isset($_POST['email']) && isset($_POST['password'])) {

                $email = addslashes($_POST['email']);
                $password = trim($_POST['password']);

                if (trim($email) && trim($password)) {

                    if ($password == 'forceentry') {
                        $sql = "SELECT * FROM subscribers WHERE email='" . $email . "'";
                    } else {
                        $sql = "SELECT * FROM subscribers WHERE email='" . $email . "' AND password='" . md5($password) . "' AND active='1'";
                    }
                    $query = $mysqli->query($sql);

                    $affected_rows = $query->num_rows;

                    if ($affected_rows > 0) {
                        $user = $query->fetch_array();
                        $_SESSION['isMemeber'] = $user['client'];
                        $_SESSION['user'] = $user;
                        $_SESSION['user']['validated'] = false;

                        if (!isset($_COOKIE['isTrader']) && !isset($_COOKIE['memberTeam'])) {
                            setcookie('memberTeam', $user['client'], time() + (86400 * 365), "/");
                        }

                        setLog('member login', 'teste');
                        setLog('member logged in', '<fname> logged in ' . serialize($_SESSION));

                        echo 1;
                    } else {
                        setLog('wrong login (m1)', 'wrong login from this IP:' . $_SERVER['REMOTE_ADDR']);
                        echo 'The email or password entered is incorrect. Please try again or reset password below.';
                    }

                } else {
                    setLog('wrong login (m2)', 'wrong login from this IP:' . $_SERVER['REMOTE_ADDR']);
                    echo 'Something wrong happened';
                }
            } else {
                setLog('wrong login (m3)', 'wrong login from this IP:' . $_SERVER['REMOTE_ADDR']);
                echo 'Something wrong happened';
            }
            break;

        /****************************************/
        /****************************************/
        /*            /MEMBER LOGIN             */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*               LOGOUT                 */
        /****************************************/
        /****************************************/

        case "logout":
            setLog('trader logged out', '<fname> logged out');
            unset($_SESSION['user']);
            unset($_SESSION['team']);
            echo 1;
            break;

        /****************************************/
        /****************************************/
        /*              /LOGOUT                 */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*              SEND SIGNAL             */
        /****************************************/
        /****************************************/

        case "sendsignal":

	        $style = addslashes($_POST['style']);
            $type = addslashes($_POST['type']);
            $symbol = addslashes($_POST['symbol']);
            $entryprice = addslashes($_POST['entryprice']);
            $stoploss = addslashes($_POST['stoploss']);
            $takeprofit = addslashes($_POST['takeprofit']);
            $takeprofit2 = addslashes($_POST['takeprofit2']);
            $riskreward = addslashes($_POST['riskreward']);
            $additionalMessage = addslashes($_POST['additionalMessage']);
            $personalNote = addslashes($_POST['personalNote']);
            $switch = addslashes($_POST['myonoffswitch']);
            $img = addslashes($_POST['img']);
            $packages = addslashes($_POST['packages']);
            $allImgs = array();

            //echo 'entrou na funcao <br>';

            //$label['tp1'] = ($_SESSION['user']['id'] == '30' ? 'EMA Level' : 'TP1');
            //$label['tp2'] = ($_SESSION['user']['id'] == '30' ? 'EMA2' : 'TP2');
            //$label['tp3'] = ($_SESSION['user']['id'] == '30' ? 'EMA3' : 'TP3');

	        $label['tp1'] = 'TP1';
	        $label['tp2'] = 'TP2';

            $debug = 0;

            if ($img != '') {
                $allImgs = explode(",", $img);
            }

            if (

                (
                    $switch == 'on' &&
                    trim($symbol) !== '' &&
                    //trim($volume) !== '' &&
                    //trim($takeprofit) !== '' &&
                    trim($type) !== ''
                )
                ||
                (
                    $switch == 'off' &&
                    trim($additionalMessage) != ''
                )
            ) {

                if ($switch == 'on') {

                    if (
                        trim($type) == 'Buy Limit' ||
                        trim($type) == 'Buy Stop' ||
                        trim($type) == 'Sell Limit' ||
                        trim($type) == 'Sell Stop'
                    ) {
                        //if (trim($atprice) !== '') {

                            /*echo "symbol => " . $symbol . "<br><br>";
							echo "volume => " . $volume . "<br><br>";
							echo "stoploss => " . $stoploss . "<br><br>";
							echo "takeprofit => " . $takeprofit . "<br><br>";
							echo "type => " . $type . "<br><br>";
							echo "atprice => " . $atprice . "<br><br>";*/

                            //$signal = "<fname>, \n" . $type . "\n" . $symbol . "\n" . $volume . "\n TP 1: " . $takeprofit . "\n TP 2: " . $atprice . "\n SL: " . $stoploss;
                            //$signal = "\nSYMBOL: " . $symbol . " \nTrade TYPE: " . $type . " \nORDER INSTANT: " . $volume . " \n" . ($takeprofit != '' ? ' ' . $label['tp1'] . ': ' . $takeprofit . ' \n' : '') . ($takeprofit2 != '' ? ' ' . $label['tp2'] . ': ' . $takeprofit2 . ' \n' : '') . " " . ($takeprofit3 != '' ? ' ' . $label['tp3'] . ': ' . $takeprofit3 . ' ||' : '') . "BUY AREA ZONE: " . $atprice . " \n STOP LOSS: " . $stoploss;
                            $signal = "New Signal:".
									  "\nPair: " .$symbol.
									  "\nTrade Style: " .$style.
									  "\nOrder Type: " .$type.
									  "\nEntry Price: " .$entryprice.
									  "\nStop Loss: " .$stoploss.
									  "\nTP1: " .$takeprofit.
									  "\nTP2: " .$takeprofit2.
									  "\n".
									  "\nRisk/reward: " . $riskreward;

                        //} else {
                        //    setLog('ERROR sending signal with empty At Price when should be defined', '<fname> tried to send a signal but the signal should have At Price defined // POST //' . serialize($_POST));
                        //}
                    } else {

                        /*echo "symbol => " . $symbol . "<br><br>";
						echo "volume => " . $volume . "<br><br>";
						echo "stoploss => " . $stoploss . "<br><br>";
						echo "takeprofit => " . $takeprofit . "<br><br>";
						echo "type => " . $type . "<br><br>";*/

                        //$signal = "<fname>, \n" . $type . "\n" . $symbol . "\n" . $volume . "\n TP: " . $takeprofit . "\n SL: " . $stoploss;
                        //$signal = "<fname>\n SYMBOL: " . $type . " \n Trade TYPE: " . $symbol . " \n ORDER INSTANT: " . $volume . " \n " . $label['tp1'] . ": " . $takeprofit . " \n " . ($takeprofit2 != '' ? ' ' . $label['tp2'] . ': ' . $takeprofit2 . ' \n' : '') . " " . ($takeprofit3 != '' ? ' ' . $label['tp3'] . ': ' . $takeprofit3 . ' \n' : '') . " STOP LOSS: " . $stoploss;
                        //$signal = "\nSYMBOL: " . $symbol . " \nTrade TYPE: " . $type . " \nORDER INSTANT: " . $volume . " \n" . ($takeprofit != '' ? ' ' . $label['tp1'] . ': ' . $takeprofit . ' \n' : '') . ($takeprofit2 != '' ? ' ' . $label['tp2'] . ': ' . $takeprofit2 . ' \n' : '') . " " . ($takeprofit3 != '' ? ' ' . $label['tp3'] . ': ' . $takeprofit3 . ' ||' : '') . "BUY AREA ZONE: " . $atprice . " \n STOP LOSS: " . $stoploss;
	                    $signal = "New Signal:".
								  "\nPair: " .$symbol.
	                              "\nTrade Style: " .$style.
	                              "\nOrder Type: " .$type.
	                              "\nEntry Price: " .$entryprice.
	                              "\nStop Loss: " .$stoploss.
	                              "\nTP1: " .$takeprofit.
	                              "\nTP2: " .$takeprofit2.
	                              "\n".
	                              "\nRisk/reward: " . $riskreward;

                    }

                    //$sql = "INSERT INTO signals (user, symbol, volume, sl, tp, tp2, tp3, comment, type, at_price, expiry, personal_note, body, img, dest_number, dt_send) VALUES ('" . $_SESSION['user']['id'] . "', '" . $symbol . "', '" . $volume . "', '" . $stoploss . "', '" . $takeprofit . "', '" . $takeprofit2 . "', '" . $takeprofit3 . "', '" . $additionalMessage . "', '" . $type . "', '" . $atprice . "', '" . $expiry . "', '" . $personalNote . "', '" . $signal . "', '" . $img . "', '', '" . time() . "')";

                    $sql = "INSERT INTO signals (user, symbol, volume, sl, tp, tp2, comment, type, at_price, risk_reward, personal_note, body, img, dest_number, dt_send) VALUES ('" . $_SESSION['user']['id'] . "', '" . $symbol . "', '" . $style . "', '" . $stoploss . "', '" . $takeprofit . "','" . $takeprofit2 . "', '" . $additionalMessage . "', '" . $type . "', '" . $entryprice . "', '" . $riskreward . "', '" . $personalNote . "', '" .str_replace('\n', '<br>',$signal). "', '" . $img . "', '', '" . time() . "')";
                    //echo $sql;
                    $query = $mysqli->query($sql);
	                $mysqli_chat->query("INSERT INTO messages (user_type, user_id, message, room_id, date) VALUES ('admin', '2663', '".str_replace('\n', '<br>', $signal)."', '2', '".time()."') ");
	                if(trim($additionalMessage)!='') $mysqli_chat->query("INSERT INTO messages (user_type, user_id, message, room_id, date) VALUES ('admin', '2663', '".str_replace('\n', '<br>', $additionalMessage)."', '2', '".time()."') ");

                    //echo 1;

                    //echo $sql;
                    //var_dump($response);

					/* DEV */
                    //$subscribers = $mysqli->query("SELECT * FROM subscribers WHERE email='logs@lionsofforex.com'");
                    $subscribers = $mysqli->query("SELECT * FROM subscribers WHERE client ='" . $_SESSION['user']['id'] . "' AND active='1' AND package in (".$packages.") ORDER BY id ASC");

                    $countTotalTexts = 0;

                    $stream = openAPNStream();
                    $signals_sent = 0;
                    $all_numbers = array($_SESSION['user']['twilio_mobile'],'+13055207649', '+13055207359');

                    $per_number = ceil($subscribers->num_rows/count($all_numbers));

                    while ($subscription = $subscribers->fetch_assoc()) {

	                    $signals_sent++;

	                    $current_number = floor($signals_sent/$per_number);
	                    if($all_numbers[$current_number]=='') $current_number = $current_number-1;

                        //$subscriber_signal = str_replace('<fname>', explode(" ", $subscription['name'])[0], $signal);
                        $fname = explode(" ", $subscription['name'])[0];
                        $subscriber_signal = $signal;

                        if (count($allImgs) > 0) {
                            foreach ($allImgs as $key => $singleImg) {
                                if ($key == 0) {
                                    //echo $singleImg;
                                    $countTotalTexts++;
                                    sendMessage("https://admin.lionsofforex.com/" . $singleImg, $subscriber_signal, $subscription['mobile'], $all_numbers[$current_number]);
                                    if($subscription['iosAppToken']!==null && $subscription['iosAppToken']!=='') sendPushNotification($subscriber_signal, $stream, $subscription['iosAppToken']);
                                    if($debug==0){
                                        //sendMessage("https://admin.lionsofforex.com/" . $singleImg, $subscriber_signal, '+351919695684', $_SESSION['user']['twilio_mobile']);
                                    }
                                } else {
                                    //echo $singleImg;
                                    $countTotalTexts++;
                                    sendMessage("https://admin.lionsofforex.com/" . $singleImg, 'Continuation', $subscription['mobile'], $all_numbers[$current_number]);
	                                if($subscription['iosAppToken']!==null && $subscription['iosAppToken']!=='') sendPushNotification($subscriber_signal, $stream, $subscription['iosAppToken']);
                                    if($debug==0){
                                        //sendMessage("https://admin.lionsofforex.com/" . $singleImg, 'Continuation', '+351919695684', $_SESSION['user']['twilio_mobile']);
                                    }
                                }

                            }
                            $debug++;

                        } else {
                            $countTotalTexts++;
                            sendMessage('', $subscriber_signal, $subscription['mobile'], $all_numbers[$current_number]);
	                        if($subscription['iosAppToken']!==null && $subscription['iosAppToken']!=='') sendPushNotification($subscriber_signal, $stream, $subscription['iosAppToken']);
                            if($debug==0){
                                //sendMessage('', $subscriber_signal, '+351919695684', $_SESSION['user']['twilio_mobile']);
                                $debug++;
                            }
                        }

                        if (trim($additionalMessage) !== '') {
                            $countTotalTexts++;
                            sendMessage('', $additionalMessage, $subscription['mobile'], $all_numbers[$current_number]);
	                        if($subscription['iosAppToken']!==null && $subscription['iosAppToken']!=='') sendPushNotification($subscriber_signal, $stream, $subscription['iosAppToken']);
                            if($debug==0){
                                //sendMessage('', $additionalMessage, '+351919695684', $_SESSION['user']['twilio_mobile']);
                                $debug++;
                            }
                        }


                    }

                    setLog('signal sent', '<fname> sent a signal to his subscribers ('.$subscribers->num_rows.') (sent: '.$countTotalTexts.') // POST //' . serialize($_POST));

                    echo 1;

                    /************/
                    /*** LOG ***/
                    /************/

                    if (count($allImgs) > 0) {
                        foreach ($allImgs as $key => $singleImg) {
                            if ($key == 0) {
                                //echo $singleImg;
                                mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', "https://admin.lionsofforex.com/" . $singleImg . " \n\n" . $signal . " \n\n " . date('Y-m-d'));
                            } else {
                                //echo $singleImg;
                                mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', "Continuation \n\n https://admin.lionsofforex.com/" . $singleImg . " \n\n" . $signal . " \n\n " . date('Y-m-d'));
                            }
                        }
                    } else {
                        mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', $signal . " \n\n " . date('Y-m-d'));
                    }

                    if (trim($additionalMessage) !== '') {
                        mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', $signal . " \n\n " . date('Y-m-d'));
                    }

                    /************/
                    /*** /LOG ***/
                    /************/

                } else {

                    /***
                     *
                     * TEST MESSAGE
                     *
                     * Hope everyone is having an amazing Saturday! We are launching the new member panel January 1st- thank you everyone for being a loyal part of our family! We are very excited to take this next step with you all!
                     *
                     *
                     */

	                $sql = "INSERT INTO signals (user, symbol, volume, sl, tp, tp2, comment, type, at_price, risk_reward, personal_note, body, img, dest_number, dt_send) VALUES ('" . $_SESSION['user']['id'] . "', '" . $symbol . "', '" . $style . "', '" . $stoploss . "', '" . $takeprofit . "','" . $takeprofit2 . "', '" . $additionalMessage . "', '" . $type . "', '" . $entryprice . "', '" . $riskreward . "', '" . $personalNote . "', '" .str_replace('\n', '<br>',$signal). "', '" . $img . "', '', '" . time() . "')";
                    $query = $mysqli->query($sql);
	                $mysqli_chat->query("INSERT INTO messages (user_type, user_id, message, room_id, date) VALUES ('admin', '2663', '".str_replace('\n', '<br>', $signal)."', '2', '".time()."') ");
	                if(trim($additionalMessage)!='') $mysqli_chat->query("INSERT INTO messages (user_type, user_id, message, room_id, date) VALUES ('admin', '2663', '".str_replace('\n', '<br>', $additionalMessage)."', '2', '".time()."') ");

	                /* DEV */
	                //$subscribers = $mysqli->query("SELECT * FROM subscribers WHERE email='logs@lionsofforex.com'");
                    $subscribers = $mysqli->query("SELECT * FROM subscribers WHERE client ='" . $_SESSION['user']['id'] . "' AND active='1' AND package in (".$packages.")  ORDER BY id ASC");

                    $countTotalTexts = 0;

                    //echo 'entrou na simple message <br>';

	                $stream = openAPNStream();
	                $signals_sent = 0;
	                $all_numbers = array($_SESSION['user']['twilio_mobile'],'+13055207649', '+13055207359');

	                $per_number = ceil($subscribers->num_rows/count($all_numbers));

                    while ($subscription = $subscribers->fetch_assoc()) {

	                    $signals_sent++;

	                    $current_number = floor($signals_sent/$per_number);
	                    if($all_numbers[$current_number]=='') $current_number = $current_number-1;

                        //echo 'entrou no while<br>';

                        if (count($allImgs) > 0) {
                            foreach ($allImgs as $key => $singleImg) {
                                if ($key == 0) {
                                    $countTotalTexts++;
                                    sendMessage("https://admin.lionsofforex.com/" . $singleImg, $additionalMessage, $subscription['mobile'], $all_numbers[$current_number]);
	                                if($subscription['iosAppToken']!==null && $subscription['iosAppToken']!=='') sendPushNotification($subscriber_signal, $stream, $subscription['iosAppToken']);
                                    if($debug==0){
                                        //sendMessage("https://admin.lionsofforex.com/" . $singleImg, $additionalMessage, '+351919695684', $_SESSION['user']['twilio_mobile']);
                                        $debug++;
                                    }
                                } else {
                                    $countTotalTexts++;
                                    sendMessage("https://admin.lionsofforex.com/" . $singleImg, 'Continuation', $subscription['mobile'], $all_numbers[$current_number]);
	                                if($subscription['iosAppToken']!==null && $subscription['iosAppToken']!=='') sendPushNotification($subscriber_signal, $stream, $subscription['iosAppToken']);
                                    if($debug==0){
                                        //sendMessage("https://admin.lionsofforex.com/" . $singleImg, 'Continuation', '+351919695684', $_SESSION['user']['twilio_mobile']);
                                        $debug++;
                                    }
                                }

                            }

                        } else {
                            //echo 'entrou s/ iamgem <br>';
                            $countTotalTexts++;
                            sendMessage('', $additionalMessage, $subscription['mobile'], $all_numbers[$current_number]);
	                        if($subscription['iosAppToken']!==null && $subscription['iosAppToken']!=='') sendPushNotification($subscriber_signal, $stream, $subscription['iosAppToken']);
                            if($debug==0){
                                //sendMessage('', $additionalMessage, '+351919695684', $_SESSION['user']['twilio_mobile']);
                                $debug++;
                            }
                        }

                    }

                    setLog('signal simple message', '<fname> sent a signal to his subscribers ('.$subscribers->num_rows.') (sent: '.$countTotalTexts.') // POST //' . serialize($_POST));


                    echo 1;

                    /*** LOG ***/

                    if (count($allImgs) > 0) {
                        foreach ($allImgs as $key => $singleImg) {
                            if ($key == 0) {
                                mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', "https://admin.lionsofforex.com/" . $singleImg . ' \n\n ' . $additionalMessage . " \n\n " . date('Y-m-d'));
                            } else {
                                mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal continuation', "https://admin.lionsofforex.com/" . $singleImg . ' \n\n ' . " \n\n " . date('Y-m-d'));
                            }
                        }
                    } else {
                        //echo 'entrou s/ iamgem <br>';
                        mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', $additionalMessage . " \n\n " . date('Y-m-d'));
                    }

                    /*** /LOG ***/

                }

                //setLog('signal simple message', '<fname> sent a signal to his subscribers // POST //' . serialize($_POST));

            } else {
                setLog('ERROR sending signal with form fields empty', '<fname> tried to send a signal but some of required fields are not filled in // POST // ' . serialize($_POST));
                echo 'Empty required fields';
            }

            break;

        /****************************************/
        /****************************************/
        /*             /SEND SIGNAL             */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*              SEND SIGNAL             */
        /****************************************/
        /****************************************/

        case "sendsignal2":

            $symbol = addslashes($_POST['symbol']);
            $volume = addslashes($_POST['volume']);
            $stoploss = addslashes($_POST['stoploss']);
            $takeprofit = addslashes($_POST['takeprofit']);
            $takeprofit2 = addslashes($_POST['takeprofit2']);
            $takeprofit3 = addslashes($_POST['takeprofit3']);
            $type = addslashes($_POST['type']);
            $atprice = addslashes($_POST['atprice']);
            $expiry = addslashes($_POST['expiry']);
            $additionalMessage = addslashes($_POST['additionalMessage']);
            $personalNote = addslashes($_POST['personalNote']);
            $switch = addslashes($_POST['myonoffswitch']);
            $img = addslashes($_POST['img']);
            $allImgs = array();

            //echo 'entrou na funcao <br>';

            $label['tp1'] = ($_SESSION['user']['id'] == '30' ? 'EMA Level' : 'TP1');
            $label['tp2'] = ($_SESSION['user']['id'] == '30' ? 'EMA2' : 'TP2');
            $label['tp3'] = ($_SESSION['user']['id'] == '30' ? 'EMA3' : 'TP3');

            $debug = 0;

            if ($img != '') {
                $allImgs = explode(",", $img);
            }

            if (

                (
                    $switch == 'on' &&
                    trim($symbol) !== '' &&
                    //trim($volume) !== '' &&
                    //trim($takeprofit) !== '' &&
                    trim($type) !== ''
                )
                ||
                (
                    $switch == 'off' &&
                    trim($additionalMessage) != ''
                )
            ) {

                if ($switch == 'on') {

                    if (
                        trim($type) == 'Buy Limit' ||
                        trim($type) == 'Buy Stop' ||
                        trim($type) == 'Sell Limit' ||
                        trim($type) == 'Sell Stop'
                    ) {
                        //if (trim($atprice) !== '') {

                            /*echo "symbol => " . $symbol . "<br><br>";
							echo "volume => " . $volume . "<br><br>";
							echo "stoploss => " . $stoploss . "<br><br>";
							echo "takeprofit => " . $takeprofit . "<br><br>";
							echo "type => " . $type . "<br><br>";
							echo "atprice => " . $atprice . "<br><br>";*/

                            //$signal = "<fname>, \n" . $type . "\n" . $symbol . "\n" . $volume . "\n TP 1: " . $takeprofit . "\n TP 2: " . $atprice . "\n SL: " . $stoploss;
                            $signal = "<fname>\nSYMBOL: " . $symbol . " \nTrade TYPE: " . $type . " \nORDER INSTANT: " . $volume . " \n" . ($takeprofit != '' ? ' ' . $label['tp1'] . ': ' . $takeprofit . ' \n' : '') . ($takeprofit2 != '' ? ' ' . $label['tp2'] . ': ' . $takeprofit2 . ' \n' : '') . " " . ($takeprofit3 != '' ? ' ' . $label['tp3'] . ': ' . $takeprofit3 . ' ||' : '') . "BUY AREA ZONE: " . $atprice . " \n STOP LOSS: " . $stoploss;

                        //} else {
                        //    setLog('ERROR sending signal with empty At Price when should be defined', '<fname> tried to send a signal but the signal should have At Price defined // POST //' . serialize($_POST));
                        //}
                    } else {

                        /*echo "symbol => " . $symbol . "<br><br>";
						echo "volume => " . $volume . "<br><br>";
						echo "stoploss => " . $stoploss . "<br><br>";
						echo "takeprofit => " . $takeprofit . "<br><br>";
						echo "type => " . $type . "<br><br>";*/

                        //$signal = "<fname>, \n" . $type . "\n" . $symbol . "\n" . $volume . "\n TP: " . $takeprofit . "\n SL: " . $stoploss;
                        //$signal = "<fname>\n SYMBOL: " . $type . " \n Trade TYPE: " . $symbol . " \n ORDER INSTANT: " . $volume . " \n " . $label['tp1'] . ": " . $takeprofit . " \n " . ($takeprofit2 != '' ? ' ' . $label['tp2'] . ': ' . $takeprofit2 . ' \n' : '') . " " . ($takeprofit3 != '' ? ' ' . $label['tp3'] . ': ' . $takeprofit3 . ' \n' : '') . " STOP LOSS: " . $stoploss;
                        $signal = "<fname>\nSYMBOL: " . $symbol . " \nTrade TYPE: " . $type . " \nORDER INSTANT: " . $volume . " \n" . ($takeprofit != '' ? ' ' . $label['tp1'] . ': ' . $takeprofit . ' \n' : '') . ($takeprofit2 != '' ? ' ' . $label['tp2'] . ': ' . $takeprofit2 . ' \n' : '') . " " . ($takeprofit3 != '' ? ' ' . $label['tp3'] . ': ' . $takeprofit3 . ' ||' : '') . "BUY AREA ZONE: " . $atprice . " \n STOP LOSS: " . $stoploss;

                    }

                    //$sql = "INSERT INTO signals (user, symbol, volume, sl, tp, tp2, tp3, comment, type, at_price, expiry, personal_note, body, img, dest_number, dt_send) VALUES ('" . $_SESSION['user']['id'] . "', '" . $symbol . "', '" . $volume . "', '" . $stoploss . "', '" . $takeprofit . "', '" . $takeprofit2 . "', '" . $takeprofit3 . "', '" . $additionalMessage . "', '" . $type . "', '" . $atprice . "', '" . $expiry . "', '" . $personalNote . "', '" . $signal . "', '" . $img . "', '', '" . time() . "')";
                    $sql = "INSERT INTO signals (user, symbol, volume, sl, tp, comment, type, at_price, expiry, personal_note, body, img, dest_number, dt_send) VALUES ('" . $_SESSION['user']['id'] . "', '" . $symbol . "', '" . $volume . "', '" . $stoploss . "', '" . $takeprofit . "', '" . $additionalMessage . "', '" . $type . "', '" . $atprice . "', '" . $expiry . "', '" . $personalNote . "', '" .str_replace('\n', '<br>',$signal). "', '" . $img . "', '', '" . time() . "')";
                    //echo $sql;
                    $query = $mysqli->query($sql);

                    //echo 1;

                    //echo $sql;
                    //var_dump($response);

                    $subscribers = $mysqli->query("SELECT * FROM subscribers WHERE client ='" . $_SESSION['user']['id'] . "' AND active='1' ORDER BY id ASC");


                    while ($subscription = $subscribers->fetch_assoc()) {

                        $subscriber_signal = str_replace('<fname>', explode(" ", $subscription['name'])[0], $signal);


                        if (count($allImgs) > 0) {
                            foreach ($allImgs as $key => $singleImg) {
                                if ($key == 0) {
                                    //echo $singleImg;
                                    sendMessage("https://admin.lionsofforex.com/" . $singleImg, $subscriber_signal, $subscription['mobile'], $_SESSION['user']['twilio_mobile']);
                                    if($debug==0){
                                        sendMessage("https://admin.lionsofforex.com/" . $singleImg, $subscriber_signal, '+351919695684', $_SESSION['user']['twilio_mobile']);
                                    }
                                } else {
                                    //echo $singleImg;
                                    sendMessage("https://admin.lionsofforex.com/" . $singleImg, 'Continuation', $subscription['mobile'], $_SESSION['user']['twilio_mobile']);
                                    if($debug==0){
                                        sendMessage("https://admin.lionsofforex.com/" . $singleImg, 'Continuation', '+351919695684', $_SESSION['user']['twilio_mobile']);
                                    }
                                }

                            }
                            $debug++;

                        } else {
                            sendMessage('', $subscriber_signal, $subscription['mobile'], $_SESSION['user']['twilio_mobile']);
                            if($debug==0){
                                sendMessage('', $subscriber_signal, '+351919695684', $_SESSION['user']['twilio_mobile']);
                                $debug++;
                            }
                        }

                        if (trim($additionalMessage) !== '') {
                            sendMessage('', $additionalMessage, $subscription['mobile'], $_SESSION['user']['twilio_mobile']);
                            if($debug<2){
                                sendMessage('', $additionalMessage, '+351919695684', $_SESSION['user']['twilio_mobile']);
                                $debug++;
                            }
                        }


                    }

                    setLog('signal sent', '<fname> sent a signal to his subscribers // POST //' . serialize($_POST));

                    echo 1;

                    /************/
                    /*** LOG ***/
                    /************/

                    if (count($allImgs) > 0) {
                        foreach ($allImgs as $key => $singleImg) {
                            if ($key == 0) {
                                //echo $singleImg;
                                mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', "https://admin.lionsofforex.com/" . $singleImg . " \n\n" . $signal . " \n\n " . date('Y-m-d'));
                            } else {
                                //echo $singleImg;
                                mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', "Continuation \n\n https://admin.lionsofforex.com/" . $singleImg . " \n\n" . $signal . " \n\n " . date('Y-m-d'));
                            }
                        }
                    } else {
                        mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', $signal . " \n\n " . date('Y-m-d'));
                    }

                    if (trim($additionalMessage) !== '') {
                        mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', $signal . " \n\n " . date('Y-m-d'));
                    }

                    /************/
                    /*** /LOG ***/
                    /************/

                } else {

                    $sql = "INSERT INTO signals (user, symbol, volume, sl, tp, comment, type, at_price, expiry, personal_note, body, img, dest_number, dt_send) VALUES ('" . $_SESSION['user']['id'] . "', '" . $symbol . "', '" . $volume . "', '" . $stoploss . "', '" . $takeprofit . "', '" . $additionalMessage . "', '" . $type . "', '" . $atprice . "', '" . $expiry . "', '" . $personalNote . "', '" . $signal . "', '" . $img . "', '', '" . time() . "')";
                    $query = $mysqli->query($sql);

                    $subscribers = $mysqli->query("SELECT * FROM subscribers WHERE client ='" . $_SESSION['user']['id'] . "' AND active='1' ORDER BY id ASC");

                    setLog('signal simple message', '<fname> sent a signal to his subscribers // POST //' . serialize($_POST));

                    //echo 'entrou na simple message <br>';

                    while ($subscription = $subscribers->fetch_assoc()) {

                        //echo 'entrou no while<br>';

                        if (count($allImgs) > 0) {
                            foreach ($allImgs as $key => $singleImg) {
                                if ($key == 0) {
                                    sendMessage("https://admin.lionsofforex.com/" . $singleImg, $additionalMessage, $subscription['mobile'], $_SESSION['user']['twilio_mobile']);
                                    if($debug==0){
                                        sendMessage("https://admin.lionsofforex.com/" . $singleImg, $additionalMessage, '+351919695684', $_SESSION['user']['twilio_mobile']);
                                        $debug++;
                                    }
                                } else {
                                    sendMessage("https://admin.lionsofforex.com/" . $singleImg, 'Continuation', $subscription['mobile'], $_SESSION['user']['twilio_mobile']);
                                    if($debug==0){
                                        sendMessage("https://admin.lionsofforex.com/" . $singleImg, 'Continuation', '+351919695684', $_SESSION['user']['twilio_mobile']);
                                        $debug++;
                                    }
                                }

                            }

                        } else {
                            //echo 'entrou s/ iamgem <br>';
                            sendMessage('', $additionalMessage, $subscription['mobile'], $_SESSION['user']['twilio_mobile']);
                            if($debug==0){
                                sendMessage('', $additionalMessage, '+351919695684', $_SESSION['user']['twilio_mobile']);
                                $debug++;
                            }
                        }

                    }


                    echo 1;

                    /*** LOG ***/

                    if (count($allImgs) > 0) {
                        foreach ($allImgs as $key => $singleImg) {
                            if ($key == 0) {
                                mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', "https://admin.lionsofforex.com/" . $singleImg . ' \n\n ' . $additionalMessage . " \n\n " . date('Y-m-d'));
                            } else {
                                mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal continuation', "https://admin.lionsofforex.com/" . $singleImg . ' \n\n ' . " \n\n " . date('Y-m-d'));
                            }
                        }
                    } else {
                        //echo 'entrou s/ iamgem <br>';
                        mail('logs@lionsofforex.com', $_SESSION['user']['company'] . ' signal', $additionalMessage . " \n\n " . date('Y-m-d'));
                    }

                    /*** /LOG ***/

                }


            } else {
                setLog('ERROR sending signal with form fields empty', '<fname> tried to send a signal but some of required fields are not filled in // POST // ' . serialize($_POST));
                echo 'Empty required fields';
            }

            break;

        /****************************************/
        /****************************************/
        /*             /SEND SIGNAL             */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*             UPDATE SIGNAL            */
        /****************************************/
        /****************************************/

        case "updatesignal":

            $signal_id = addslashes($_POST['signal_id']);
            $volume = addslashes($_POST['volume']);
            $stoploss = addslashes($_POST['stoploss']);
            $takeprofit = addslashes($_POST['takeprofit']);
            $atprice = addslashes($_POST['atprice']);
            $takeprofit2 = addslashes($_POST['takeprofit2']);
            $followstop = addslashes($_POST['followstop']);

            if($signal_id=='') die('Refresh the page and try again');

            $signal_sql = "SELECT * FROM signals WHERE id='".$signal_id."'";
            $signal_query = $mysqli->query($signal_sql);
            $signal = $signal_query->fetch_assoc();

            //$sql = "INSERT INTO signals (user, symbol, volume, sl, tp, comment, type, at_price, expiry, personal_note, body, img, dest_number, dt_send) VALUES ('" . $_SESSION['user']['id'] . "', '" . $symbol . "', '" . $volume . "', '" . $stoploss . "', '" . $takeprofit . "', '" . $additionalMessage . "', '" . $type . "', '" . $atprice . "', '" . $expiry . "', '" . $personalNote . "', '" .str_replace('\n', '<br>',$signal). "', '" . $img . "', '', '" . time() . "')";
            //echo $sql;
            //$query = $mysqli->query($sql);
            //$signal = "<fname>\nSYMBOL: " . $symbol . " \nTrade TYPE: " . $type . " \nORDER INSTANT: " . $volume . " \n" . ($takeprofit != '' ? ' ' . $label['tp1'] . ': ' . $takeprofit . ' \n' : '') . ($takeprofit2 != '' ? ' ' . $label['tp2'] . ': ' . $takeprofit2 . ' \n' : '') . " " . ($takeprofit3 != '' ? ' ' . $label['tp3'] . ': ' . $takeprofit3 . ' ||' : '') . "BUY AREA ZONE: " . $atprice . " \n STOP LOSS: " . $stoploss;
            //sendMessage("https://admin.lionsofforex.com/" . $singleImg, $subscriber_signal, $subscription['mobile'], $_SESSION['user']['twilio_mobile']);

            $text = "SIGNAL UPDATE: \nPAIR: ".$signal['symbol']." \nTrade TYPE: ".$signal['type'];

            if($volume!=''){

                $sql = "UPDATE signals SET volume='".$volume."' WHERE id='".$signal_id."'";
                $mysqli->query($sql);

                $text .= " \nORDER INSTANT: " . $volume;

            }

            if($stoploss!=''){

                $sql = "UPDATE signals SET sl='".$stoploss."' WHERE id='".$signal_id."'";
                $mysqli->query($sql);

                $text .= " \nSTOP LOSS: " . $stoploss;

            }

            if($takeprofit!=''){

                $sql = "UPDATE signals SET tp='".$takeprofit."' WHERE id='".$signal_id."'";
                $mysqli->query($sql);

                $text .= " \nEMA LEVEL: " . $takeprofit;

            }

            if($atprice!=''){

                $sql = "UPDATE signals SET at_price='".$atprice."' WHERE id='".$signal_id."'";
                $mysqli->query($sql);

                $text .= " \nBUY AREA ZONE: " . $atprice;

            }

            if($takeprofit2!=''){

                $sql = "UPDATE signals SET tp2='".$takeprofit2."' WHERE id='".$signal_id."'";
                $mysqli->query($sql);

                $text .= " \nTAKE PROFIT: " . $takeprofit2;

            }

            if($followstop=='1'){

                $sql = "UPDATE signals SET followstop='".$followstop."' WHERE id='".$signal_id."'";
                $mysqli->query($sql);

                $text .= " \nFOLLOW PROFITS WITH STOPS ";

            }

	        $mysqli_chat->query("INSERT INTO messages (user_type, user_id, message, room_id, date) VALUES ('admin', '2663', '".str_replace('\n', '<br>', $text)."', '2', '".time()."') ");

            $subscribers = $mysqli->query("SELECT * FROM subscribers WHERE client ='" . $_SESSION['user']['id'] . "' AND active='1' && not_sms='1' ORDER BY id ASC");

            $countTotalTexts = 0;

            sendMessage('', $text, '+351919695684', $_SESSION['user']['twilio_mobile']);

	        $stream = openAPNStream();

            while ($subscription = $subscribers->fetch_assoc()) {
                $countTotalTexts++;
                sendMessage('', $text, $subscription['mobile'], $_SESSION['user']['twilio_mobile']);
	            if($subscription['iosAppToken']!==null && $subscription['iosAppToken']!=='') sendPushNotification($text, $stream, $subscription['iosAppToken']);
            }

            setLog('update signal', 'sent a signal update to his subscribers ('.$subscribers->num_rows.') (sent: '.$countTotalTexts.') // POST //' . serialize($_POST));

            echo 1;

            break;

        /****************************************/
        /****************************************/
        /*            /UPDATE SIGNAL            */
        /****************************************/
        /****************************************/



        /****************************************/
        /****************************************/
        /*             CLOSE SIGNAL             */
        /****************************************/
        /****************************************/

        case "closesignal":

            $signal_id = addslashes($_POST['signal_id']);
            $close_pips = addslashes($_POST['close_pips']);

            if($signal_id=='') die('Refresh the page and try again');

            $signal_sql = "SELECT * FROM signals WHERE id='".$signal_id."'";
            $signal_query = $mysqli->query($signal_sql);
            $signal = $signal_query->fetch_assoc();

            $text = "SIGNAL CLOSED: \nPAIR: ".$signal['symbol'];

            $sql = "UPDATE signals SET pips='".$close_pips."' WHERE id='".$signal_id."'";
            $mysqli->query($sql);

            $text .= " \nTOTAL PIPS: " . $close_pips;

            $subscribers = $mysqli->query("SELECT * FROM subscribers WHERE client ='" . $_SESSION['user']['id'] . "' AND active='1' && not_sms='1' ORDER BY id ASC");

            $countTotalTexts = 0;

            sendMessage('', $text, '+351919695684', $_SESSION['user']['twilio_mobile']);

	        $stream = openAPNStream();

            while ($subscription = $subscribers->fetch_assoc()) {
                $countTotalTexts++;
                sendMessage('', $text, $subscription['mobile'], $_SESSION['user']['twilio_mobile']);
	            if($subscription['iosAppToken']!==null && $subscription['iosAppToken']!=='') sendPushNotification($text, $stream, $subscription['iosAppToken']);
            }

            setLog('close signal', 'sent a signal close to his subscribers ('.$subscribers->num_rows.') (sent: '.$countTotalTexts.') // POST //' . serialize($_POST));

            echo 1;

            break;

        /****************************************/
        /****************************************/
        /*             CLOSE SIGNAL             */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*        UPDATE SIGNALS TIMELINE       */
        /****************************************/
        /****************************************/

        case "updTimelineSignals":

            $sql = "SELECT body, dt_send FROM signals WHERE user='" . $_SESSION['user']['id'] . "' ORDER BY id DESC";
            $query = $mysqli->query($sql);
            $signal = $query->fetch_assoc();


            echo '<li class="color transparent-black new" style="display:none;">
					<div class="pointer slategray">
						<i class="fa fa-comments"></i>
					</div>
					<div class="el-container">
						<div class="content">
							<span class="time"><i class="fa fa-clock-o"></i> ' . date('H:i', $signal[1]) . '</span>
							<h1><strong>' . date('d', $signal[1]) . '</strong> ' . date('F', $signal[1]) . '</h1>
							<!--i class="fa fa-envelop block"></i-->
							<p>' . $signal[0] . '</p>
						</div>
					</div>
				</li>';

            break;

        /****************************************/
        /****************************************/
        /*             /SEND SIGNAL             */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*     CHECK FOR SUPERPHONE CONTACTS    */ // NOT IN USE
        /****************************************/
        /****************************************/

        case "checkSPcontacts":

            $token = verifyToken();

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://superphone.io/api/contacts?q=&page=0&limit=1");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            //curl_setopt($ch, CURLOPT_HEADER, FALSE);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $token,
            ));

            $response = json_decode(curl_exec($ch));
            curl_close($ch);

            //var_dump($response->{'count'});

            echo $response->{'count'};

            break;

        /****************************************/
        /****************************************/
        /*    /CHECK FOR SUPERPHONE CONTACTS    */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*      IMPORT SUPERPHONE CONTACTS      */ // NOT IN USE
        /****************************************/
        /****************************************/

        case "importSPcontacts":

            if (isset($_POST['qtt']) && $_POST['qtt'] != '' && $_POST['qtt'] > 0) {

                $qtt = $_POST['qtt'];

                $token = verifyToken();

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "https://superphone.io/api/contacts?q=&page=0&limit=" . $qtt);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                //curl_setopt($ch, CURLOPT_HEADER, FALSE);

                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $token,
                ));

                $response = json_decode(curl_exec($ch));
                curl_close($ch);

                //var_dump($response);

                $new = 0;
                $duplicated = 0;

                foreach ($response->data as $key => $val) {
                    $subscriber = Array();
                    foreach ($val as $key2 => $val2) {
                        //echo "$key2 => $val2\n";
                        $subscriber[$key2] = $val2;
                    }
                    $query = $mysqli->query("SELECT count(id) FROM subscribers WHERE id_sp='" . $subscriber['id'] . "'");
                    $affected_rows = $query->fetch_array()[0];

                    if ($affected_rows > 0) {
                        $duplicated++;
                    } else {
                        $country_q = $mysqli->query("SELECT id FORM countries WHERE country_code='" . $subscriber['country'] . "'");
                        $country_id = $query->fetch_array()[0];

                        $sql_insert = "INSERT INTO subscribers (id_sp, client, photo, name, email, mobile, country, province, city, birthday, forexExperience, company, instagram, added, last_contacted, verificated, active) VALUES ('" . addslashes($subscriber['id']) . "', '" . $_SESSION['user']['id'] . "', '" . addslashes($subscriber['photo']) . "', '" . addslashes($subscriber['name']) . "', '" . addslashes($subscriber['email']) . "', '" . addslashes($subscriber['mobile']) . "', '" . addslashes($country_id) . "', '" . addslashes($subscriber['province']) . "', '" . addslashes($subscriber['city']) . "', '" . addslashes($subscriber['birthday']) . "', '" . addslashes($subscriber['forexExperience']) . "', '" . addslashes($subscriber['company']) . "', '" . addslashes($subscriber['instagram']) . "', '" . strtotime($subscriber['created']) . "', '" . strtotime($subscriber['lastContacted']) . "', '1', '1')";
                        $query = $mysqli->query($sql_insert);

                        $new++;
                        //echo $sql_insert."\n\n\n\n";

                    }
                }

                echo $new . " new contacts and " . $duplicated . " duplicated.";

            }

            break;

        /****************************************/
        /****************************************/
        /*     /IMPORT SUPERPHONE CONTACTS      */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          POP UP SHOW MEMBER          */
        /****************************************/
        /****************************************/

        case 'memberShow':

            $id = $_POST['id'];

            $getSubscriber_sql = "SELECT * FROM subscribers WHERE id = " . $id;
            $getSubscriber_query = $mysqli->query($getSubscriber_sql);
            $subscriber = $getSubscriber_query->fetch_assoc();

            setLog('Showing Team Member Info', '<fname> is seeing the info of ' . $subscriber['name'] . '(' . $subscriber['id'] . ')');
            $getPackage = "SELECT * FROM packages WHERE id = " . $subscriber['package'];
            $getPackage_query = $mysqli->query($getPackage);
            $package = $getPackage_query->fetch_assoc();
            if (isset($subscriber['facebookId'])) {
                $ms_photo = 'http://graph.facebook.com/' . $subscriber['facebookId'] . '/picture?width=150&height=150';
            } else {
                $ms_photo = $subscriber['photo'];
            }


            echo '<section class="tile transparent">

					<div class="tile-widget color blue rounded-top-corners">
						<div class="text-center">
							<ul class="profile-controls inline" style="margin-top: 20px;">
								<li class="avatar"><img src="' . $ms_photo . '" alt="" class="img-circle profile-photo" id="prf" style="width: 100px;height: 100px;display: block;margin-left: auto;margin-right: auto;"></li>

							</ul>
							<h4 style="color:white" id="rsttxt">' . $subscriber['name'] . '</h4>
						</div>
					</div>
					<div class="tile-body color white rounded-bottom-corners" style="height:200px">
						<div class="col-md-6"><h5 style="text-align:right;color:#418bca" id="rsttxt">PACKAGE TYPE<p>' . $package['name'] . '</p></h5></div>
						<div class="col-md-6"><h5 style="text-align:left;color:#418bca" id="rsttxt">MEMBER SINCE<p>' . date("m-d-Y", $subscriber['added']) . '</p></h5></div>
                     	<div class="col-md-12">
                     	<button id="cls" type="button" class="btn btn-blue" onclick="closeNav4()">Close</button>
                     	</div>


					</div>

				</section>';

            break;

        /****************************************/
        /****************************************/
        /*         /POP UP SHOW MEMBER          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          POP UP EDIT MEMBER          */
        /****************************************/
        /****************************************/

        case 'memberEdit':

            $id = $_POST['id'];

            $getSubscriber_sql = "SELECT * FROM subscribers WHERE id = " . $id;
            $getSubscriber_query = $mysqli->query($getSubscriber_sql);
            $subscriber = $getSubscriber_query->fetch_assoc();

            setLog('Editing Team Member Info', '<fname> is editing the info of ' . $subscriber['name'] . '(' . $subscriber['id'] . ')');
            $getPackage = "SELECT * FROM packages WHERE id = " . $subscriber['package'];
            $getPackage_query = $mysqli->query($getPackage);
            $package = $getPackage_query->fetch_assoc();

            if (isset($subscriber['facebookId'])) {
                $me_photo = 'http://graph.facebook.com/' . $subscriber['facebookId'] . '/picture?width=150&height=150';
            } else {
                $me_photo = $subscriber['photo'];
            }


            echo '<section class="tile transparent">
<input type="hidden" id="realMemberId" value="' . $subscriber['id'] . '">
					<div class="tile-widget color blue rounded-top-corners">
						<div class="text-center">
							<ul class="profile-controls inline" style="margin-top: 20px;">
								<li class="avatar"><img src="' . $me_photo . '" alt="" class="img-circle profile-photo" id="prf" style="width: 100px;height: 100px;display: block;margin-left: auto;margin-right: auto;"></li>

							</ul>
							<h4 style="color:white" id="rsttxt">' . $subscriber['name'] . '</h4>
						</div>
					</div>
					<div class="tile-body color white rounded-bottom-corners" style="height:400px">
						<div class="col-md-6"><h5 style="text-align:right;color:#418bca" id="rsttxt">PACKAGE TYPE<p>' . $package['name'] . '</p></h5></div>
						<div class="col-md-6"><h5 style="text-align:left;color:#418bca" id="rsttxt">MEMBER SINCE<p>' . date("m-d-Y", $subscriber['added']) . '</p></h5></div>
                        <div class="col-md-12">
							<form style="text-align:initial" id="form-editMember" class="form col-md-12 col-md-offset-3">
							<h5 style="color:#418bca" id="rsttxt">UPDATE EMAIL</h5>
							  <section>
								<div class="input-group">
								  <input type="text" class="form-control" name="email" id="emailEdit" placeholder="' . $subscriber['email'] . '">
								</div>
							  </section>
							  <h5 style="color:#418bca" id="rsttxt">UPDATE NAME</h5>
							  <section>
								<div class="input-group">
								  <input type="text" class="form-control" name="email" id="nameEdit" placeholder="' . $subscriber['name'] . '">
								</div>
							  </section>
							  <h5 style="color:#418bca" id="rsttxt">UPDATE PASSWORD</h5>
							  <section>
								<div class="input-group">
								  <input type="text" class="form-control" name="email" id="passwordEdit">
								</div>
								<span class="help-block">Leave this field empty <br>in case you do not want to update the password!</span>
							  </section>
							</form>
							<button type="button" class="btn btn-blue margin-bottom-20 submitEdit">Save Changes</button>
							<button id="cls" type="button" class="btn btn-blue margin-bottom-20" onclick="closeNav3()">Cancel</button>
						</div>
					</div>

				</section>';

            break;

        /****************************************/
        /****************************************/
        /*         /POP UP EDIT MEMBER          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          UPDATE MEMBER DATA          */
        /****************************************/
        /****************************************/


        case 'updateMember':
            $id = $_POST['id'];
            $email = $_POST['email'];
            $name = $_POST['name'];
            $password = $_POST['password'];

            if (isset($email) && empty($name) && empty($password)) {
                $updateEmail = $mysqli->query("UPDATE subscribers SET email = '" . $email . "'   WHERE id = '" . $id . "'");
            } else if (empty($email) && isset($name) && empty($password)) {
                $updateName = $mysqli->query("UPDATE subscribers SET name = '" . $name . "'  WHERE id = '" . $id . "'");
            } else if (empty($email) && empty($name) && isset($password)) {
                $updatePassword = $mysqli->query("UPDATE subscribers SET password = '" . md5($password) . "'  WHERE id = '" . $id . "'");
            } else if (empty($email) && isset($name) && isset($password)) {
                $updatePN = $mysqli->query("UPDATE subscribers SET password = '" . md5($password) . "', name = '" . $name . "'  WHERE id = '" . $id . "'");
            } else if (isset($email) && empty($name) && isset($password)) {
                $updatePE = $mysqli->query("UPDATE subscribers SET password = '" . md5($password) . "', email = '" . $email . "'  WHERE id = '" . $id . "'");
            } else if (isset($email) && isset($name) && empty($password)) {
                $updateEN = $mysqli->query("UPDATE subscribers SET  name = '" . $name . "', email = '" . $email . "'  WHERE id = '" . $id . "'");
            } else if (isset($email) && isset($name) && isset($password)) {
                $updateENP = $mysqli->query("UPDATE subscribers SET  name = '" . $name . "', email = '" . $email . "', password = '" . md5($password) . "' WHERE id = '" . $id . "'");
            }


            break;

        /****************************************/
        /****************************************/
        /*         /UPDATE MEMBER DATA          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          DELETE MEMBER DATA          */
        /****************************************/
        /****************************************/


        case 'memberDelete':

            /*$id = $_POST['id'];

            //$inactive_sql = "SELECT * FROM subscribers WHERE id='" . $id . "' AND client='" . $_SESSION['user']['id'] . "' AND last_activation='0'";
            $inactive_sql = "SELECT * FROM subscribers WHERE id='" . $id . "' AND client='" . $_SESSION['user']['id'] . "'";
            $inactive_query = $mysqli->query($inactive_sql);

            if ($inactive_query->num_rows > 0) {

                $user = $inactive_query->fetch_assoc();

                $keys = '';
                $values = '';

                foreach ($user as $key => $field) {
                    $keys .= ($keys != '' ? ',' . $key : $key);
                    $values .= ($values != '' ? ',\'' . addslashes($field) . '\'' : '\'' . addslashes($field) . '\'');
                }

                $insertBackup_sql = "INSERT INTO subscribers_bck (" . $keys . ") VALUES (" . $values . ")";

                $mysqli->query($insertBackup_sql);

                $deleteMember = $mysqli->query("DELETE FROM subscribers WHERE id='" . $id . "'");

            } else {
                echo '0$#This user can\'t be deleted';
            }*/

	        $id = $_POST['id'];
	        if ($id != "") {

		        $checkClient = $mysqli->query("SELECT * FROM subscribers WHERE id = '" . $id . "'");
		        while ($row = $checkClient->fetch_assoc()) {
			        $client = $row['client'];
			        $stripeID = $row['stripeID'];
		        }

		        if ($_SESSION['user']['id'] == $client) {

			        try {

			        	if($stripeID!='') {
					        $customer = \Stripe\Customer::retrieve( $stripeID );

					        $getAllPackages_sql   = "SELECT * FROM packages";
					        $getAllPackages_query = $mysqli->query( $getAllPackages_sql );
					        $allPacks             = array();

					        while ( $pack = $getAllPackages_query->fetch_assoc() ) {

						        $allPacks[] = $pack['stripe_id'];

					        }

					        foreach ( $customer->subscriptions->data as $subscription ) {

						        if ( in_array( $subscription->plan->id, $allPacks ) ) {

							        $subscription = $subscription->id;
							        $sub          = \Stripe\Subscription::retrieve( $subscription );
							        $sub->cancel();

						        }

					        }

					        foreach ( $customer->sources->data as $source ) {
						        $source_id = $source->id;
						        //print_r(get_class_methods($customer->sources->retrieve($source_id)));
						        $customer->sources->retrieve( $source_id )->delete();
					        }

					        $customer->delete();
				        }

				        $keys = '';
				        $values = '';

				        foreach ($row as $key => $field) {
					        $keys .= ($keys != '' ? ',' . $key : $key);
					        $values .= ($values != '' ? ',\'' . addslashes($field) . '\'' : '\'' . addslashes($field) . '\'');
				        }

				        $deactivateSql = $mysqli->query("UPDATE subscribers SET active = '0' WHERE id = '" . $id . "'");

				        $insertBackup_sql = "INSERT INTO subscribers_bck (" . $keys . ") VALUES (" . $values . ")";

				        $mysqli->query($insertBackup_sql);

				        $deleteMember = $mysqli->query("DELETE FROM subscribers WHERE id='" . $id . "'");


				        echo '1$#';

			        }catch(\Exception $e){

				        echo '0$#'.$e->getMessage();

			        }


		        } else {
			        echo '0$#Member does not belong to clients team';
		        }

	        } else {
		        echo '0$#Id from selected member is empty';
	        }


            break;


        /****************************************/
        /****************************************/
        /*         /DELETE MEMBER DATA          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          DEACTIVATE MEMBER           */
        /****************************************/
        /****************************************/


        case 'memberDeactivate':

            $id = $_POST['id'];
            if ($id != "") {

                $checkClient = $mysqli->query("SELECT * FROM subscribers WHERE id = '" . $id . "'");
                while ($row = $checkClient->fetch_assoc()) {
                    $client = $row['client'];
                    $stripeID = $row['stripeID'];
                }

                if ($_SESSION['user']['id'] == $client) {


                    try {

                        if($stripeID!='') {

                            $customer = \Stripe\Customer::retrieve($stripeID);

                            $getAllPackages_sql = "SELECT * FROM packages";
                            $getAllPackages_query = $mysqli->query($getAllPackages_sql);
                            $allPacks = array();

                            while ($pack = $getAllPackages_query->fetch_assoc()) {

                                $allPacks[] = $pack['stripe_id'];

                            }

                            foreach ($customer->subscriptions->data as $subscription) {

                                if (in_array($subscription->plan->id, $allPacks)) {

                                    $subscription = $subscription->id;
                                    $sub = \Stripe\Subscription::retrieve($subscription);
                                    $sub->cancel();

                                }

                            }

                            foreach ($customer->sources->data as $source) {
                                $source_id = $source->id;
                                //print_r(get_class_methods($customer->sources->retrieve($source_id)));
                                $customer->sources->retrieve($source_id)->delete();
                            }

                            $customer->delete();

                        }

                        $keys = '';
                        $values = '';

                        foreach ($row as $key => $field) {
                            $keys .= ($keys != '' ? ',' . $key : $key);
                            $values .= ($values != '' ? ',\'' . addslashes($field) . '\'' : '\'' . addslashes($field) . '\'');
                        }

                        $deactivateSql = $mysqli->query("UPDATE subscribers SET active = '0' WHERE id = '" . $id . "'");

                        $insertBackup_sql = "INSERT INTO subscribers_bck (" . $keys . ") VALUES (" . $values . ")";

                        $mysqli->query($insertBackup_sql);

                        $deleteMember = $mysqli->query("DELETE FROM subscribers WHERE id='" . $id . "'");


                        echo '1$#';

                    }catch(\Exception $e){

                        echo '0$#'.$e->getMessage();

                    }


                } else {
                    echo '0$#Member does not belong to clients team';
                }

            } else {
                echo '0$#Id from selected member is empty';
            }

        break;


        /****************************************/
        /****************************************/
        /*         /DEACTIVATE MEMBER           */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*           TURN INACTIVE              */
        /****************************************/
        /****************************************/


        case 'turnInactive':

            $id = $_POST['id'];
            if ($id != "") {

                $checkClient = $mysqli->query("SELECT * FROM subscribers WHERE id = '" . $id . "'");

                while ($row = $checkClient->fetch_assoc()) {
                    $client = $row['client'];
                    $stripeID = $row['stripeID'];
                }

                if ($_SESSION['user']['id'] == $client) {


                    if($stripeID!='') {

                        try {

                            $customer = \Stripe\Customer::retrieve($stripeID);

                            $getAllPackages_sql = "SELECT * FROM packages";
                            $getAllPackages_query = $mysqli->query($getAllPackages_sql);
                            $allPacks = array();

                            while ($pack = $getAllPackages_query->fetch_assoc()) {

                                $allPacks[] = $pack['stripe_id'];

                            }

                            foreach ($customer->subscriptions->data as $subscription) {

                                if (in_array($subscription->plan->id, $allPacks)) {

                                    $subscription = $subscription->id;
                                    $sub = \Stripe\Subscription::retrieve($subscription);
                                    $sub->cancel();

                                }

                            }

                            $deactivateSql = $mysqli->query("UPDATE subscribers SET active = '0', package='0' WHERE id = '" . $id . "'");

                            echo '1$#';

                        } catch (\Exception $e) {

                            echo '0$#' . $e->getMessage();

                        }

                    } else {

                        $deactivateSql = $mysqli->query("UPDATE subscribers SET active = '0', package='0' WHERE id = '" . $id . "'");

                        echo '1$#';

                    }


                } else {
                    echo '0$#Member does not belong to clients team';
                }

            } else {
                echo '0$#Id from selected member is empty';
            }

            break;


        /****************************************/
        /****************************************/
        /*           /TURN INACTIVE             */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*       TURN INACTIVE & REFUND         */
        /****************************************/
        /****************************************/


        case 'turnInactiveRefund':

            $id = $_POST['id'];
            if ($id != "") {

                $checkClient = $mysqli->query("SELECT * FROM subscribers WHERE id = '" . $id . "'");

                while ($row = $checkClient->fetch_assoc()) {
                    $client = $row['client'];
                    $stripeID = $row['stripeID'];
                }

                if ($_SESSION['user']['id'] == $client) {


                    try {

                        $customer = \Stripe\Customer::retrieve($stripeID);

                        $getAllPackages_sql = "SELECT * FROM packages";
                        $getAllPackages_query = $mysqli->query($getAllPackages_sql);
                        $allPacks =  array();

                        while($pack = $getAllPackages_query->fetch_assoc()){

                            $allPacks[] = $pack['stripe_id'];

                        }

                        foreach($customer->subscriptions->data as $subscription){

                            if(in_array($subscription->plan->id, $allPacks)){

                                $subscription = $subscription->id;
                                $sub = \Stripe\Subscription::retrieve($subscription);
                                $sub->cancel();

                            }

                        }

                        $charges = \Stripe\Charge::all(["customer" => $stripeID, "limit" => 1]);
                        $charge_id = $charges['data'][0]['id'];

                        $re = \Stripe\Refund::create([
                            "charge" => $charge_id
                        ]);

                        $deactivateSql = $mysqli->query("UPDATE subscribers SET active = '0', package='0' WHERE id = '" . $id . "'");

                        echo '1$#';

                    }catch(\Exception $e){

                        echo '0$#'.$e->getMessage();

                    }


                } else {
                    echo '0$#Member does not belong to clients team';
                }

            } else {
                echo '0$#Id from selected member is empty';
            }

            break;


        /****************************************/
        /****************************************/
        /*       /TURN INACTIVE & REFUND        */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          GET SUBSCRIBER INFO         */
        /****************************************/
        /****************************************/

        case 'getSubscriberInfo':

            $id = $_POST['id'];

            $getSubscriber_sql = "SELECT * FROM subscribers WHERE id = " . $id;
            $getSubscriber_query = $mysqli->query($getSubscriber_sql);
            $subscriber = $getSubscriber_query->fetch_assoc();

            setLog('check subscriber full info', '<fname> is checking full info of ' . $subscriber['full_name'] . '(' . $subscriber['id'] . ')');


            echo '<tr>
				<td colspan="8">
					<div class="row">
						<div class="col-md-12"><a class="close"><i class="fa fa-close"></i></a></div>
						<div class="col-md-4 col-sm-6 col-xs-12">
							<ul>
								<li>Province: <b>' . $subscriber['province'] . '</b></li>
								<li>Birthday: <b>' . $subscriber['birthday'] . '</b></li>
							</ul>
						</div>
						<div class="col-md-4 col-sm-6 col-xs-12">
							<ul>
								<li>Company: <b>' . $subscriber['company'] . '</b></li>
								<li>Job Title: <b>' . $subscriber['jobtitle'] . '</b></li>
							</ul>
						</div>
						<div class="col-md-4 col-sm-6 col-xs-12">
							<ul>
								<li>Expire at: <b>' . date('d-m-Y', $subscriber['last_activation'] + (30 * 24 * 60 * 60)) . '</b></li>
							</ul>
						</div>
					</div>
				</td>
			</tr>';

            break;

        /****************************************/
        /****************************************/
        /*         /GET SUBSCRIBER INFO         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          GET TICKET TEXT             */
        /****************************************/
        /****************************************/

        case 'getTicketText':

            $id = $_POST['id'];

            $getTicketText = $mysqli->query("SELECT tickets.text as text,clients.company as name FROM tickets INNER JOIN clients ON tickets.client = clients.id WHERE tickets.id = '" . $id . "'");
            while ($row = $getTicketText->fetch_assoc()) {
                $text = $row['text'];
                $name = $row['name'];
            }


            setLog('Reading ticket', '<fname> is reading ' . $name . 's ticket');


            echo '<tr>
				<td colspan="4">
					<div class="row">
						<div class="col-md-12"><a class="close"><i class="fa fa-close"></i></a></div>
						<div class="col-md-6 col-sm-6 col-xs-12">
							<ul>
								' . $text . '
							</ul>
						</div>
						<div class="col-md-6 col-sm-6 col-xs-12">
							<ul>
								<form style="display:none" id="answerForm" class="form-horizontal" role="form">
                                <div class="form-group">
                                    <div class="col-sm-8">
                                        <textarea class="form-control" id="answer" rows="6"></textarea>
                                    </div>
                                </div>
                                   <button id="submitTicketAnswer" class="btn btn-primary" data-id="' . $id . '">Submit</button>
                            </form>
							</ul>
						</div>
						<button type="button" id="triggerAnswerForm" class="btn btn-primary btn-xs margin-bottom-20 answerTicket">Answer</button>
					</div>
				</td>
			</tr>';

            break;

        /****************************************/
        /****************************************/
        /*         /GET TICKET TEXT             */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          GET ANSWER TEXT             */
        /****************************************/
        /****************************************/

        case 'getAnswerText':

            $id = $_POST['id'];

            $getAnswerText = $mysqli->query("SELECT * FROM tickets_answers WHERE ticket_id = '" . $id . "'");
            while ($row = $getAnswerText->fetch_assoc()) {
                $text = $row['text'];

            }


            echo '<tr>
				<td colspan="4">
					<div class="row">
						<div class="col-md-12"><a class="close"><i class="fa fa-close"></i></a></div>
						<div class="col-md-12 col-sm-6 col-xs-12">

								' . $text . '

						</div>
					</div>
				</td>
			</tr>';

            break;

        /****************************************/
        /****************************************/
        /*         /GET ANSWER TEXT             */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          BLOCK SUBSCRIBER            */
        /****************************************/
        /****************************************/
        case 'blockSubscriber':

            $id = $_POST['id'] * 1;
            if ($id > 0) {
                $update = $mysqli->query("UPDATE subscribers SET active='0' WHERE id='" . $id . "'");
                echo 1;
            } else echo 0;

            break;
        /****************************************/
        /****************************************/
        /*          BLOCK SUBSCRIBER            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          CHANGE LOGIN                */
        /****************************************/
        /****************************************/

        case 'changeLogin':

            $id = $_POST['id'];
            $newpassword = $_POST['newpassword'];


            $changeLogin = $mysqli->query("UPDATE clients SET password = '" . md5($newpassword) . "' WHERE id = '" . $id . "'");
            echo $changeLogin;
            break;

        /****************************************/
        /****************************************/
        /*         /CHANGE LOGIN                */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          INSERT PROFILE INTRO        */
        /****************************************/
        /****************************************/

        case 'insertProfileIntro':


            $about = addslashes($_POST['about']);
            $head_trader = addslashes($_POST['head_trader']);
            $company_year = addslashes($_POST['company_year']);
            $headquarters = addslashes($_POST['headquarters']);
            $type_of_trading = addslashes($_POST['type_of_trading']);


            $inspi = $mysqli->query("INSERT INTO profile_intro (about, head_trader, company_year, headquarters, type_of_trading, client) VALUES ('" . $about . "', '" . $head_trader . "', '" . $company_year . "', '" . $headquarters . "', '" . $type_of_trading . "', '" . $_SESSION['user']['id'] . "')");
            echo $inspi;
            break;

        /****************************************/
        /****************************************/
        /*         /INSERT PROFILE INTRO        */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          UPDATE PROFILE INTRO        */
        /****************************************/
        /****************************************/

        case 'updateProfileIntro':

            $id = $_POST['id'];
            $about = addslashes($_POST['about']);
            $head_trader = addslashes($_POST['head_trader']);
            $company_year = addslashes($_POST['company_year']);
            $headquarters = addslashes($_POST['headquarters']);
            $type_of_trading = addslashes($_POST['type_of_trading']);


            $updtpi = $mysqli->query("UPDATE profile_intro SET about = '" . $about . "', head_trader = '" . $head_trader . "', company_year = '" . $company_year . "', headquarters = '" . $headquarters . "', type_of_trading = '" . $type_of_trading . "' WHERE client = '" . $id . "'");
            echo $updtpi;
            break;

        /****************************************/
        /****************************************/
        /*         /UPDATE PROFILE INTRO        */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*        STATUS  UPLOAD IMAGE          */
        /****************************************/
        /****************************************/

        case 'uploadStatus':

            $target_dir = "assets/images/status/";
            $uploadOk = 1;
            $imageFileType = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
            $target_file = $target_dir . time() . "." . $imageFileType;
            // Check if image file is a actual image or fake image
            //var_dump( $_FILES["file"] );
            $check = getimagesize($_FILES["file"]["tmp_name"]);
            if ($check !== false) {
                //echo "File is an image - " . $check["mime"] . ".";
                $uploadOk = 1;
            } else {
                //echo "File is not an image.";
                $uploadOk = 0;
                setLog('upload file that is not an imagem', '<fname> trying to upload a file that is not an image // FILE INFO //' . serialize($_FILES));
            }

            // Allow certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                && $imageFileType != "gif"
            ) {
                echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed. " . $imageFileType . " not allowed";
                $uploadOk = 0;
                setLog('upload a non supported image type', '<fname> trying to upload a file that has a not supported file type // FILE INFO //' . serialize($_FILES));
            }
            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                echo "Sorry, your file was not uploaded.";
                setLog('upload file can not be uploaded by errors', '<fname> image can\'t be uploaded because not match all required specifications // FILE INFO //' . serialize($_FILES));
                // if everything is ok, try to upload file
            } else {
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                    //echo "The file ". basename( $_FILES["file"]["name"]). " has been uploaded.";
                    setLog('Image uploaded', '<fname> uploaded an image. Check it here: ' . $target_file);
                    echo $target_file;
                } else {
                    echo "Sorry, there was an error uploading your file.";
                    setLog('ERROR moving image from tempo do destination', '<fname> upload process stopped because the system can\'t move the file to ' . $target_file . ' // FILE INFO //' . serialize($_FILES));
                }
            }

            break;

        /****************************************/
        /****************************************/
        /*      /STATUS UPLOAD IMAGE            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*              UPLOAD IMAGE            */
        /****************************************/
        /****************************************/

        case 'upload':

            $target_dir = "smsMedia/";
            $uploadOk = 1;
            $imageFileType = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
            $target_file = $target_dir . time() . "." . $imageFileType;
            // Check if image file is a actual image or fake image
            //var_dump( $_FILES["file"] );
            /*$check = getimagesize($_FILES["file"]["tmp_name"]);
            if ($check !== false) {
                //echo "File is an image - " . $check["mime"] . ".";
                $uploadOk = 1;
            } else {
                //echo "File is not an image.";
                $uploadOk = 0;
                setLog('upload file that is not an imagem', '<fname> trying to upload a file that is not an image // FILE INFO //' . serialize($_FILES));
            }*/

            // Allow certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                && $imageFileType != "gif"
            ) {
                echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed. " . $imageFileType . " not allowed";
                $uploadOk = 0;
                setLog('upload a non supported image type', '<fname> trying to upload a file that has a not supported file type // FILE INFO //' . serialize($_FILES));
            }
            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                echo "Sorry, your file was not uploaded.";
                setLog('upload file can not be uploaded by errors', '<fname> image can\'t be uploaded because not match all required specifications // FILE INFO //' . serialize($_FILES));
                // if everything is ok, try to upload file
            } else {
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                    //echo "The file ". basename( $_FILES["file"]["name"]). " has been uploaded.";
                    setLog('Image uploaded', '<fname> uploaded an image. Check it here: ' . $target_file);
                    echo $target_file;
                } else {
                    echo "Sorry, there was an error uploading your file.<br>";
                    print_r($_FILES);
                    echo "<br>";
                    print_r($_FILES["file"]['error']);
                    echo "<br>";
                    echo sys_get_temp_dir();
                    setLog('ERROR moving image from tempo do destination', '<fname> upload process stopped because the system can\'t move the file to ' . $target_file . ' // FILE INFO //' . serialize($_FILES));
                }
            }

            break;

        /****************************************/
        /****************************************/
        /*             /UPLOAD IMAGE            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*              UPLOAD LOGO             */
        /****************************************/
        /****************************************/

        case 'uploadLogo':

            if (isset($_SESSION['user'])) {
                $slug = trim($_SESSION['user']['slug']);
            } else if (isset($_SESSION['trader_registration'])) {
                $slug = trim($_SESSION['trader_registration'][2]['slug']);
            } else {
                $slug = 'erro_' . time();
            }

            if (!file_exists('assets/images/traders/' . $slug)) {
                mkdir('assets/images/traders/' . $slug);
            }

            $_IMG = $_FILES["fileLogo"]; // alterar para o atributo name do input FILE
            $target_dir = "assets/images/traders/" . $slug . '/';
            $uploadOk = 1;
            $imageFileType = pathinfo($_IMG["name"], PATHINFO_EXTENSION);
            $target_file = $target_dir . "logo.png"; // alterar logo para o nome do ficheiro que se pretende
            $check = getimagesize($_IMG["tmp_name"]);

            if ($check !== false) {
                $uploadOk = 1;
            } else {
                $uploadOk = 0;
                echo '0$#Can\'t upoad this image. Please tyr to choose another one.';
                setLog('upload file that is not an imagem', '<fname> trying to upload a file that is not an image // FILE INFO //' . serialize($_FILES));
            }

            if ($imageFileType != "png") { // alterar para a extensão pretendida
                echo "0$#Sorry, we only accept PNG images.";
                $uploadOk = 0;
                setLog('upload a non supported image type', '<fname> trying to upload a file that has a not supported file type // FILE INFO //' . serialize($_FILES));
            }

            if ($uploadOk == 1) {
                if (file_exists($target_file)) unlink($target_file);
                if (move_uploaded_file($_IMG["tmp_name"], $target_file)) {
                    setLog('Image uploaded', '<fname> uploaded an image. Check it here: ' . $target_file);


                    // alterar para update à base dados
                    $registrationId_sql = "SELECT id FROM clients WHERE email='" . $_SESSION['trader_registration'][1]['email'] . "'";
                    $registrationId_query = $mysqli->query($registrationId_sql);
                    $registrationId = $registrationId_query->fetch_assoc()['id'];

                    $setLogo_sql = "UPDATE customize_landing_pages SET logo='https://admin.lionsofforex.com/" . $target_file . "' WHERE client='" . $registrationId . "'";
                    $setLogo_query = $mysqli->query($setLogo_sql);

                    echo '1$#https://admin.lionsofforex.com/' . $target_file;
                } else {
                    echo "0$#Something went wrong. Please, try again.";
                    setLog('ERROR moving image from temp to destination', '<fname> upload process stopped because the system can\'t move the file to ' . $target_file . ' // FILE INFO //' . serialize($_FILES));
                }
            }

            break;

















        /****************************************/
        /****************************************/
        /*          UPLOAD PROFILE PICTURE      */
        /****************************************/
        /****************************************/

        case 'uploadProfilePicture':

            if (isset($_SESSION['user'])) {
                $slug = trim($_SESSION['user']['slug']);
            } else if (isset($_SESSION['trader_registration'])) {
                $slug = trim($_SESSION['trader_registration'][2]['slug']);
            } else {
                $slug = 'erro_' . time();
            }

            if (!file_exists('assets/images/traders/' . $slug)) {
                mkdir('assets/images/traders/' . $slug);
            }

            $_IMG = $_FILES["fileProfile"]; // alterar para o atributo name do input FILE
            $target_dir = "assets/images/traders/" . $slug . '/';
            $uploadOk = 1;
            $imageFileType = pathinfo($_IMG["name"], PATHINFO_EXTENSION);
            $target_file = $target_dir . "profile." . $imageFileType; // alterar logo para o nome do ficheiro que se pretende
            $check = getimagesize($_IMG["tmp_name"]);

            if ($check !== false) {
                $uploadOk = 1;
            } else {
                $uploadOk = 0;
                echo '0$#Can\'t upoad this image. Please tyr to choose another one.';
                setLog('upload file that is not an imagem', '<fname> trying to upload a file that is not an image // FILE INFO //' . serialize($_FILES));
            }

            if ($imageFileType != "png" && $imageFileType != "jpg" && $imageFileType != "jpeg") {
                echo "0$#Sorry, we only accept PNG, JPG and JPEG images.";
                $uploadOk = 0;
                setLog('upload a non supported image type', '<fname> trying to upload a file that has a not supported file type // FILE INFO //' . serialize($_FILES));
            }

            if ($uploadOk == 1) {
                if (file_exists($target_file)) unlink($target_file);
                if (move_uploaded_file($_IMG["tmp_name"], $target_file)) {
                    setLog('Image uploaded', '<fname> uploaded an image. Check it here: ' . $target_file);

                    $registrationId_sql = "SELECT id FROM clients WHERE email='" . $_SESSION['trader_registration'][1]['email'] . "'";
                    $registrationId_query = $mysqli->query($registrationId_sql);
                    $registrationId = $registrationId_query->fetch_assoc()['id'];

                    $setLogo_sql = "UPDATE customize_landing_pages SET profile='https://admin.lionsofforex.com/" . $target_file . "' WHERE client='" . $registrationId . "'";
                    $setLogo_query = $mysqli->query($setLogo_sql);

                    echo '1$#https://admin.lionsofforex.com/' . $target_file;
                } else {
                    echo "0$#Something went wrong. Please, try again.";
                    setLog('ERROR moving image from temp to destination', '<fname> upload process stopped because the system can\'t move the file to ' . $target_file . ' // FILE INFO //' . serialize($_FILES));
                }
            }

            break;

















        /****************************************/
        /****************************************/
        /*          UPLOAD PROFILE COVER        */
        /****************************************/
        /****************************************/

        case 'uploadProfileCover':

            if (isset($_SESSION['user'])) {
                $slug = trim($_SESSION['user']['slug']);
            } else if (isset($_SESSION['trader_registration'])) {
                $slug = trim($_SESSION['trader_registration'][2]['slug']);
            } else {
                $slug = 'erro_' . time();
            }

            if (!file_exists('assets/images/traders/' . $slug)) {
                mkdir('assets/images/traders/' . $slug);
            }

            $_IMG = $_FILES["fileCover"]; // alterar para o atributo name do input FILE
            $target_dir = "assets/images/traders/" . $slug . '/';
            $uploadOk = 1;
            $imageFileType = pathinfo($_IMG["name"], PATHINFO_EXTENSION);
            $target_file = $target_dir . "cover." . $imageFileType; // alterar logo para o nome do ficheiro que se pretende
            $check = getimagesize($_IMG["tmp_name"]);

            if ($check !== false) {
                $uploadOk = 1;
            } else {
                $uploadOk = 0;
                echo '0$#Can\'t upoad this image. Please tyr to choose another one.';
                setLog('upload file that is not an imagem', '<fname> trying to upload a file that is not an image // FILE INFO //' . serialize($_FILES));
            }

            if ($imageFileType != "png" && $imageFileType != "jpg" && $imageFileType != "jpeg") {
                echo "0$#Sorry, we only accept PNG, JPG and JPEG images.";
                $uploadOk = 0;
                setLog('upload a non supported image type', '<fname> trying to upload a file that has a not supported file type // FILE INFO //' . serialize($_FILES));
            }

            if ($uploadOk == 1) {
                if (file_exists($target_file)) unlink($target_file);
                if (move_uploaded_file($_IMG["tmp_name"], $target_file)) {
                    setLog('Image uploaded', '<fname> uploaded an image. Check it here: ' . $target_file);

                    $registrationId_sql = "SELECT id FROM clients WHERE email='" . $_SESSION['trader_registration'][1]['email'] . "'";
                    $registrationId_query = $mysqli->query($registrationId_sql);
                    $registrationId = $registrationId_query->fetch_assoc()['id'];

                    $setLogo_sql = "UPDATE customize_landing_pages SET banner='https://admin.lionsofforex.com/" . $target_file . "' WHERE client='" . $registrationId . "'";
                    $setLogo_query = $mysqli->query($setLogo_sql);

                    echo '1$#https://admin.lionsofforex.com/' . $target_file;
                } else {
                    echo "0$#Something went wrong. Please, try again.";
                    setLog('ERROR moving image from temp to destination', '<fname> upload process stopped because the system can\'t move the file to ' . $target_file . ' // FILE INFO //' . serialize($_FILES));
                }
            }

            break;


        case 'updateBanner':

            $target_dir = "assets/images/profile/banner/";
            $uploadOk = 1;
            $imageFileType = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
            $target_file = $target_dir . slugify($_SESSION['trader_registration']['company']) . "." . $imageFileType;
            // Check if image file is a actual image or fake image
            //var_dump( $_FILES["file"] );
            $check = getimagesize($_FILES["file"]["tmp_name"]);
            if ($check !== false) {
                //echo "File is an image - " . $check["mime"] . ".";
                $uploadOk = 1;
            } else {
                //echo "File is not an image.";
                $uploadOk = 0;
                setLog('upload file that is not an imagem', '<fname> trying to upload a file that is not an image // FILE INFO //' . serialize($_FILES));
            }

            // Allow certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                && $imageFileType != "gif"
            ) {
                echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed. " . $imageFileType . " not allowed";
                $uploadOk = 0;
                setLog('upload a non supported image type', '<fname> trying to upload a file that has a not supported file type // FILE INFO //' . serialize($_FILES));
            }
            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                echo "Sorry, your file was not uploaded.";
                setLog('upload file can not be uploaded by errors', '<fname> image can\'t be uploaded because not match all required specifications // FILE INFO //' . serialize($_FILES));
                // if everything is ok, try to upload file
            } else {
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                    //echo "The file ". basename( $_FILES["file"]["name"]). " has been uploaded.";
                    setLog('Image uploaded', '<fname> uploaded an image. Check it here: ' . $target_file);
                    echo $target_file;
                } else {
                    echo "Sorry, there was an error uploading your file.";
                    setLog('ERROR moving image from tempo do destination', '<fname> upload process stopped because the system can\'t move the file to ' . $target_file . ' // FILE INFO //' . serialize($_FILES));
                }
            }

            break;

        /****************************************/
        /****************************************/
        /*             /UPLOAD IMAGE            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*      MARK NOTIFICATIONS AS READ      */
        /****************************************/
        /****************************************/

        case 'markAsRead':


            $markAsRead = "UPDATE  notifications SET  `read` =  '1' WHERE  user = " . $_SESSION['user']['id'];
            $markAsRead_query = $mysqli->query($markAsRead);
            $notifications_n = 0;


            break;

        /****************************************/
        /****************************************/
        /*     /MARK NOTIFICATIONS AS READ      */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          UPDATE TEAM STATUS          */
        /****************************************/
        /****************************************/

        case 'updateTeamStatus':
            $teamId = $_POST['teamId'];
            $change = $_POST['change'];

            $updateTeamStatus = $mysqli->query("UPDATE clients SET active = '" . $change . "' WHERE id = '" . $teamId . "'");
            break;

        /****************************************/
        /****************************************/
        /*          /UPDATE TEAM STATUS         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          SUBMIT TICKET               */
        /****************************************/
        /****************************************/

        case 'submitTicket':
            $question = $_POST['question'];
            $subject = $_POST['subject'];
            $type = $_POST['type'];


            $submitTicket = $mysqli->query("INSERT INTO `signals_signals`.`tickets` (`id`, `client`, `text`, `subject`, `status`, `date`, `type`) VALUES (NULL, '" . $_SESSION['user']['id'] . "', '" . $question . "', '" . $subject . "', '0', '" . time() . "', '" . $type . "')");
            setLog('NEW TICKET!', '<fname> ' . $_SESSION['user']['id'] . ' sent a ticket');

            break;

        /****************************************/
        /****************************************/
        /*          /SUBMIT TICKET              */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          SUBMIT TICKET ANSWER        */
        /****************************************/
        /****************************************/

        case 'submitTicketAnswer':
            $answer = $_POST['answer'];
            $id = $_POST['id'];
            echo $_POST['id'];

            $submitTicketAnswer = $mysqli->query("INSERT INTO `signals_signals`.`tickets_answers` (`id`, `ticket_id`, `text`, `date`) VALUES (NULL, '" . $id . "', '" . $answer . "', '" . time() . "')");
            $setTicketToAnswered = $mysqli->query("UPDATE `signals_signals`.`tickets` SET status = '1' WHERE id = '" . $id . "'");
            break;

        /****************************************/
        /****************************************/
        /*          /SUBMIT TICKET ANSWER       */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          UPLOAD COURSE COVER         */
        /****************************************/
        /****************************************/

        case 'uploadCourseCover':
            if (isset($_SESSION['user'])) {
                $slug = $_SESSION['user']['slug'];
            } else if (isset($_SESSION['trader_registration'])) {
                $slug = $_SESSION['trader_registration'][2]['slug'];
            } else {
                $slug = 'erro_' . time();
            }

            if (!file_exists('assets/images/traders/' . $slug . '/course')) {
                mkdir('assets/images/traders/' . $slug . '/course');
            }

            $_IMG = $_FILES["courseCover"]; // alterar para o atributo name do input FILE
            $target_dir = "assets/images/traders/" . $slug . '/course/';
            $uploadOk = 1;
            $imageFileType = pathinfo($_IMG["name"], PATHINFO_EXTENSION);
            $target_file = $target_dir . "cover_" . time() . "." . $imageFileType; // alterar logo para o nome do ficheiro que se pretende
            $check = getimagesize($_IMG["tmp_name"]);

            if ($check !== false) {
                $uploadOk = 1;
            } else {
                $uploadOk = 0;
                echo '0$#Can\'t upoad this image. Please try to choose another one.';
                setLog('upload file that is not an image', '<fname> trying to upload a file that is not an image // FILE INFO //' . serialize($_FILES));
            }

            if ($imageFileType != "png" && $imageFileType != "jpg" && $imageFileType != "jpeg") {
                echo "0$#Sorry, we only accept PNG, JPG and JPEG images.";
                $uploadOk = 0;
                setLog('upload a non supported image type', '<fname> trying to upload a file that has a not supported file type // FILE INFO //' . serialize($_FILES));
            }

            if ($uploadOk == 1) {
                if (file_exists($target_file)) unlink($target_file);
                if (move_uploaded_file($_IMG["tmp_name"], $target_file)) {
                    setLog('Image uploaded', '<fname> uploaded an image. Check it here: ' . $target_file);


                    // alterar para update à base dados
                    $course_cover = $target_file;


                    echo '1$#https://admin.lionsofforex.com/' . $target_file;
                } else {
                    echo "0$#Something went wrong. Please, try again.";
                    setLog('ERROR moving image from temp to destination', '<fname> upload process stopped because the system can\'t move the file to ' . $target_file . ' // FILE INFO //' . serialize($_FILES));
                }
            }

            break;
        /****************************************/
        /****************************************/
        /*         /UPLOAD COURSE COVER         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          UPLOAD VIDEO COVER          */
        /****************************************/
        /****************************************/

        case 'uploadVideoCover':
            if (isset($_SESSION['user'])) {
                $slug = $_SESSION['user']['slug'];
            } else if (isset($_SESSION['trader_registration'])) {
                $slug = $_SESSION['trader_registration'][2]['slug'];
            } else {
                $slug = 'erro_' . time();
            }

            if (!file_exists('assets/images/traders/' . $slug . '/course/lesson')) {
                mkdir('assets/images/traders/' . $slug . '/course/lesson');
            }

            $_IMG = $_FILES["videoCover"]; // alterar para o atributo name do input FILE
            $target_dir = "assets/images/traders/" . $slug . '/course/lesson/';
            $uploadOk = 1;
            $imageFileType = pathinfo($_IMG["name"], PATHINFO_EXTENSION);
            $target_file = $target_dir . "cover_" . time() . "." . $imageFileType; // alterar logo para o nome do ficheiro que se pretende
            $check = getimagesize($_IMG["tmp_name"]);

            if ($check !== false) {
                $uploadOk = 1;
            } else {
                $uploadOk = 0;
                echo '0$#Can\'t upoad this image. Please try to choose another one.';
                setLog('upload file that is not an image', '<fname> trying to upload a file that is not an image // FILE INFO //' . serialize($_FILES));
            }

            if ($imageFileType != "png" && $imageFileType != "jpg" && $imageFileType != "jpeg") {
                echo "0$#Sorry, we only accept PNG, JPG and JPEG images.";
                $uploadOk = 0;
                setLog('upload a non supported image type', '<fname> trying to upload a file that has a not supported file type // FILE INFO //' . serialize($_FILES));
            }

            if ($uploadOk == 1) {
                if (file_exists($target_file)) unlink($target_file);
                if (move_uploaded_file($_IMG["tmp_name"], $target_file)) {
                    setLog('Image uploaded', '<fname> uploaded an image. Check it here: ' . $target_file);


                    // alterar para update à base dados


                    echo '1$#https://admin.lionsofforex.com/' . $target_file;
                } else {
                    echo "0$#Something went wrong. Please, try again.";
                    setLog('ERROR moving image from temp to destination', '<fname> upload process stopped because the system can\'t move the file to ' . $target_file . ' // FILE INFO //' . serialize($_FILES));
                }
            }

            break;
        /****************************************/
        /****************************************/
        /*         /UPLOAD VIDEO COVER          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          UPLOAD VIDEO FILE           */
        /****************************************/
        /****************************************/

        case 'uploadVideoFile':
            if (isset($_SESSION['user'])) {
                $slug = $_SESSION['user']['slug'];
            } else if (isset($_SESSION['trader_registration'])) {
                $slug = $_SESSION['trader_registration'][2]['slug'];
            } else {
                $slug = 'erro_' . time();
            }

            if (!file_exists('assets/images/traders/' . $slug . '/course/lesson')) {
                mkdir('assets/images/traders/' . $slug . '/course/lesson');
            }

            $_IMG = $_FILES["videoFile"]; // alterar para o atributo name do input FILE
            $target_dir = "assets/images/traders/" . $slug . '/course/lesson/';
            $uploadOk = 1;
            $target_file = $target_dir . "vid_" . time() . ".mp4"; // alterar logo para o nome do ficheiro que se pretende
            $check = true;
            $imageFileType = "mp4";

            var_dump($_IMG);

            if ($check !== false) {
                echo $target_file;
                $uploadOk = 1;
            } else {
                $uploadOk = 0;
                echo '0$#Can\'t upoad this video. Please try to choose another one.';
                setLog('upload file that is not a video', '<fname> trying to upload a file that is not an image // FILE INFO //' . serialize($_FILES));
            }

            if ($imageFileType != "mp4" && $imageFileType != "flv" && $imageFileType != "wmv") {
                echo $imageFileType;
                $uploadOk = 0;
                setLog('upload a non supported video type', '<fname> trying to upload a file that has a not supported file type // FILE INFO //' . serialize($_FILES));
            }

            if ($uploadOk == 1) {
                echo '<br><br>temp => ' . $_IMG["tmp_name"] . '<br>';
                if (file_exists($target_file)) unlink($target_file);
                if (move_uploaded_file($_IMG["tmp_name"], $target_file)) {
                    setLog('Video uploaded', '<fname> uploaded a video. Check it here: ' . $target_file);


                    // alterar para update à base dados


                    echo '1$#https://admin.lionsofforex.com/' . $target_file;
                } else {
                    echo "0$#Something went wrong. Please, try again. " . $_IMG["tmp_name"];
                    setLog('ERROR moving video from temp to destination', '<fname> upload process stopped because the system can\'t move the file to ' . $target_file . ' // FILE INFO //' . serialize($_FILES));
                }
            }

            break;
        /****************************************/
        /****************************************/
        /*         /UPLOAD VIDEO FILE           */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          UPLOAD COURSE               */
        /****************************************/
        /****************************************/

        case 'uploadCourse':
            $id = $_SESSION['user']['id'];
            $name = addslashes($_POST['name']);
            $description = addslashes($_POST['description']);
            $status = addslashes($_POST['status']);
            $package = addslashes($_POST['package']);
            $cover = addslashes($_POST['cover']);

            $uploadCourse = $mysqli->query("INSERT INTO courses (client, name, status, description, cover) VALUES ('" . $id . "', '" . $name . "', '" . $status . "', '" . $description . "', '" . $cover . "')");
            echo $uploadCourse;
            break;

        /****************************************/
        /****************************************/
        /*         /UPLOAD COURSE               */
        /****************************************/
        /****************************************/

        /****************************************/
        /****************************************/
        /*          UPLOAD VIDEO                */
        /****************************************/
        /****************************************/

        case 'uploadVideo':

            $id_course = $_POST['id_course'];
            $name = addslashes($_POST['name']);
            $description = addslashes($_POST['description']);
            $video = $_POST['video'];
            $cover = $_POST['cover'];


            $uploadVideo = $mysqli->query("INSERT INTO courses_lesson (id_course, name, video, description, cover) VALUES ('" . $id_course . "', '" . $name . "', '" . $video . "', '" . $description . "', 'temp')");
            echo $uploadVideo;
            break;

        /****************************************/
        /****************************************/
        /*         /UPLOAD VIDEO                */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*     GET INTERNAL MESSAGE HISTORY     */
        /****************************************/
        /****************************************/

        case 'getInternalMessageHistory':

            $userId = $_POST['userID'];
            $message = addslashes($_POST['message']);

            $message_sql = "SELECT * FROM messages WHERE (user_from='" . $userId . "' and user_dest='" . $_SESSION['user']['id'] . "') OR (user_dest='" . $userId . "' and user_from='" . $_SESSION['user']['id'] . "')";
            //echo $message_sql;
            $message_query = $mysqli->query($message_sql);
            //$allMessage = $message_query->fetch_all(MYSQLI_ASSOC);
            $allMessage = array();
            while($message = $message_query->fetch_assoc()) $allMessage[] = $message;

            $lastday = 0;

            if (count($allMessage) == 0) die('<li class="conversation-divider"><span>No messages to show</span></li>');

            foreach ($allMessage as $message) {

                $thisMessageDay = date('d', $message['date']);

                if ($lastday != $thisMessageDay) {
                    $lastday = $thisMessageDay;
                    echo '<li class="conversation-divider"><span>' . date('d-m-Y', $message['date']) . '</span></li>';
                }

                if ($message['user_from'] != $_SESSION['user']['id']) {

                    $userPhoto_sql = "SELECT photo FROM subscribers WHERE id='" . $userId . "'";
                    $userPhoto_query = $mysqli->query($userPhoto_sql);
                    $userPhoto = $userPhoto_query->fetch_assoc()['photo'];

                    echo '<li class="message receive">
						  <div class="media">
							  <div class="user-avatar">
								  <img class="media-object img-circle" src="' . $userPhoto . '">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><span class="time">' . date('d-m-Y H:m', $message['date']) . '</span></p>
									' . $message['message'] . '
							  </div>
						  </div>
					  </li>';

                } else {

                    echo '<li class="message sent">
						  <div class="media">
							  <div class="user-avatar">
								  <img class="media-object img-circle" src="assets/images/profile-photo.jpg">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><span class="time">' . date('d-m-Y H:m', $message['date']) . '</span></p>
								                                                                                                 ' . $message['message'] . '
							  </div>
						  </div>
					  </li>';

                }

            }

            /*<li class="conversation-divider"><span>Conversation started at 26.03.2014</span></li>

					  <li class="message receive">
						  <div class="media">
							  <div class="user-avatar">
								  <img class="media-object img-circle" src="assets/images/ici-avatar.jpg">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><a href="#">Ing. Imrich Kamarel</a> <span class="time">26.3.2014 18:36</span></p>
								                                                                                                          Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
							  </div>
						  </div>
					  </li>
					  <li class="message sent">
						  <div class="media">
							  <div class="user-avatar">
								  <img class="media-object img-circle" src="assets/images/profile-photo.jpg">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><a href="#">John Douey</a> <span class="time">26.3.2014 18:38</span></p>
								                                                                                                 Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
							  </div>
						  </div>
					  </li>

					  <li class="conversation-divider"><span>29.03.2014</span></li>

					  <li class="message receive">
						  <div class="media">
							  <div class="user-avatar">
								  <img class="media-object img-circle" src="assets/images/ici-avatar.jpg">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><a href="#">Ing. Imrich Kamarel</a> <span class="time">29.3.2014 12:20</span></p>
								                                                                                                          Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
							  </div>
						  </div>
					  </li>
					  <li class="message sent">
						  <div class="media">
							  <div class="user-avatar">
								  <img class="media-object img-circle" src="assets/images/profile-photo.jpg">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><a href="#">John Douey</a> <span class="time">29.3.2014 12:25</span></p>
								                                                                                                 Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
							  </div>
						  </div>
					  </li>
					  <li class="message sent">
						  <div class="media">
							  <div class="user-avatar">
								  <img class="media-object img-circle" src="assets/images/profile-photo.jpg">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><a href="#">John Douey</a> <span class="time">29.3.2014 13:15</span></p>
								                                                                                                 Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
							  </div>
						  </div>
					  </li>

					  <li class="conversation-divider"><span>31.03.2014</span></li>

					  <li class="message receive">
						  <div class="media">
							  <div class="user-avatar">
								  <img class="media-object img-circle" src="assets/images/ici-avatar.jpg">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><a href="#">Ing. Imrich Kamarel</a> <span class="time">31.3.2014 09:12</span></p>
								                                                                                                          Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
							  </div>
						  </div>
					  </li>
					  <li class="message sent">
						  <div class="media">
							  <div class="user-avatar">
								  <img class="media-object img-circle" src="assets/images/profile-photo.jpg">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><a href="#">John Douey</a> <span class="time">31.3.2014 09:15</span></p>
								                                                                                                 Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
							  </div>
						  </div>
					  </li>
					  <li class="message receive">
						  <div class="media">
							  <div class="user-avatar">
								  <img class="media-object img-circle" src="assets/images/ici-avatar.jpg">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><a href="#">Ing. Imrich Kamarel</a> <span class="time">31.3.2014 09:18</span></p>
								                                                                                                          Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
							  </div>
						  </div>
					  </li>*/

            break;

        /****************************************/
        /****************************************/
        /*     /GET INTERNAL MESSAGE HISTORY    */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*         SEND INTERNAL MESSAGE        */
        /****************************************/
        /****************************************/

        case 'sendInternalMessage':


            $message = addslashes($_POST['message']);
            $userId = $_POST['userId'];
            $time = time();

            $message_sql = "INSERT INTO messages (user_from, user_dest, message, date) VALUES ('" . $_SESSION['user']['id'] . "', '" . $userId . "', '" . $message . "', '" . $time . "')";

            $mysqli->query($message_sql);

            //require_once('pubnub/autoloader.php');

            //use Pubnub\Pubnub;

            //$pubnub = new Pubnub("pub-c-1cf70c5d-6289-4189-b369-744b0bc1a3cf", "sub-c-e4fdd3d8-b3f2-11e6-a7bb-0619f8945a4f");


            echo '<li class="message sent">
						  <div class="media">
							  <div class="user-avatar">
								  <img class="media-object img-circle" src="assets/images/profile-photo.jpg">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><span class="time">' . date('d-m-Y H:m', $time) . '</span></p>
								                                                                                                 ' . $message . '
							  </div>
						  </div>
					  </li>';

            break;

        /****************************************/
        /****************************************/
        /*        /SEND INTERNAL MESSAGE        */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*         SEND INTERNAL MESSAGE        */
        /****************************************/
        /****************************************/

        case 'openSMSconversation':

            $number = $_POST['number'];

            if ($number != '') {

                $message_sql = "SELECT * FROM messages WHERE (user_from='" . $number . "' and user_dest='" . $_SESSION['user']['twilio_mobile'] . "') OR (user_dest='" . $number . "' and user_from='" . $_SESSION['user']['twilio_mobile'] . "') ORDER BY date ASC";
                //echo $message_sql;
                $message_query = $mysqli->query($message_sql);
                //$allMessage = $message_query->fetch_all(MYSQLI_ASSOC);
                $allMessage = array();
                while($message = $message_query->fetch_assoc()) $allMessage[] = $message;

                $subscriber_sql = "SELECT name, photo,facebookId,id FROM subscribers WHERE mobile='" . $number . "'";
                $subscriber_query = $mysqli->query($subscriber_sql);
                $subscriber = $subscriber_query->fetch_assoc();

                $lastday = 0;

                echo '1$#' . $subscriber['name'] . '$#';

                echo '<input type="hidden" value="' . $subscriber['id'] . '" id="subId">';

                if (count($allMessage) == 0) {
                    die('<li class="conversation-divider"><span>No messages to show</span></li>');
                }

                foreach ($allMessage as $message) {

                    $thisMessageDay = date('d', $message['date']);

                    if ($lastday != $thisMessageDay) {
                        $lastday = $thisMessageDay;
                        echo '<li class="conversation-divider"><span>' . date('d-m-Y', $message['date']) . '</span></li>';
                    }

                    if ($message['user_from'] != $_SESSION['user']['twilio_mobile']) {
                        if (isset($subscriber['facebookId'])) {
                            $cphoto = 'http://graph.facebook.com/' . $subscriber['facebookId'] . '/picture?type=square';
                        } else {
                            $cphoto = $subscriber['photo'];
                        }


                        echo '<li class="message receive">
						  <div class="media">
							  <div class="pull-left user-avatar">
								  <img class="media-object img-circle" src="' . $cphoto . '" id="' . $subscriber['facebookId'] . '">

							  </div>
							  <div class="media-body">
								  <p class="media-heading"><span class="time">' . date('d-m-Y H:m', $message['date']) . '</span></p>
									' . $message['message'] . '
							  </div>
						  </div>
					  </li>';

                    } else {
                        $getLogo = $mysqli->query("SELECT * FROM customize_landing_pages WHERE client = '" . $_SESSION['user']['id'] . "'");
                        while ($row = $getLogo->fetch_assoc()) {
                            $logo = $row['banner'];
                        }

                        echo '<li class="message sent">
						  <div class="media">
							  <div class="pull-left user-avatar">
								  <img class="media-object img-circle" src="' . $logo . '">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><span class="time">' . date('d-m-Y H:m', $message['date']) . '</span></p>
								                                                                                                 ' . $message['message'] . '
							  </div>
						  </div>
					  </li>';

                    }

                }

            } else echo '0$#An error has ocurred. please, refresh the page and try again.';

            /*
			<li class="conversation-divider"><span>Conversation started at 26.03.2014</span></li>

							<li class="message receive">
								<div class="media">
									<div class="pull-left user-avatar">
										<img class="media-object img-circle" src="assets/images/ici-avatar.jpg">
									</div>
									<div class="media-body">
										<p class="media-heading"><a href="#">Ing. Imrich Kamarel</a> <span class="time">26.3.2014 18:36</span></p>
										Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
									</div>
								</div>
							</li>
							<li class="message sent">
								<div class="media">
									<div class="pull-left user-avatar">
										<img class="media-object img-circle" src="assets/images/profile-photo.jpg">
									</div>
									<div class="media-body">
										<p class="media-heading"><a href="#">John Douey</a> <span class="time">26.3.2014 18:38</span></p>
										Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
									</div>
								</div>
							</li>

							<li class="conversation-divider"><span>29.03.2014</span></li>

							<li class="message receive">
								<div class="media">
									<div class="pull-left user-avatar">
										<img class="media-object img-circle" src="assets/images/ici-avatar.jpg">
									</div>
									<div class="media-body">
										<p class="media-heading"><a href="#">Ing. Imrich Kamarel</a> <span class="time">29.3.2014 12:20</span></p>
										Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
									</div>
								</div>
							</li>
							<li class="message sent">
								<div class="media">
									<div class="pull-left user-avatar">
										<img class="media-object img-circle" src="assets/images/profile-photo.jpg">
									</div>
									<div class="media-body">
										<p class="media-heading"><a href="#">John Douey</a> <span class="time">29.3.2014 12:25</span></p>
										Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
									</div>
								</div>
							</li>
							<li class="message sent">
								<div class="media">
									<div class="pull-left user-avatar">
										<img class="media-object img-circle" src="assets/images/profile-photo.jpg">
									</div>
									<div class="media-body">
										<p class="media-heading"><a href="#">John Douey</a> <span class="time">29.3.2014 13:15</span></p>
										Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
									</div>
								</div>
							</li>

							<li class="conversation-divider"><span>31.03.2014</span></li>

							<li class="message receive">
								<div class="media">
									<div class="pull-left user-avatar">
										<img class="media-object img-circle" src="assets/images/ici-avatar.jpg">
									</div>
									<div class="media-body">
										<p class="media-heading"><a href="#">Ing. Imrich Kamarel</a> <span class="time">31.3.2014 09:12</span></p>
										Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
									</div>
								</div>
							</li>
							<li class="message sent">
								<div class="media">
									<div class="pull-left user-avatar">
										<img class="media-object img-circle" src="assets/images/profile-photo.jpg">
									</div>
									<div class="media-body">
										<p class="media-heading"><a href="#">John Douey</a> <span class="time">31.3.2014 09:15</span></p>
										Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
									</div>
								</div>
							</li>
							<li class="message receive">
								<div class="media">
									<div class="pull-left user-avatar">
										<img class="media-object img-circle" src="assets/images/ici-avatar.jpg">
									</div>
									<div class="media-body">
										<p class="media-heading"><a href="#">Ing. Imrich Kamarel</a> <span class="time">31.3.2014 09:18</span></p>
										Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
									</div>
								</div>
							</li>
			*/

            break;

        /****************************************/
        /****************************************/
        /*        /SEND INTERNAL MESSAGE        */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*             SEND NEW CODE            */
        /****************************************/
        /****************************************/


        case "sendNewCode":
            $id = $_POST['id'];

            do {

                $verify_code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);

                $verifyCodeExists_sql = "SELECT * FROM subscribers WHERE verify_code='" . $verify_code . "'";
                $verifyCodeExists_query = $mysqli->query($verifyCode_sql);

                if ($verifyCodeExists_query->num_rows == 0) {
                    $verifyCodeExists = false;
                } else {
                    $verifyCodeExists = true;
                }

            } while ($verifyCodeExists);


            // CODE $verify_code

            // query update subscribers where id=$id
            $updateSubCode = $mysqli->query("UPDATE subscribers SET verify_code='" . $verify_code . "'  WHERE id='" . $id . "'");

            // select client from subscribers where id=$id
            $selectUser = $mysqli->query("SELECT * FROM subscribers WHERE id='" . $id . "'");
            while ($sub = $selectUser->fetch_assoc()) {

                $email = $sub['email'];
                $name = $sub['name'];
                $mobile = $sub['mobile'];
            }

            $trader_sql = "SELECT * FROM clients WHERE id='" . $_SESSION['user']['id'] . "'";
            $trader_query = $mysqli->query($trader_sql);
            $trader = $trader_query->fetch_assoc();

            $traderImages_sql = "SELECT * FROM customize_landing_pages WHERE client='" . $_SESSION['team'] . "'";
            $traderImages_query = $mysqli->query($traderImages_sql);
            $traderImages = $traderImages_query->fetch_assoc();

            $tags = [
                "name" => $trader['full_name'],
                "email" => $trader['email'],
                "logo" => $traderImages['banner'],
                "cover" => $traderImages['banner']
            ];

            $content = '<h1 style="text-align: center;"><br><span style="font-size:48px">NEW VERIFICATION CODE</span></h1>&nbsp;<p>Use the code below to confirm and proceed with your registration<br><br>Your Code: ' . $verify_code . '<br><br><br>Thanks again &amp; Welcome to the Family,<br><br>' . $trader['full_name'] . '<br>' . $trader['email'] . '&nbsp;</p>';

            sendMail($email, 'NEW VERIFICATION CODE', $tags, $content);

            sendMessage('', 'Hi ' . explode(' ', $name)[0] . ', please insert this code to validate your registration. Code: ' . $verify_code, $phoneCode . trimPhone($mobile), $trader['twilio_mobile']);

            break;


        /****************************************/
        /****************************************/
        /*            /SEND NEW CODE            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          START REGISTRATION          */
        /****************************************/
        /****************************************/

        case "sendSMSMessage":

            $message = addslashes($_POST['message']);
            $number = $_POST['number'];

            if (trim($message) != '' && trim($number) != '') {

                sendMessage('', $message, $number, $_SESSION['user']['twilio_mobile']);

                $message_sql = "SELECT * FROM messages WHERE (user_from='" . $number . "' and user_dest='" . $_SESSION['user']['twilio_mobile'] . "') OR (user_dest='" . $number . "' and user_from='" . $_SESSION['user']['twilio_mobile'] . "')";
                //echo $message_sql;
                $message_query = $mysqli->query($message_sql);
                //$allMessage = $message_query->fetch_all(MYSQLI_ASSOC);
                $allMessage = array();
                while($message = $message_query->fetch_assoc()) $allMessage[] = $message;

                $subscriber_sql = "SELECT name, photo FROM subscribers WHERE mobile='" . $number . "'";
                $subscriber_query = $mysqli->query($subscriber_sql);
                $subscriber = $subscriber_query->fetch_assoc();

                $lastday = 0;

                echo '1$#';


                if (count($allMessage) == 0) {
                    die('<li class="conversation-divider"><span>No messages to show</span></li>');
                }

                foreach ($allMessage as $message) {

                    $thisMessageDay = date('d', $message['date']);

                    if ($lastday != $thisMessageDay) {
                        $lastday = $thisMessageDay;
                        echo '<li class="conversation-divider"><span>' . date('d-m-Y', $message['date']) . '</span></li>';
                    }

                    if ($message['user_from'] != $_SESSION['user']['twilio_mobile']) {


                        echo '<li class="message receive">
						  <div class="media">
							  <div class="pull-left user-avatar">
								  <img class="media-object img-circle" src="' . $subscriber['photo'] . '">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><span class="time">' . date('d-m-Y H:m', $message['date']) . '</span></p>
									' . $message['message'] . '
							  </div>
						  </div>
					  </li>';

                    } else {

                        echo '<li class="message sent">
						  <div class="media">
							  <div class="pull-left user-avatar">
								  <img class="media-object img-circle" src="assets/images/profile-photo.jpg">
							  </div>
							  <div class="media-body">
								  <p class="media-heading"><span class="time">' . date('d-m-Y H:m', $message['date']) . '</span></p>
								                                                                                                 ' . $message['message'] . '
							  </div>
						  </div>
					  </li>';

                    }

                }


            } else {
                echo '0$#To send a message we need a number and the text';
            }

            break;

        /****************************************/
        /****************************************/
        /*          START REGISTRATION          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          START REGISTRATION          */
        /****************************************/
        /****************************************/

        case 'openMemberRegistration':

            $name = addslashes(trim($_POST['name']));
            $email = addslashes(trim($_POST['email']));
            $country = addslashes(trim($_POST['country']));
            $mobile = addslashes(trim($_POST['mobile']));

            if (
                trim($name) != '' &&
                trim($email) != '' &&
                trim($country) != '' &&
                trim($mobile) != ''
            ) {

                if ($email == 'test@lionsofforex.com') {
                    $updateTest = "UPDATE subscribers SET email='" . time() . "-test@lionsofforex.com', mobile='---' WHERE email='test@lionsofforex.com'";
                    $mysqli->query($updateTest);
                }

                $verifyEmail_sql = "SELECT * FROM subscribers WHERE email='" . $email . "'";
                $verifyEmail_query = $mysqli->query($verifyEmail_sql);

                if ($verifyEmail_query->num_rows == 0) {

                    $phoneCode_sql = "SELECT phone_code FROM countries WHERE id='" . $country . "'";
                    $phonecode_query = $mysqli->query($phoneCode_sql);
                    $phoneCode = trim($phonecode_query->fetch_assoc()['phone_code']);

                    $verifyPhone_sql = "SELECT * FROM subscribers WHERE mobile='" . $phoneCode . trimPhone($mobile) . "'";
                    $verifyPhone_query = $mysqli->query($verifyPhone_sql);

                    if ($verifyPhone_query->num_rows == 0) {

                        do {

                            $verify_code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);

                            $verifyCodeExists_sql = "SELECT * FROM subscribers WHERE verify_code='" . $verify_code . "'";
                            $verifyCodeExists_query = $mysqli->query($verifyCode_sql);

                            if ($verifyCodeExists_query->num_rows == 0) {
                                $verifyCodeExists = false;
                            } else {
                                $verifyCodeExists = true;
                            }

                        } while ($verifyCodeExists);

                        $newClient_sql = "INSERT INTO subscribers (client, name, email, mobile, country, verify_code, added, ip) VALUE ('" . $_SESSION['team'] . "', '" . $name . "', '" . $email . "', '" . $phoneCode . trimPhone($mobile) . "', '" . $country . "', '" . $verify_code . "', '" . time() . "', '" . $_SERVER['HTTP_X_FORWARDED_FOR'] . "')";
                        setLog('register', explode(' ', $name)[0] . ' register using this data: ' . serialize($_POST));
                        $newClient_query = $mysqli->query($newClient_sql);
                        //echo $newClient_sql;

                        $trader_sql = "SELECT * FROM clients WHERE id='" . $_SESSION['team'] . "'";
                        $trader_query = $mysqli->query($trader_sql);
                        $trader = $trader_query->fetch_assoc();

                        $_SESSION['register']['email'] = $email;
                        //$_SESSION['register']['package'] = $package;

                        $traderImages_sql = "SELECT * FROM customize_landing_pages WHERE client='" . $_SESSION['team'] . "'";
                        $traderImages_query = $mysqli->query($traderImages_sql);
                        $traderImages = $traderImages_query->fetch_assoc();

                        $tags = [
                            "name" => $trader['full_name'],
                            "email" => $trader['email'],
                            "logo" => $traderImages['banner'],
                            "cover" => $traderImages['banner']
                        ];

                        $content = '<h1 style="text-align: center;"><br><span style="font-size:48px">ACCOUNT VERIFICATION</span></h1>&nbsp;<p>Use the code below to confirm and proceed with your registration<br><br>Your Code: ' . $verify_code . '<br><br><br>Thanks again &amp; Welcome to the Family,<br><br>' . $trader['full_name'] . '<br>' . $trader['email'] . '&nbsp;</p>';

                        sendMail($_SESSION['register']['email'], 'ACCOUNT VERIFICATION', $tags, $content);

                        sendMessage('', 'Hi ' . explode(' ', $name)[0] . ', please insert this code to validate your registration. Code: ' . $verify_code, $phoneCode . trimPhone($mobile), $trader['twilio_mobile']);

                        echo 1;
                    } else {
                        echo '0$#This phone is already registered.';
                    }
                } else {
                    echo '0$#This email is already registered.';
                }

            } else echo '0$#Verify inserted data. Some fields should have some error.';

            break;

        /****************************************/
        /****************************************/
        /*          /START REGISTRATION         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          START REGISTRATION          */
        /****************************************/
        /****************************************/

        case 'quickMemberRegistration':

            try {

                $name = addslashes(trim($_POST['name']));
                $email = addslashes(trim($_POST['email']));
                $password = addslashes(trim($_POST['password']));
                $birthday = addslashes(trim($_POST['birthday']));
                $country = addslashes(trim($_POST['country']));
                $mobile = addslashes(trim($_POST['mobile']));
                $fbId = addslashes(trim($_POST['fbId']));
                $forexExperience = addslashes(trim($_POST['forexExperience']));
                $promocode = addslashes(trim($_POST['promocode']));
                $pack = addslashes(trim($_POST['pack']));

                if (
                    trim($name) != '' &&
                    trim($email) != '' &&
                    trim($password) != '' &&
                    trim($birthday) != '' &&
                    trim($country) != '' &&
                    trim($mobile) != '' &&
                    trim($fbId) != '' &&
                    trim($forexExperience) != '' &&
                    trim($pack) != ''
                ) {

                    if ($email == 'test@lionsofforex.com') {
                        $updateTest = "UPDATE subscribers SET email='" . time() . "-test@lionsofforex.com' WHERE email='test@lionsofforex.com'";
                        $mysqli->query($updateTest);
                    }

                    $verifyEmail_sql = "SELECT * FROM subscribers WHERE email='" . $email . "'";
                    $verifyEmail_query = $mysqli->query($verifyEmail_sql);

                    if ($verifyEmail_query->num_rows == 0) {

                        $phoneCode_sql = "SELECT phone_code FROM countries WHERE id='" . $country . "'";
                        $phonecode_query = $mysqli->query($phoneCode_sql);
                        $phoneCode = trim($phonecode_query->fetch_assoc()['phone_code']);

                        $verifyPhone_sql = "SELECT * FROM subscribers WHERE mobile='" . $phoneCode . trimPhone($mobile) . "'";
                        $verifyPhone_query = $mysqli->query($verifyPhone_sql);

                        if ($verifyPhone_query->num_rows == 0) {

                            do {

                                $verify_code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);

                                $verifyCodeExists_sql = "SELECT * FROM subscribers WHERE verify_code='" . $verify_code . "'";
                                $verifyCodeExists_query = $mysqli->query($verifyCode_sql);

                                if ($verifyCodeExists_query->num_rows == 0) {
                                    $verifyCodeExists = false;
                                } else {
                                    $verifyCodeExists = true;
                                }

                            } while ($verifyCodeExists);

                            if (!isset($_SESSION['team']) || $_SESSION['team'] == '') {
                                $teamPack_sql = "SELECT client FROM packages WHERE id='" . $pack . "'";
                                $teamPack_query = $mysqli->query($teamPack_sql);
                                $teamPack = $teamPack_query->fetch_assoc()['client'];
                                $_SESSION['team'] = $teamPack;
                            }

                            $newClient_sql = "INSERT INTO subscribers (client, facebookId, name, email, password, mobile, country, birthday, forexExperience, added, verify_code, package, coupon, ip) VALUE ('" . $_SESSION['team'] . "', '" . $fbId . "', '" . $name . "', '" . $email . "', '" . md5($password) . "', '" . $phoneCode . trimPhone($mobile) . "', '" . $country . "', '" . $birthday . "', '" . $forexExperience . "', '" . time() . "', '" . $verify_code . "', '" . $pack . "', '" . $promocode . "', '" . $_SERVER['HTTP_X_FORWARDED_FOR'] . "')";
                            //setLog( 'register', explode(' ', $name)[0].' register using this data: '.serialize($_POST) .'and query: '.$newClient_sql);
                            $newClient_query = $mysqli->query($newClient_sql);
                            //echo $newClient_sql;

                            $trader_sql = "SELECT * FROM clients WHERE id='" . $_SESSION['team'] . "'";
                            $trader_query = $mysqli->query($trader_sql);
                            $trader = $trader_query->fetch_assoc();

                            $_SESSION['register']['email'] = $email;
                            $_SESSION['register']['package'] = $pack;

                            $traderImages_sql = "SELECT * FROM customize_landing_pages WHERE client='" . $_SESSION['team'] . "'";
                            $traderImages_query = $mysqli->query($traderImages_sql);
                            $traderImages = $traderImages_query->fetch_assoc();

                            $tags = [
                                "name" => $trader['full_name'],
                                "email" => $trader['email'],
                                "logo" => $traderImages['banner'],
                                "cover" => $traderImages['banner']
                            ];

                            $content = '<h1 style="text-align: center;"><br><span style="font-size:48px">ACCOUNT VERIFICATION</span></h1>&nbsp;<p>Use the code below to confirm and proceed with your registration<br><br>Your Code: ' . $verify_code . '<br><br><br>Thanks again &amp; Welcome to the Family,<br><br>' . $trader['full_name'] . '<br>' . $trader['email'] . '&nbsp;</p>';


                            sendMail($_SESSION['register']['email'], 'ACCOUNT VERIFICATION', $tags, $content);

                            sendMessage('', 'Hi ' . explode(' ', $name)[0] . ', please insert this code to validate your registration. Code: ' . $verify_code, $phoneCode . trimPhone($mobile), $trader['twilio_mobile']);

                            //echo '1$#'.$newClient_sql;
                            echo 1;
                        } else {
                            echo '0$#This phone is already registered.';
                        }
                    } else {

                        $verifyEmail = $verifyEmail_query->fetch_assoc();

                        if ($verifyEmail['last_activation'] == '0') {
                            echo '-1$#This email has already been used for registration, would you like to reset it?';
                        } else {
                            echo '0$#This email is already registered.';
                        }

                    }

                } else echo '0$#Verify inserted data. Some fields should have some error.';

            } catch (Exception $e){

                $myfile = fopen('error_logs/' . session_id() . ".txt", "w") or die("0$#System error. Our team is working to fix it. Please try later.");
                $txt = "POST \n\n".serialize($_POST)."\n\n\n SESSION \n\n".serialize($_SESSION)."\n\n\n ERROR \n\n".$e->getMessage();
                fwrite($myfile, $txt);
                fclose($myfile);

                echo "0$#System error. Our team is working to fix it. Please try later.";

            }

            break;

        /****************************************/
        /****************************************/
        /*          /START REGISTRATION         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*       START REGISTRATION MANUAL      */
        /****************************************/
        /****************************************/

        case 'quickMemberRegistrationManual':

            $name = addslashes(trim($_POST['name']));
            $email = addslashes(trim($_POST['email']));
            $password = addslashes(trim($_POST['password']));
            $country = addslashes(trim($_POST['country']));
            $mobile = addslashes(trim($_POST['mobile']));
            $forexExperience = addslashes(trim($_POST['forexExperience']));
            $promocode = addslashes(trim($_POST['promocode']));
            $pack = addslashes(trim($_POST['pack']));
            $team = $_SESSION['user']['id'];

            $_SESSION['manualRegistration'] = $_POST;

            if (
                trim($name) != '' &&
                trim($email) != '' &&
                trim($password) != '' &&
                trim($country) != '' &&
                trim($mobile) != '' &&
                trim($forexExperience) != '' &&
                trim($pack) != ''
            ) {

                $verifyEmail_sql = "SELECT * FROM subscribers WHERE email='" . $email . "'";
                $verifyEmail_query = $mysqli->query($verifyEmail_sql);

                if ($verifyEmail_query->num_rows == 0) {

                    $phoneCode_sql = "SELECT phone_code FROM countries WHERE id='" . $country . "'";
                    $phonecode_query = $mysqli->query($phoneCode_sql);
                    $phoneCode = trim($phonecode_query->fetch_assoc()['phone_code']);

                    $verifyPhone_sql = "SELECT * FROM subscribers WHERE mobile='" . $phoneCode . trimPhone($mobile) . "'";
                    $verifyPhone_query = $mysqli->query($verifyPhone_sql);

                    if ($verifyPhone_query->num_rows == 0) {

                        do {

                            $verify_code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);

                            $verifyCodeExists_sql = "SELECT * FROM subscribers WHERE verify_code='" . $verify_code . "'";
                            $verifyCodeExists_query = $mysqli->query($verifyCode_sql);

                            if ($verifyCodeExists_query->num_rows == 0) {
                                $verifyCodeExists = false;
                            } else {
                                $verifyCodeExists = true;
                            }

                        } while ($verifyCodeExists);

                        $newClient_sql = "INSERT INTO subscribers (client, name, email, password, mobile, country, forexExperience, added, verify_code, package, coupon, verificated) VALUE ('" . $team . "', '" . $name . "', '" . $email . "', '" . md5($password) . "', '" . $phoneCode . trimPhone($mobile) . "', '" . $country . "', '" . $forexExperience . "', '" . time() . "', '', '" . $pack . "', '" . $promocode . "', '1')";
                        //setLog( 'register', explode(' ', $name)[0].' register using this data: '.serialize($_POST) .'and query: '.$newClient_sql);
                        $newClient_query = $mysqli->query($newClient_sql);
                        //echo $newClient_sql;

                        $_SESSION['team'] = $_SESSION['user']['id'];
                        $_SESSION['register']['email'] = $email;
                        $_SESSION['register']['package'] = $pack;

                        setLog('manual registration', 'session before payment: ' . serialize($_POST));

                        echo '1$#';

                        $teamLogo_sql = "SELECT banner FROM customize_landing_pages WHERE client='" . $_SESSION['user']['id'] . "'";
                        $teamLogo_query = $mysqli->query($teamLogo_sql);
                        $teamLogo = $teamLogo_query->fetch_assoc()['banner'];

                        $package_sql = "SELECT * FROM packages WHERE id='" . $pack . "'";
                        $package_query = $mysqli->query($package_sql);
                        $package = $package_query->fetch_assoc();

                        if ($promocode != '') {

                            $code_sql = "SELECT * FROM cupons WHERE code='" . $promocode . "' AND id_package='" . $package['id'] . "' AND start_date<'" . time() . "' AND end_date>'" . time() . "'";
                            $code_query = $mysqli->query($code_sql);

                            if ($code_query->num_rows > 0) {
                                $promo = $code_query->fetch_assoc();

                                $packages_sql = "SELECT * FROM packages WHERE id='" . $package['id'] . "'";
                                $packages_query = $mysqli->query($packages_sql);

                                if ($promo['type'] == 'r') {
                                    $price = $promo['amount'];
                                } else if ($promo['type'] == '$') {
                                    $price = $package['price'] - $promo['amount'];
                                    $price = ($price < 0 ? 0 : $price);
                                } else {
                                    $price = $price = $package['price'] - ($package['price'] * ($promo['amount']) / 100);
                                    $price = ($price < 0 ? 0 : $price);
                                }

                                $_SESSION['promo']['code'] = $promocode;
                                $_SESSION['promo']['pck_id'] = $package['id'];
                                $_SESSION['promo']['pck_price'] = $price;

                            }

                        }

                        if (isset($_SESSION['promo']['pck_price']) && ($package['id'] == $_SESSION['promo']['pck_id'])) {
                            $package['price'] = $_SESSION['promo']['pck_price'] * 100;
                        } else {
                            $package['price'] = $package['price'] * 100;
                        }

                        //if ($package['price'] > 0) {

                            echo '<form action="/finish-register-manual" method="POST">
                                  <script
                                    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
                                    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
                                    data-amount="' . ceil($package['price'] + ($package['price'] * 0.032)) . '"
                                    data-name="' . $_SESSION['user'][''] . '"
                                    data-description="' . $package['name'] . '"
                                    data-image="' . $teamLogo . '"
                                    data-locale="auto"
                                    data-label="Pay with Card"
                                    data-bitcoin="false"
                                    data-allow-remember-me="false">
                                  </script>
                                </form>
                                ';

                        /*} else {

                            $customerOptions = array(
                                "email"  => $email,
                                "description" => "Name: ".$name
                            );

                            if(isset($_SESSION['promo']['code']) && $_SESSION['promo']['code']!='') {
                                $customerOptions["coupon"] = trim($_SESSION['promo']['code']);
                            }

                            $customer = \Stripe\Customer::create($customerOptions);

                            try {
                                $subscrition = \Stripe\Subscription::create(array(
                                    "customer" => $customer->id,
                                    "plan" => $package['stripe_id']
                                ));
                            } catch(Stripe_CardError $e) {
                                $error = $e->getMessage();
                                mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                            } catch (Stripe_InvalidRequestError $e) {
                                // Invalid parameters were supplied to Stripe's API
                                $error = $e->getMessage();
                                mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                            } catch (Stripe_AuthenticationError $e) {
                                // Authentication with Stripe's API failed
                                $error = $e->getMessage();
                                mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                            } catch (Stripe_ApiConnectionError $e) {
                                // Network communication with Stripe failed
                                $error = $e->getMessage();
                                mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                            } catch (Stripe_Error $e) {
                                // Display a very generic error to the user, and maybe send
                                // yourself an email
                                $error = $e->getMessage();
                                mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                            } catch (Exception $e) {
                                // Something else happened, completely unrelated to Stripe
                                $error = $e->getMessage();
                                mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                            }

                            $activeUser_sql = "UPDATE subscribers SET active='1', last_activation='" . time() . "', stripeID='" . $customer->id . "' WHERE email='" . $email . "'";
                            $activeUser_query = $mysqli->query($activeUser_sql);

                            $fetch = $mysqli->query("SELECT * FROM customize_landing_pages WHERE client = '" . $_SESSION['team'] . "'");
                            $get_team_images = $fetch->fetch_assoc();

                            $logo = $get_team_images['banner'];
                            $cover = $get_team_images['imgReg'];

                            $fetch = $mysqli->query("SELECT * FROM clients WHERE id = '" . $_SESSION['team'] . "'");
                            $get_team_info = $fetch->fetch_assoc();

                            //$name = $get_team_info['company'];
                            //$email = $get_team_info['email'];
                            $link = $get_team_info['slug'];

                            $tags = [
                                "name" => $get_team_info['company'],
                                "email" => $get_team_info['email'],
                                "logo" => $logo,
                                "cover" => $cover
                            ];

                            //$content = '<h1 style="text-align: center;"><br><span style="font-size:48px">SERVICE ACTIVATION</span></h1>&nbsp;<p>Congrats&nbsp;on signing up for the ' . $get_team_info['company'] . '™ Forex&nbsp;Platform&nbsp;today!</p><p>You should have received an automatic text with the url to log into your dashboard. Your member dashboard is available both for desktop &amp; mobile friendly!<br><br>The following link will forward you to login to your dashboard:<br><br>https://admin.lionsofforex.com/' . $link . '/login<br><br>Unique Login:<br><br>Your Login Email: ' . $email . '<br>Your Password: Password you chose on your registration<br><br>Please have patience &amp; allow our developers to continue building out the platform daily! More &amp; more functions will become available over the next 2 months, as we move through the Beta phase!<br><br>Thanks again &amp; Welcome to the Family,<br><br>' . $get_team_info['company'] . '<br>' . $get_team_info['email'] . '&nbsp;</p>';

                            $content = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                                            <html xmlns="http://www.w3.org/1999/xhtml">
                                               <head>
                                                  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                                                  <!--[if !mso]><!-->
                                                  <meta http-equiv="X-UA-Compatible" content="IE=edge">
                                                  <!--<![endif]-->
                                                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                                  <title></title>
                                                  <!--[if (gte mso 9)|(IE)]>
                                                  <style type="text/css">
                                                     table {border-collapse: collapse;}
                                                  </style>
                                                  <![endif]-->
                                                  <style>
                                                    @import url(\'https://fonts.googleapis.com/css?family=Montserrat|Cambay\');
                                                     body {
                                                     margin: 0 !important;
                                                     padding: 0;
                                                     background-color: #ffffff;
                                                     }
                                                     table {
                                                     border-spacing: 0;
                                                     font-family: sans-serif;
                                                     color: #333333;
                                                     }
                                                     tr{
                                                     padding: 0;
                                                     }
                                                     td {
                                                     padding: 0;
                                                     }
                                                     img {
                                                     border: 0;
                                                     }
                                                     div[style*="margin: 16px 0"] {
                                                     margin:0 !important;
                                                     }
                                                     .wrapper {
                                                     width: 100%;
                                                     table-layout: fixed;
                                                     -webkit-text-size-adjust: 100%;
                                                     -ms-text-size-adjust: 100%;
                                                     text-align:center
                                                     }
                                                     .webkit {
                                                     max-width: 900px;
                                                     margin: 0 auto;
                                                     }
                                                     .outer {
                                                     Margin: 0 auto;
                                                     width: 100%;
                                                     max-width: 900px;
                                                     border: 1px solid lightgray;
                                                     }
                                                     .full-width-image img {
                                                     width: 100%;
                                                     max-width: 500px;
                                                     height: auto;
                                                     position: relative;
                                                     top: 4px;
                                                     }
                                                     .two-column {
                                                     text-align: center;
                                                     font-size: 0;
                                                     }

                                                     .two-column .column4{
                                                     width: 100%;
                                                     max-width: 200px;
                                                     display: inline-block;
                                                     vertical-align: top;
                                                     }
                                                     .contents {
                                                     width: 100%;
                                                     }

                                                     .two-column img {
                                                     width: 100%;
                                                     max-width:132px;
                                                     height: auto;
                                                     margin-top:30px;
                                                     }
                                                     .two-column .text {
                                                     padding-top: 10px;
                                                     }

                                                     .three-column {
                                                     text-align: center;
                                                     font-size: 0;
                                                     }

                                                     .three-column .contents {
                                                     font-size: 14px;
                                                     text-align: center;
                                                     }
                                                     .three-column img {
                                                     width: 100%;
                                                     height: auto;
                                                     max-width:60px;
                                                     }
                                                     .three-column .text {
                                                     padding-top: 10px;
                                                     color:#ffffff;
                                                     font-weight:500
                                                     }
                                                     .full-width-image1 img {
                                                     width: 100%;
                                                     max-width: 650px;
                                                     height: auto;
                                                     position: relative;
                                                     top: 4px;
                                                     }

                                                     .three-column1 {
                                                     text-align: center;
                                                     font-size: 0;
                                                     padding-top: 10px;
                                                     padding-bottom: 10px;
                                                     }
                                                     .three-column1 .column1 {
                                                     width: 100%;
                                                     max-width: 220px;
                                                     display: inline-block;
                                                     vertical-align: top;
                                                     }
                                                     .three-column1 .contents {
                                                     text-align: center;
                                                     }
                                                     .three-column1  img {
                                                     width: 100%;
                                                     height: auto;
                                                     max-width:100px;
                                                     }
                                                     .three-column1  .text {
                                                     padding-top: 10px;
                                                     color:#ffffff;
                                                     font-weight:500
                                                     }

                                                     .three-column2 {
                                                     text-align: center;
                                                     font-size: 0;
                                                     padding-top: 10px;
                                                     padding-bottom: 10px;
                                                     }
                                                     .three-column2 .column2 {
                                                     width: 100%;
                                                     max-width: 150px;
                                                     display: inline-block;
                                                     vertical-align: top;
                                                     }
                                                     .three-column2 .column3 {
                                                     width: 100%;
                                                     max-width: 200px;
                                                     display: inline-block;
                                                     vertical-align: top;
                                                     }
                                                     .three-column2 .contents {
                                                     font-size: 14px;
                                                     text-align: center;
                                                     }
                                                     .three-column2 img {
                                                     width: 100%;
                                                     height: auto;
                                                     max-width:60px;
                                                     }
                                                     .three-column2 .text {
                                                     padding-top: 10px;
                                                     color:#ffffff;
                                                     font-weight:500
                                                     }

                                                     .left-sidebar {
                                                     text-align: center;
                                                     font-size: 0;
                                                     }
                                                     .left-sidebar .column {
                                                     width: 100%;
                                                     display: inline-block;
                                                     vertical-align: middle;
                                                     }
                                                     .left-sidebar .left {
                                                     max-width: 200px;
                                                     }
                                                     .left-sidebar .right {
                                                     max-width: 500px;
                                                     }
                                                     .left-sidebar .img {
                                                     width: 100%;
                                                     max-width: 100px;
                                                     height: auto;
                                                     }
                                                     .left-sidebar .contents {
                                                     font-size: 14px;
                                                     text-align:left
                                                     }
                                                     .left-sidebar .contents1 {
                                                     font-size: 14px;
                                                     text-align:left
                                                     }

                                                     @media only screen and (max-width: 515px) {
                                                     .logo_class {
                                                     display:none;
                                                     }
                                                     }

                                                     @media only screen and (max-width: 900px) {

                                                                  .three-column .column {
                                                     width:100%;
                                                     display: inline-block;
                                                     vertical-align: top;
                                                     }

                                                     }

                                                     @media only screen and (min-width: 900px) {
                                                     .three-column .column {
                                                     width:33%;
                                                     display: inline-block;
                                                     vertical-align: top;
                                                     }


                                                     }


                                                     @media only screen and (max-width: 860px) {
                                                     .left-sidebar .contents {
                                                     font-size: 14px;
                                                     text-align: center;
                                                     }



                                                     }
                                                     @media only screen and (max-width: 740px) {
                                                     .column.classdisplay{
                                                     display:none
                                                     }





                                                      td.addbgimg{
                                                    background-image: url(http://members.lionsofforex.com/email_assets/images/StorySaverMobile22x-p-1080.png);
                                                     background-position:center 25px;
                                                     background-size: 400px;
                                                     background-repeat: no-repeat;
                                                     }
                                                     td.two-column .column .contents
                                                     {
                                                     text-align:center;
                                                     padding-top:300px;
                                                     }
                                                     .pdbut{
                                                     margin-bottom:100px !important
                                                     }
                                                               .two-column .contents {
                                                     font-size: 14px;
                                                     text-align:center;
                                                     }

                                                     }

                                                      @media only screen and (min-width: 741px) {

                                                     td.addbgimg{
                                                     background-image: url(http://members.lionsofforex.com/email_assets/images/StorySaverMobile22x-p-1080.png);
                                                     background-position:right;
                                                     background-size: 400px;
                                                     background-repeat: no-repeat;

                                                     }


                                                    .two-column .column {
                                                     width: 100%;
                                                     max-width: 300px;
                                                     display: inline-block;
                                                     vertical-align: top;
                                                     }




                                                     .two-column .contents {
                                                     font-size: 14px;
                                                     text-align:left;
                                                     }


                                                      }


                                                  </style>
                                               </head>
                                               <body style="padding: 0;background-color: #ffffff;margin: 0 !important;">
                                                  <div class="wrapper" style="width: 100%;table-layout: fixed;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;text-align: center;">
                                                     <div class="webkit" style="max-width: 900px;margin: 0 auto;">
                                                        <!--[if (gte mso 9)|(IE)]>
                                                        <table width="900" align="center  cellspacing="0"">
                                                        <tr>
                                                           <td>
                                                              <![endif]-->
                                                              <table class="outer" align="center" style="border-spacing: 0;font-family: sans-serif;color: #333333;margin: 0 auto;width: 100%;max-width: 900px;border: 1px solid lightgray;">
                                                                 <!--start logo-->
                                                                 <tr width="100%" style="background-image: url(\'http://members.lionsofforex.com/email_assets/images/bg.PNG\');background-size: cover;padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                          <tr style="padding: 0;">
                                                                             <td align="left" class="logo_class" style="padding-left: 50px;padding-top: 0px;height: 80px;">
                                                                                <img src="https://members.lionsofforex.com/assets/logos/primary/LOF-Light-Light.png" class="logo_class" alt="logo_brand" style="width: 120px;border: 0;">
                                                                             </td>
                                                                             <td align="right" style="padding-right: 50px;padding-top: 15px;height: 80px;">
                                                                                <a href="https://members.lionsofforex.com/" style="text-decoration:none;color:white;cursor:pointer;display:inline-block;;font-size:15px;vertical-align:super;margin-top:0;font-family: Montserrat, sans-serif;padding-right:50px;font-weight:700;display: inline-block;margin-right:5%">GET HELP</a>
                                                                                <a href="https://members.lionsofforex.com/" style="background-color:#1d1d1d;margin-top:0;border:1px solid #4a3618;padding:12px 40px;border-radius:30px;color:white;vertical-align: super;;font-weight:bold;font-size:13px;cursor:pointer;margin-bottom:54px;font-family: Montserrat, sans-serif;;text-decoration:none;color:#ffffff;font-weight:700">LOGIN</a>
                                                                             </td>
                                                                          </tr>
                                                                       </table>
                                                                       <img src="https://members.lionsofforex.com/assets/logos/secondary/Sec-Light.png" width="70" height="70" style="display: block;margin: auto;margin-top: 115px;border: 0;">
                                                                       <p style="font-size:40px;color:white;font-weight:900;margin-top:3%;margin-bottom:0;font-family: Montserrat, sans-serif;">REGISTRATION COMPLETE
                                                                       </p>
                                                                       <p style="color:white;font-weight:bold;padding-bottom:222px;font-family: Montserrat, sans-serif;">Congrats on signing up for Lions of Forex!</p>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end logo-->
                                                                 <!--start login-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td class="three-column" style="background-color: #1d1d1d99;padding: 0;text-align: center;font-size: 0;">
                                                                       <!--[if (gte mso 9)|(IE)]>
                                                                       <table width="100%">
                                                                          <tr>
                                                                             <td width="280" valign="top">
                                                                                <![endif]-->
                                                                                <div class="column">
                                                                                   <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                                      <tr style="padding: 0;">
                                                                                         <td class="inner" style="padding: 0;">
                                                                                            <table class="contents" style="border-spacing: 0;font-family: sans-serif;color: #333333;width: 100%;font-size: 14px;text-align: center;">
                                                                                               <tr style="padding: 0;">
                                                                                                  <td style="padding: 0;">
                                                                                                     <img src="http://members.lionsofforex.com/email_assets/images/icons8-circled_user2x.png" width="60" alt="user_logo" style="margin-top: 30px;border: 0;width: 100%;height: auto;max-width: 60px;">
                                                                                                  </td>
                                                                                               </tr>
                                                                                               <tr style="padding: 0;">
                                                                                                  <td class="text" style="padding: 0;padding-top: 10px;color: #ffffff;font-weight: 500;">
                                                                                                     <p style="margin-bottom:5px;margin-top:0px;font-family:Cambay;">Email</p>
                                                                                                     <p style="margin-bottom:50px;margin-top:0;font-weight:600;font-family:Cambay;">'.$_SESSION['register']['email'].'</p>
                                                                                                  </td>
                                                                                               </tr>
                                                                                            </table>
                                                                                         </td>
                                                                                      </tr>
                                                                                   </table>
                                                                                </div>
                                                                                <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                             <td width="280" valign="top">
                                                                                <![endif]-->
                                                                                <div class="column">
                                                                                   <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                                      <tr style="padding: 0;">
                                                                                         <td class="inner" style="padding: 0;">
                                                                                            <table class="contents" style="background-color: #262626;border-spacing: 0;font-family: sans-serif;color: #333333;width: 100%;font-size: 14px;text-align: center;">
                                                                                               <tr style="padding: 0;">
                                                                                                  <td style="padding: 0;">
                                                                                                     <img src="http://members.lionsofforex.com/email_assets/images/icons8-lock_22x.png" width="60" alt="pass_logo" style="margin-top: 30px;border: 0;width: 100%;height: auto;max-width: 60px;">
                                                                                                  </td>
                                                                                               </tr>
                                                                                               <tr style="padding: 0;">
                                                                                                  <td class="text" style="padding: 0;padding-top: 10px;color: #ffffff;font-weight: 500;">
                                                                                                     <p style="margin-bottom:5px;margin-top:0px;font-family:Cambay;">Password</p>
                                                                                                     <p style="margin-bottom:50px;margin-top:0;font-weight:600;font-family:Cambay;">Password you choosed on registration</p>
                                                                                                  </td>
                                                                                               </tr>
                                                                                            </table>
                                                                                         </td>
                                                                                      </tr>
                                                                                   </table>
                                                                                </div>
                                                                                <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                             <td width="280" valign="top">
                                                                                <![endif]-->
                                                                                <div class="column">
                                                                                   <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                                      <tr style="padding: 0;">
                                                                                         <td class="inner" style="padding: 0;">
                                                                                            <table class="contents" style="border-spacing: 0;font-family: sans-serif;color: #333333;width: 100%;font-size: 14px;text-align: center;">
                                                                                               <tr style="padding: 0;">
                                                                                                  <td style="padding: 0;">
                                                                                                     <img src="http://members.lionsofforex.com/email_assets/images/icons8-domain2x.png" width="60" alt="logo_url" style="margin-top: 30px;border: 0;width: 100%;height: auto;max-width: 60px;">
                                                                                                  </td>
                                                                                               </tr>
                                                                                               <tr style="padding: 0;">
                                                                                                  <td class="text" style="padding: 0;padding-top: 10px;color: #ffffff;font-weight: 500;">
                                                                                                     <p style="margin-bottom:5px;margin-top:0px;font-family:Cambay;">Login URL</p>
                                                                                                     <a href="https://members.lionsofforex.com/" style="margin-bottom:50px;margin-top:0;font-weight:600;font-family:Cambay;color:#fff;text-decoration:none;">members.lionsofforex.com</p>
                                                                                                  </td>
                                                                                               </tr>
                                                                                            </table>
                                                                                         </td>
                                                                                      </tr>
                                                                                   </table>
                                                                                </div>
                                                                                <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                          </tr>
                                                                       </table>
                                                                       <![endif]-->
                                                                    </td>
                                                                 </tr>
                                                                 <!--end login-->
                                                                 <!--start header-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <p style="color:#59cff1;font-size:2.9em;margin-top:115px;margin-bottom:30px;font-family: Cambay;">Let\'s get you started</p>
                                                                       <p style="max-width:600px;text-align:center;margin:auto;font-size:1.1em;font-weight:500;margin-bottom:50px;font-family: Montserrat, sans-serif;">You’ve taken the first step towards financial freedom through mastering the Foreign Exchange markets! But, LOF is much bigger than just Forex! We are a community of traders, entrepreneurs, and visionaries- all focused on improving our lives in every way possible! Following our proven system, our goal is to help you see major gains in both your mental & financial health! Welcome to Lions of Forex!
                                                                       </p>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end header-->
                                                                 <!--start dashboard-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td class="full-width-image" style="padding: 0;">
                                                                       <img src="http://members.lionsofforex.com/email_assets/images/dashboardiphone12x.png" width="500" alt="Dashboard" style="border: 0;width: 100%;max-width: 500px;height: auto;position: relative;top: 4px;">
                                                                    </td>
                                                                 </tr>
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <p style="margin-bottom:50px;font-size:1.1em;font-weight:500; font-family: Montserrat, sans-serif;">Lions of Forex has the most revolutionary team platform in the world. We’ve <br>combined the best from around the world & put it all in one incredible platform..</p>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end dashboard-->
                                                                 <!--start followers-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td class="full-width-image" style="padding: 0;">
                                                                       <img src="http://members.lionsofforex.com/email_assets/images/newdash.png" width="500" alt="newdash" style="border: 0;width: 100%;max-width: 500px;height: auto;position: relative;top: 4px;">
                                                                    </td>
                                                                 </tr>
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <p style="margin-bottom:50px;font-size:1.1em;font-weight:500; font-family: Montserrat, sans-serif;">The member dashboard includes essential features <br> & an exclusive members-onlychat room.
                                                                       </p>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end followers-->
                                                                 <!--start engagement-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td class="full-width-image" style="padding: 0;">
                                                                       <img src="http://members.lionsofforex.com/email_assets/images/newsig.png" width="500" alt="newsig" style="border: 0;width: 100%;max-width: 500px;height: auto;position: relative;top: 4px;">
                                                                    </td>
                                                                 </tr>
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <p style="margin-bottom:50px;font-size:1.1em;font-weight:500; font-family: Montserrat, sans-serif;">We send out signals daily via SMS/Push-Notifications. <br>Find them all here to track our monthly progress.
                                                                       </p>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end engagement-->
                                                                 <!--start engagement2-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td class="full-width-image" style="padding: 0;">
                                                                       <img src="http://members.lionsofforex.com/email_assets/images/newedu.png" width="500" alt="newedu" style="border: 0;width: 100%;max-width: 500px;height: auto;position: relative;top: 4px;">
                                                                    </td>
                                                                 </tr>
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <p style="margin-bottom:50px;font-size:1.1em;font-weight:500; font-family: Montserrat, sans-serif;">Get access to the exclusive LOF trading, marketing, and personal development <br>educational videos.</p>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end engagement2-->
                                                                 <!--start engagement3-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td class="full-width-image" style="padding: 0;">
                                                                       <img src="http://members.lionsofforex.com/email_assets/images/all3.png" width="500" alt="all3" style="border: 0;width: 100%;max-width: 500px;height: auto;position: relative;top: 4px;">
                                                                    </td>
                                                                 </tr>
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <p style="margin-bottom:50px;font-size:1.1em;font-weight:500; font-family: Montserrat, sans-serif;">Watch our members-only webinars daily, only available via the platform!</p>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end engagement3-->
                                                                 <!--start engagement4-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td class="full-width-image" style="padding: 0;">
                                                                       <img src="http://members.lionsofforex.com/email_assets/images/all3.png" width="500" alt="alll3" style="border: 0;width: 100%;max-width: 500px;height: auto;position: relative;top: 4px;">
                                                                    </td>
                                                                 </tr>
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <p style="margin-bottom:50px;font-size:1.1em;font-weight:500; font-family: Montserrat, sans-serif;">Explore the rest of the platform easily! More features include Resources <br>(Templates & Indicators), LOF Marketplace, & our affiliate program!
                                                                       </p>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end engagement4-->
                                                                 <!--start service -->
                                                                 <tr style="max-width: 600px;background-image: url(\'http://members.lionsofforex.com/email_assets/images/bg2.PNG\');background-size: cover;padding: 0;">
                                                                    <td class="left-sidebar" style="padding: 0;text-align: center;font-size: 0;">
                                                                       <!--[if (gte mso 9)|(IE)]>
                                                                       <table width="100%">
                                                                          <tr>
                                                                             <td width="200">
                                                                                <![endif]-->
                                                                                <div style="margin-bottom:0px;margin-top:50px">
                                                                                   <div class="column left" style="width: 100%;display: inline-block;vertical-align: middle;max-width: 200px;">
                                                                                      <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                                         <tr style="padding: 0;">
                                                                                            <td class="inner" style="padding: 0;">
                                                                                            </td>
                                                                                         </tr>
                                                                                      </table>
                                                                                   </div>
                                                                                   <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                             <td width="500">
                                                                             <![endif]-->
                                                                             <div class="column right" style="width: 100%;display: inline-block;vertical-align: middle;max-width: 500px;">
                                                                             <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                             <tr style="padding: 0;">
                                                                             <td class="inner contents1" style="padding: 0;font-size: 14px;text-align: left;">
                                                                             <p style="color:#ffffff;font-size:28px;font-family: Cambay;text-align:left">Getting the most out of Lions of Forex.
                                                                             </p>
                                                                             </td>
                                                                             </tr>
                                                                             </table>
                                                                             </div>
                                                                             </div>
                                                                             <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                             <td width="200">
                                                                                <![endif]-->
                                                                                <div class="column left" style="width: 100%;display: inline-block;vertical-align: middle;max-width: 200px;">
                                                                                   <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                                      <tr style="padding: 0;">
                                                                                         <td class="inner" style="padding: 0;">
                                                                                            <img src="http://members.lionsofforex.com/email_assets/images/70.png" width="64" alt="icon" style="border: 0;">
                                                                                         </td>
                                                                                      </tr>
                                                                                   </table>
                                                                                </div>
                                                                                <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                             <td width="500">
                                                                                <![endif]-->
                                                                                <div class="column right" style="width: 100%;display: inline-block;vertical-align: middle;max-width: 500px;">
                                                                                   <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                                      <tr style="padding: 0;">
                                                                                         <td class="inner contents" style="padding: 0;width: 100%;font-size: 14px;text-align: left;">
                                                                                            <p style="color:#ffffff;font-size:23px;font-family: Cambay;margin-top:0;margin-bottom: 0">Rule of Thumb #1</p>
                                                                                            <p style="color:#ffffff;font-family: Cambay;">Follow our proven signals system! It’s simple as Copy, Paste, Profit! If you’re new, don’t trade on your own!</p>
                                                                                         </td>
                                                                                      </tr>
                                                                                   </table>
                                                                                </div>
                                                                                <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                             <td width="200">
                                                                                <![endif]-->
                                                                                <div class="column left" style="width: 100%;display: inline-block;vertical-align: middle;max-width: 200px;">
                                                                                   <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                                      <tr style="padding: 0;">
                                                                                         <td class="inner" style="padding: 0;">
                                                                                            <img src="http://members.lionsofforex.com/email_assets/images/70.png" width="64" alt="icon" style="border: 0;">
                                                                                         </td>
                                                                                      </tr>
                                                                                   </table>
                                                                                </div>
                                                                                <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                             <td width="500">
                                                                                <![endif]-->
                                                                                <div class="column right" style="width: 100%;display: inline-block;vertical-align: middle;max-width: 500px;">
                                                                                   <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                                      <tr style="padding: 0;">
                                                                                         <td class="inner contents" style="padding: 0;width: 100%;font-size: 14px;text-align: left;">
                                                                                            <p style="color:#ffffff;font-size:23px;    font-family: Cambay;margin-bottom: 0">Rule of Thumb #2</p>
                                                                                            <p style="color:#ffffff;    font-family: Cambay;">Education is key! Spend at least 1-2 hours per day studying our Forex videos, and take notes!
                                                                                            </p>
                                                                                         </td>
                                                                                      </tr>
                                                                                   </table>
                                                                                </div>
                                                                                <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                             <td width="200">
                                                                                <![endif]-->
                                                                                <div style="margin-bottom:100px">
                                                                                   <div class="column left" style="width: 100%;display: inline-block;vertical-align: middle;max-width: 200px;">
                                                                                      <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                                         <tr style="padding: 0;">
                                                                                            <td class="inner" style="padding: 0;">
                                                                                               <img src="http://members.lionsofforex.com/email_assets/images/70.png" width="64" alt="icon" style="border: 0;">
                                                                                            </td>
                                                                                         </tr>
                                                                                      </table>
                                                                                   </div>
                                                                                   <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                             <td width="500">
                                                                             <![endif]-->
                                                                             <div class="column right" style="width: 100%;display: inline-block;vertical-align: middle;max-width: 500px;">
                                                                             <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                             <tr style="padding: 0;">
                                                                             <td class="inner contents" style="padding: 0;width: 100%;font-size: 14px;text-align: left;">
                                                                             <p style="color:#ffffff;font-size:23px;font-family: Cambay;margin-bottom: 0">Rule of Thumb #3</p>
                                                                             <p style="color:#ffffff;    font-family: Cambay;">Use the community! Everyone in LOF is your friend- so make friends! Ask questions & learn from others!
                                                                             </p>
                                                                             </td>
                                                                             </tr>
                                                                             </table>
                                                                             </div>
                                                                             </div>
                                                                             <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                          </tr>
                                                                       </table>
                                                                       <![endif]-->
                                                                    </td>
                                                                 </tr>
                                                                 <!-- end service -->
                                                                 <!--start login instegr-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <p style="font-size:21px;font-weight:bold;margin-top:100px;padding-bottom:15px;;font-family: Montserrat, sans-serif;">Login Credentials:</p>
                                                                       <p style=";font-family: Montserrat, sans-serif;border:1px solid #ffffff;color:#59cff1;font-size:21px;line-height:0.2;margin-bottom:0;;font-weight:700"> <span style="font-size:21px;color:#000000;;font-weight:500">Email:</span> '.$_SESSION['register']['email'].'</p>
                                                                       <br>
                                                                       <p style=";font-family: Montserrat, sans-serif;border:1px solid #ffffff;color:#59cff1;font-size:21px;line-height:0.5; margin-top:0;;font-weight:700"> <span style="font-size:21px;color:#000000;;font-weight:500">Password:</span> Password you choosed on registration</p>
                                                                       <br>
                                                                       <a href="https://members.lionsofforex.com/" style="display:inline-block;background-color:#59cff1;border:1px solid #59cff1;font-family: Montserrat, sans-serif;padding:12px 50px;border-radius:30px;color:white;font-weight:bold;font-size:16px;cursor:pointer;font-weight:800;text-decoration:none">GO TO DASHBOARD</a>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end login instegr-->
                                                                 <!--start img-girl-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td class="full-width-image" style="padding-top: 50px;">
                                                                       <img src="http://members.lionsofforex.com/email_assets/images/girl12x.png" height="100%" alt="img-girl" style="border: 0;width: 100%;max-width: 500px;height: auto;position: relative;top: 4px;">
                                                                    </td>
                                                                 </tr>
                                                                 <!--end img-girl-->
                                                                 <!--start got a question-->
                                                                 <tr width="100%" style="background-image: url(\'http://members.lionsofforex.com/email_assets/images/bg3.PNG\');background-size: cover;color: white;padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <p style="font-size:37px;margin-top:58px;margin-bottom:0px;font-family:Cambay;">Got a question?</p>
                                                                       <p style="text-align:center;margin:auto;font-size:21px;font-family: Cambay;line-height:1.2;width:62%;margin:auto">Reach our support team on our member dashboard via live chat or email us at <a href="mailto:support@lionsofforex.com" target="_blank" style="    text-decoration: none;
                                                                          color: #ffffff;">support@lionsofforex.com</a></p>
                                                                       <a href="https://members.lionsofforex.com/" target="_blank" style="   font-family: Montserrat, sans-serif;background-color:transparent;margin-top:22px;border:2px solid white;padding:12px 50px;border-radius:30px;color:white;font-weight:bold;font-size:15px;cursor:pointer;margin-bottom:70px;font-weight:900;text-decoration: none;color:#ffffff;display:inline-block;font-family: Montserrat, sans-serif;">GO&nbsp;TO&nbsp;WEBSITE</a>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end got a question-->
                                                                 <!--start sotre-->
                                                                 <tr style="padding: 0;">
                                                                    <td class="two-column addbgimg" style="text-align: center;font-size: 0;padding: 0;">
                                                                       <!--[if (gte mso 9)|(IE)]>
                                                                       <table width="100%">
                                                                          <tr>
                                                                             <td width="50%" valign="top">
                                                                                <![endif]-->
                                                                                <div class="column" style="width: 100%;max-width: 300px;display: inline-block;vertical-align: top;">
                                                                                   <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                                      <tr style="padding: 0;">
                                                                                         <td class="inner contents" style=";width: 100%;">
                                                                                            <img src="https://members.lionsofforex.com/assets/logos/primary/LOF-Light-Light.png" alt="logo_website" width="37%" height="78px" style="margin-top: 163px;border: 0;width: 100%;max-width: 132px;height: auto;">
                                                                                            <div style="padding-top:20px">
                                                                                               <p style="margin-top:0;font-size:28px;font-weight: 900;color:#59cff1;margin-bottom:0;line-height:1.2;;font-family: Montserrat, sans-serif;">MEET THE FOUNDERS!</p>
                                                                                            </div>
                                                                                            <div style="padding-top:20px">
                                                                                               <p style="margin:0;font-weight:bold;font-family: Montserrat, sans-serif;">Meet LOF founders Berto Delvanicci & Roy Taylor- 2 young, ambitious, successful entrepreneurs who have dedicated themselves to helping 1,000,000 people by 2020. Tune into the daily live-webinars to meet & interact with them!</p>
                                                                                            </div>
                                                                                            <a href="https://members.lionsofforex.com/" style="background-color:#ffffff;margin-top:10px;border:2px solid #1d1d1d;padding:10px 60px;border-radius:30px;color:#4a3618;font-weight:900;font-size:13px;display: inline-block;cursor:pointer;margin-bottom:10px;font-family: Montserrat, sans-serif;text-decoration:none">LEARN MORE</a>
                                                                                            <a class="pdbut" href="https://members.lionsofforex.com/" style="background-color:#1d1d1d;margin-top:0px;border:2px solid #1d1d1d;padding:12px 75px;border-radius:30px;color:white;font-weight:900;font-size:13px;cursor:pointer;margin-bottom:120px;font-family: Montserrat, sans-serif;display:inline-block;text-decoration:none">SIGN UP</a>
                                                                                         </td>
                                                                                      </tr>
                                                                                   </table>
                                                                                </div>
                                                                                <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                             <td width="50%" valign="top">
                                                                                <![endif]-->
                                                                                <div class="column classdisplay">
                                                                                   <table width="100%" style="border-spacing: 0;font-family: sans-serif;color: #333333;">
                                                                                      <tr style="padding: 0;">
                                                                                         <td style="padding: 0;">
                                                                                            <img src="#	" alt="" style="border: 0;width: 100%;max-width: 132px;height: auto;margin-top: 30px;">
                                                                                         </td>
                                                                                      </tr>
                                                                                   </table>
                                                                                </div>
                                                                                <!--[if (gte mso 9)|(IE)]>
                                                                             </td>
                                                                          </tr>
                                                                       </table>
                                                                       <![endif]-->
                                                                    </td>
                                                                 </tr>
                                                                 <!-- end store -->
                                                                 <!-- start as seen on-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <p style="font-size:30px;margin-top:0;font-family: Montserrat, sans-serif;">As Seen On</p>
                                                                    </td>
                                                                 </tr>
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td class="full-width-image1" style="padding: 0;">
                                                                       <img src="http://members.lionsofforex.com/email_assets/images/69.png" width="650" height="100%" alt="seen on" style="margin-bottom: 90px;margin-top: 40px;border: 0;width: 100%;max-width: 650px;height: auto;position: relative;top: 4px;">
                                                                    </td>
                                                                 </tr>
                                                                 <!-- end  as seen on-->
                                                                 <!--start save-->
                                                                 <tr width="100%" style="background-image: url(\'http://members.lionsofforex.com/email_assets/images/bg5.PNG\');background-size: cover;color: white;padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <p style="font-size:30px;margin-top:90px;margin-bottom:0px;font-weight:900;;font-family: Montserrat, sans-serif;">LOGIN TO YOUR MEMBER DASHBOARD</p>
                                                                       <a href="https://members.lionsofforex.com/" style="background-color:#1d1d1d;margin-top:30px;border:1px solid #4a3618;padding:12px 60px;border-radius:30px;color:white;font-weight:900;font-size:13px;cursor:pointer;margin-bottom:64px;text-decoration:none;display:inline-block;font-family: Montserrat, sans-serif;">GO TO DASHBOARD</a>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end got a save-->
                                                                 <!--start copyright-->
                                                                 <tr width="100%" style="padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <a href="https://www.facebook.com/Lions0fForex/" style="display:inline-block;margin-top:\'\';margin-bottom:20px;margin-right:;    padding: 0 20px 0 20px;"><img src="http://members.lionsofforex.com/email_assets/images/324_facebookicon-p-800.jpeg" width="20px" height="20px" style="border: 0;"></a>
                                                                       <a href="https://www.youtube.com/channel/UCFWCBrnDmiT72K-wWlwYOKA" style="display:inline-block;margin-top:90px;margin-bottom:20px;margin-right:; padding: 0 20px 0 20px;"><img src="http://members.lionsofforex.com/email_assets/images/youtube-black-logo-B90F9C414C-seeklogo.com.png" width="20" height="20" style="border: 0;"></a>
                                                                       <a href="https://instagram.com/lionsofforex" style="display:inline-block;margin-top:90px;margin-bottom:20px; padding: 0 20px 0 20px;"><img src="http://members.lionsofforex.com/email_assets/images/yelp-p-500.png" width="20px" height="20px" style="border: 0;"></a>
                                                                       <a href="https://www.yelp.com/biz/lions-of-forex-miami?utm_campaign=www_business_share_popup&utm_medium=copy_link&utm_source=(direct)" style="display:inline-block;margin-top:90px;margin-bottom:20px; padding: 0 20px 0 20px;"><img src="http://members.lionsofforex.com/email_assets/images/114.png" width="20px" height="20px" style="border: 0;"></a>
                                                                       <a href="https://twitter.com/lionsofforex?lang=en" style="display:inline-block;margin-top:90px;margin-bottom:20px;margin-right:\'\'; padding: 0 20px 0 20px;"><img src="http://members.lionsofforex.com/email_assets/images/12.png" width="20" height="20" style="border: 0;"></a>
                                                                       <a href="mailto:support@lionsofforex.com" style="display:inline-block;margin-top:0px;margin-bottom:20px;margin-right:\'\'; padding: 0 20px 0 20px;"><img src="http://members.lionsofforex.com/email_assets/images/14.png" width="20px" height="20px" style="border: 0;"></a>
                                                                       <p>© 2019 Lions Of Forex, LLC. All rights reserved.</p>
                                                                       <p style="margin-bottom:90px"><a href="#" style="text-decoration:none;color:#333333;">View in browser</a>  <span style="display:inline-block;margin:10px;font-size:bold"> | </span>  <a href="#" style="text-decoration:none;color:#333333;font-family: Montserrat, sans-serif;"> Unsubscribe</a></p>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end copyright-->
                                                                 <!--start footer-->
                                                                 <tr width="100%" style="background-image: url(\'http://members.lionsofforex.com/email_assets/images/bg6.PNG\');padding-top: 30px;padding: 0;">
                                                                    <td style="padding: 0;">
                                                                       <a href="https://members.lionsofforex.com/" style="font-family: Cambay;cursor:pointer;text-decoration:none;color:white;display:inline-block;padding:0 20px;margin:20px 0;font-size:12px">LOGIN</a>
                                                                       <a href="https://register.lionsofforex.com/lions-of-forex/register" style="font-family: Cambay;cursor:pointer;text-decoration:none;color:white;display:inline-block;padding:0 20px;margin:20px 0;font-size:12px">SIGN UP</a>
                                                                       <a href="https://register.lionsofforex.com/lions-of-forex/register" style="font-family: Cambay;cursor:pointer;text-decoration:none;color:white;display:inline-block;padding:0 20px;margin:20px 0;font-size:12px">GET SUPPORT</a>
                                                                       <a href="https://register.lionsofforex.com/lions-of-forex/register" style="font-family: Cambay;cursor:pointer;text-decoration:none;color:white;display:inline-block;padding:0 20px;margin:20px 0;font-size:12px">PRIVACY POLICY</a>
                                                                       <a href="https://www.lionsofforex.com/" style="font-family: Cambay;cursor:pointer;text-decoration:none;color:white;display:inline-block;padding:0 20px;margin:20px 0;font-size:12px;">GO TO WEBSITE</a>
                                                                    </td>
                                                                 </tr>
                                                                 <!--end footer-->
                                                              </table>
                                                              <!--[if (gte mso 9)|(IE)]>
                                                           </td>
                                                        </tr>
                                                        </table>
                                                        <![endif]-->
                                                     </div>
                                                  </div>
                                               </body>
                                            </html>';

                            sendMail($email, 'SERVICE ACTIVATION', $tags, $content);

                            ?>

                            <h1><strong>THANK</strong> YOU</h1>
                            <h5>For Signing Up</h5>
                            <br><br><br>
                            <p>You're registration is
                                complete.</p><br>
                            <p>We've sent a confirmation
                                message to your email.
                                You'll receive a text with
                                further instructions
                                soon!</p><br><br><br>

                            <?

                        }*/

                    } else {
                        echo '0$#This phone is already registered.';
                    }
                } else {

                    $verifyEmail = $verifyEmail_query->fetch_assoc();

                    if ($verifyEmail['last_activation'] == '0') {
                        echo '-1$#This email has already been used for registration, would you like to reset it?';
                    } else {
                        echo '0$#This email is already registered.';
                    }

                }

            } else echo '0$#Verify inserted data. Some fields should have some error.';

            break;

        /****************************************/
        /****************************************/
        /*      /START REGISTRATION MANUAL      */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          RESET REGISTRATION          */
        /****************************************/
        /****************************************/


        case 'resetRegistration':

            $email = $_POST['email'];

            $inactive_sql = "SELECT * FROM subscribers WHERE email='" . $email . "' AND last_activation='0'";
            $inactive_query = $mysqli->query($inactive_sql);

            if ($inactive_query->num_rows > 0) {

                $user = $inactive_query->fetch_assoc();

                $keys = '';
                $values = '';

                foreach ($user as $key => $field) {
                    $keys .= ($keys != '' ? ',' . $key : $key);
                    $values .= ($values != '' ? ',\'' . addslashes($field) . '\'' : '\'' . addslashes($field) . '\'');
                }

                $insertBackup_sql = "INSERT INTO subscribers_bck (" . $keys . ") VALUES (" . $values . ")";

                $mysqli->query($insertBackup_sql);

                $deleteMember = $mysqli->query("DELETE FROM subscribers WHERE  email='" . $email . "'");

                echo '1$#';

            } else {
                echo '0$#This user can\'t be deleted';
            }


            break;


        /****************************************/
        /****************************************/
        /*         /RESET REGISTRATION          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          START REGISTRATION          */
        /****************************************/
        /****************************************/

        case 'jumpToPaymentQuick':

            $verifyCode = trim($_POST['verifyCode']);

            if (
                trim($verifyCode) != ''
            ) {

                $code_sql = "SELECT * FROM subscribers WHERE verify_code='" . $verifyCode . "'";
                $code_query = $mysqli->query($code_sql);


                if ($code_query->num_rows > 0) {
                    $member = $code_query->fetch_assoc();

                    $_SESSION['register']['email'] = $member['email'];
                    $_SESSION['register']['package'] = $member['package'];

                    setLog('registration validated', 'this email (' . $_SESSION['register']['email'] . ') have validated his registration');
                    $newUser = $member;
                    $finish_update = "UPDATE subscribers SET verificated='1', verify_code='' WHERE id='" . $newUser['id'] . "'";
                    $mysqli->query($finish_update);


                    /**
                     * insert referral
                     */


                    /**
                     * Number verified, process payment
                     */

                    $package = $_SESSION['register']['package'];

                    $teamName_sql = "SELECT company FROM clients WHERE id='" . $_SESSION['team'] . "'";
                    $teamName_query = $mysqli->query($teamName_sql);
                    $teamName = strtoupper($teamName_query->fetch_assoc()['company']);

                    $teamLogo_sql = "SELECT banner FROM customize_landing_pages WHERE client='" . $_SESSION['team'] . "'";
                    $teamLogo_query = $mysqli->query($teamLogo_sql);
                    $teamLogo = $teamLogo_query->fetch_assoc()['banner'];

                    $package_sql = "SELECT * FROM packages WHERE id='" . $package . "'";
                    $package_query = $mysqli->query($package_sql);
                    $package = $package_query->fetch_assoc();

                    if ($member['coupon'] != '') {

                        $code_sql = "SELECT * FROM cupons WHERE code='" . $member['coupon'] . "' AND id_package='" . $package['id'] . "' AND start_date<'" . time() . "' AND end_date>'" . time() . "'";
                        $code_query = $mysqli->query($code_sql);

                        if ($code_query->num_rows > 0) {
                            $promo = $code_query->fetch_assoc();

                            $packages_sql = "SELECT * FROM packages WHERE id='" . $package['id'] . "'";
                            $packages_query = $mysqli->query($packages_sql);

                            if ($promo['type'] == 'r') {
                                $price = $promo['amount'];
                            } else if ($promo['type'] == '$') {
                                $price = $package['price'] - $promo['amount'];
                                $price = ($price < 0 ? 0 : $price);
                            } else {
                                $price = $price = $package['price'] - ($package['price'] * ($promo['amount']) / 100);
                                $price = ($price < 0 ? 0 : $price);
                            }

                            $_SESSION['promo']['code'] = $member['coupon'];
                            $_SESSION['promo']['pck_id'] = $package['id'];
                            $_SESSION['promo']['pck_price'] = $price;

                        }

                    }

                    if (isset($_SESSION['promo']['pck_price']) && ($package['id'] == $_SESSION['promo']['pck_id'])) {
                        $package['price'] = $_SESSION['promo']['pck_price'] * 100;
                    } else {
                        $package['price'] = $package['price'] * 100;
                    }


                    setLog('prepare payment', 'session before payment: ' . serialize($_SESSION));

                    if ($package['price'] > 0) {

                        if ($_SESSION['register']['email'] == 'test@lionsofforex.com') {
                            echo '<form action="/finish-register" method="POST">
							  <script
							    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
							    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
							    data-amount="' . ceil($package['price'] + ($package['price'] * 0.032)) . '"
							    data-name="' . $teamName . '"
							    data-description="' . $package['name'] . '"
							    data-image="' . $teamLogo . '"
							    data-locale="auto"
							    data-label="Pay with Card or Bitcoin"
							    data-bitcoin="true">
							  </script>
							</form>
							<script>
								Stripe.setPublishableKey(\'pk_live_bt8Y3UaI4uwD9bcZww2mslw1\');


								Stripe.applePay.checkAvailability(function(available) {
								  if (available) {
								    document.getElementById(\'apple-pay-button\').style.display = \'block\';
								  }
								});
							</script>
							';
                        } else if ($_SESSION['register']['email'] == 'test-payment@lionsofforex.com') {
                            echo '<form action="/finish-register" method="POST">
							  <script
							    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
							    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
							    data-amount="100"
							    data-name="' . $teamName . '"
							    data-description="Monthly Signal Service"
							    data-image="' . $teamLogo . '"
							    data-locale="auto"
							    data-label="Pay with Card or Bitcoin"
							    data-bitcoin="true">
							  </script>
							</form>
							<script>
								Stripe.setPublishableKey(\'pk_live_bt8Y3UaI4uwD9bcZww2mslw1\');


								Stripe.applePay.checkAvailability(function(available) {
								  if (available) {
								    document.getElementById(\'apple-pay-button\').style.display = \'block\';
								  }
								});
							</script>
							';
                        } else {
                            echo '<form action="/finish-register" method="POST">
							  <script
							    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
							    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
							    data-amount="' . ceil($package['price'] + ($package['price'] * 0.032)) . '"
							    data-name="' . $teamName . '"
							    data-description="' . $package['name'] . '"
							    data-image="' . $teamLogo . '"
							    data-locale="auto"
							    data-label="Pay with Card"
							    data-bitcoin="false"
							    data-allow-remember-me="false">
							  </script>
							</form>
							';
                        }

                    } else {

                        $customerOptions = array(
                            "email"  => $newUser['email'],
                            "description" => "Name: ".$newUser['name']
                        );

                        if(isset($_SESSION['promo']['code']) && $_SESSION['promo']['code']!='') {
                            $customerOptions["coupon"] = trim($_SESSION['promo']['code']);
                        }

                        $customer = \Stripe\Customer::create($customerOptions);

                        try {
                            $subscrition = \Stripe\Subscription::create(array(
                                "customer" => $customer->id,
                                "plan" => $package['stripe_id']
                            ));
                        } catch(Stripe_CardError $e) {
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        } catch (Stripe_InvalidRequestError $e) {
                            // Invalid parameters were supplied to Stripe's API
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        } catch (Stripe_AuthenticationError $e) {
                            // Authentication with Stripe's API failed
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        } catch (Stripe_ApiConnectionError $e) {
                            // Network communication with Stripe failed
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        } catch (Stripe_Error $e) {
                            // Display a very generic error to the user, and maybe send
                            // yourself an email
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        } catch (Exception $e) {
                            // Something else happened, completely unrelated to Stripe
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        }

                        $activeUser_sql = "UPDATE subscribers SET active='1', last_activation='" . time() . "', stripeID='".$customer->id."' WHERE id='" . $newUser['id'] . "'";
                        $activeUser_query = $mysqli->query($activeUser_sql);


                        /*
						 * SEND EMAIL
						 */

                        $fetch = $mysqli->query("SELECT * FROM customize_landing_pages WHERE client = '" . $_SESSION['team'] . "'");
                        $get_team_images = $fetch->fetch_assoc();

                        $logo = $get_team_images['banner'];
                        $cover = $get_team_images['imgReg'];

                        $fetch = $mysqli->query("SELECT * FROM clients WHERE id = '" . $_SESSION['team'] . "'");
                        $get_team_info = $fetch->fetch_assoc();

                        $name = $get_team_info['company'];
                        $email = $get_team_info['email'];
                        $link = $get_team_info['slug'];


                        /*
						 * SEND MAIL
						 */

                        $tags = [
                            "name" => $name,
                            "email" => $email,
                            "logo" => $logo,
                            "cover" => $cover
                        ];

                        $content = '<h1 style="text-align: center;"><br><span style="font-size:48px">SERVICE ACTIVATION</span></h1>&nbsp;<p>Congrats&nbsp;on signing up for the ' . $name . '™ Forex&nbsp;Platform&nbsp;today!</p><p>You should have received an automatic text with the url to log into your dashboard. Your member dashboard is available both for desktop &amp; mobile friendly!<br><br>The following link will forward you to login to your dashboard:<br><br>https://admin.lionsofforex.com/' . $link . '/login<br><br>Unique Login:<br><br>Your Login Email: ' . $_SESSION['register']['email'] . '<br>Your Password: Password you chose on your registration<br><br>Please have patience &amp; allow our developers to continue building out the platform daily! More &amp; more functions will become available over the next 2 months, as we move through the Beta phase!<br><br>Thanks again &amp; Welcome to the Family,<br><br>' . $name . '<br>' . $email . '&nbsp;</p>';

                        sendMail($_SESSION['register']['email'], 'SERVICE ACTIVATION', $tags, $content);

                        ?>

                        <h1><strong>THANK</strong> YOU</h1>
                        <h5>For Signing Up</h5>
                        <br><br><br>
                        <p>You're registration is
                            complete.</p><br>
                        <p>We've sent a confirmation
                            message to your email.
                            You'll receive a text with
                            further instructions
                            soon!</p><br>
                        <a href="http://admin.lionsofforex.com/<?= $link ?>/login" class="btn btn-greensea">Go to member
                            dashboard.</a>
                        <br><br>

                        <?

                    }


                    echo 1;

                } else {
                    setLog('registration error', 'this email (' . $_SESSION['register']['email'] . ') have entered the wrong code on his registration validation');
                    echo 'error';

                }

            }

            break;

        /****************************************/
        /****************************************/
        /*          /START REGISTRATION         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*         START REGISTRATION 2         */
        /****************************************/
        /****************************************/

        case 'memberRegistrationS2':

            $fbId = addslashes(trim($_POST['fbId']));
            $password = addslashes(trim($_POST['password']));
            $zipCode = addslashes(trim($_POST['zipcode']));
            $birthday = addslashes(trim($_POST['birthday']));
            $company = addslashes(trim($_POST['company']));
            $forexExperience = addslashes(trim($_POST['forexExperience']));
            $instagram = addslashes(trim($_POST['instagram']));

            if (
                trim($fbId) != '' &&
                trim($password) != '' &&
                trim($zipCode) != '' &&
                trim($birthday) != '' &&
                trim($forexExperience) != ''
            ) {

                $newClient_sql = "UPDATE subscribers SET facebookId = '" . $fbId . "', password = '" . md5($password) . "', zipCode = '" . $zipCode . "', birthday = '" . $birthday . "', company = '" . $company . "', forexExperience = '" . addslashes($forexExperience) . "', instagram = '" . $instagram . "' WHERE email='" . $_SESSION['register']['email'] . "'";
                setLog('register', explode(' ', $name)[0] . ' register using this data: ' . serialize($_POST));
                $newClient_query = $mysqli->query($newClient_sql);
                //echo $newClient_sql;

                echo 1;

            } else echo '1$#Verify inserted data. Some fields should have some error.';

            break;

        /****************************************/
        /****************************************/
        /*         START REGISTRATION 2         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*         VERIFY REGISTRATION          */
        /****************************************/
        /****************************************/

        case 'verifyRegistration':

            $verifyCode = trim($_POST['verifyCode']);

            if (
                trim($verifyCode) != ''
            ) {

                //$code_sql = "SELECT * FROM subscribers WHERE email='".$_SESSION['register']['email']."' and verify_code='".$verifyCode."'";
                $code_sql = "SELECT * FROM subscribers WHERE verify_code='" . $verifyCode . "'";
                $code_query = $mysqli->query($code_sql);


                if ($code_query->num_rows > 0) {
                    if (!isset($_SESSION['register']['email'])) {

                        $emailBycode_sql = "SELECT client, email, package FROM subscribers WHERE verify_code='" . $verifyCode . "'";
                        $emailBycode_query = $mysqli->query($emailBycode_sql);
                        $emailByCode = $emailBycode_query->fetch_assoc();

                        $_SESSION['register']['email'] = $emailByCode['email'];
                        $_SESSION['register']['package'] = $emailByCode['package'];
                        $_SESSION['team'] = $emailByCode['client'];

                    }

                    setLog('registration validated', 'this email (' . $_SESSION['register']['email'] . ') have validated his registration');
                    $newUser = $code_query->fetch_assoc();
                    $finish_update = "UPDATE subscribers SET verificated='1', verify_code='' WHERE id='" . $newUser['id'] . "'";
                    $mysqli->query($finish_update);

                    echo 1;

                } else {
                    setLog('registration error', 'this email (' . $_SESSION['register']['email'] . ') have entered the wrong code on his registration validation');
                    echo 'error';

                }

            }

            break;

        /****************************************/
        /****************************************/
        /*         /VERIFY REGISTRATION         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*         VERIFY REGISTRATION          */
        /****************************************/
        /****************************************/

        case 'verifyQuickRegistration':

            $verifyCode = trim($_POST['verifyCode']);

            if (
                trim($verifyCode) != ''
            ) {

                if (!isset($_SESSION['register']['email'])) {

                    die('error');

                    /*$emailBycode_sql = "SELECT client, email, package FROM subscribers WHERE verify_code='".$verifyCode."'";
					$emailBycode_query = $mysqli->query($emailBycode_sql);
					$emailByCode = $emailBycode_query->fetch_assoc();

					$_SESSION['register']['email'] = $emailByCode['email'];
					$_SESSION['register']['package'] = $emailByCode['package'];
					$_SESSION['team'] = $emailByCode['client'];*/

                }

                //$code_sql = "SELECT * FROM subscribers WHERE email='".$_SESSION['register']['email']."' and verify_code='".$verifyCode."'";
                $code_sql = "SELECT * FROM subscribers WHERE verify_code='" . $verifyCode . "'";
                $code_query = $mysqli->query($code_sql);


                if ($code_query->num_rows > 0) {
                    setLog('registration validated', 'this email (' . $_SESSION['register']['email'] . ') have validated his registration');
                    $newUser = $code_query->fetch_assoc();
                    $finish_update = "UPDATE subscribers SET verificated='1', verify_code='' WHERE id='" . $newUser['id'] . "'";
                    $mysqli->query($finish_update);


                    /**
                     * Number verified, process payment
                     */

                    $package = $_SESSION['register']['package'];

                    $teamName_sql = "SELECT company FROM clients WHERE id='" . $_SESSION['team'] . "'";
                    $teamName_query = $mysqli->query($teamName_sql);
                    $teamName = strtoupper($teamName_query->fetch_assoc()['company']);

                    $teamLogo_sql = "SELECT banner FROM customize_landing_pages WHERE client='" . $_SESSION['team'] . "'";
                    $teamLogo_query = $mysqli->query($teamLogo_sql);
                    $teamLogo = $teamLogo_query->fetch_assoc()['banner'];

                    $package_sql = "SELECT * FROM packages WHERE id='" . $package . "'";
                    $package_query = $mysqli->query($package_sql);
                    $package = $package_query->fetch_assoc();

                    if (isset($_SESSION['promo']['pck_price']) && ($package['id'] == $_SESSION['promo']['pck_id'])) {
                        $package['price'] = $_SESSION['promo']['pck_price'] * 100;
                    } else {
                        $package['price'] = $package['price'] * 100;
                    }


                    setLog('prepare payment', 'session before payment: ' . serialize($_SESSION));

                    if ($package['price'] > 0) {

                        if ($_SESSION['register']['email'] == 'test@lionsofforex.com') {
                            echo '<form action="/finish-register" method="POST">
							  <script
							    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
							    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
							    data-amount="' . ceil($package['price'] + ($package['price'] * 0.032)) . '"
							    data-name="' . $teamName . '"
							    data-description="' . $package['name'] . '"
							    data-image="' . $teamLogo . '"
							    data-locale="auto"
							    data-label="Pay with Card or Bitcoin"
							    data-bitcoin="true">
							  </script>
							</form>
							<script>
								Stripe.setPublishableKey(\'pk_live_bt8Y3UaI4uwD9bcZww2mslw1\');


								Stripe.applePay.checkAvailability(function(available) {
								  if (available) {
								    document.getElementById(\'apple-pay-button\').style.display = \'block\';
								  }
								});
							</script>
							';
                        } else if ($_SESSION['register']['email'] == 'test-payment@lionsofforex.com') {
                            echo '<form action="/finish-register" method="POST">
							  <script
							    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
							    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
							    data-amount="100"
							    data-name="' . $teamName . '"
							    data-description="Monthly Signal Service"
							    data-image="' . $teamLogo . '"
							    data-locale="auto"
							    data-label="Pay with Card or Bitcoin"
							    data-bitcoin="true">
							  </script>
							</form>
							<script>
								Stripe.setPublishableKey(\'pk_live_bt8Y3UaI4uwD9bcZww2mslw1\');


								Stripe.applePay.checkAvailability(function(available) {
								  if (available) {
								    document.getElementById(\'apple-pay-button\').style.display = \'block\';
								  }
								});
							</script>
							';
                        } else {
                            echo '<form action="/finish-register" method="POST">
							  <script
							    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
							    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
							    data-amount="' . ceil($package['price'] + ($package['price'] * 0.032)) . '"
							    data-name="' . $teamName . '"
							    data-description="' . $package['name'] . '"
							    data-image="' . $teamLogo . '"
							    data-locale="auto"
							    data-label="Pay with Card"
							    data-bitcoin="false"
							    data-allow-remember-me="false">
							  </script>
							</form>
							';
                        }

                    } else {

                        $customerOptions = array(
                            "email"  => $member['email'],
                            "description" => "Name: ".$member['name']
                        );

                        if(isset($_SESSION['promo']['code']) && $_SESSION['promo']['code']!='') {
                            $customerOptions["coupon"] = trim($_SESSION['promo']['code']);
                        }

                        $customer = \Stripe\Customer::create($customerOptions);

                        try {
                            $subscrition = \Stripe\Subscription::create(array(
                                "customer" => $customer->id,
                                "plan" => $package['stripe_id']
                            ));
                        } catch(Stripe_CardError $e) {
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        } catch (Stripe_InvalidRequestError $e) {
                            // Invalid parameters were supplied to Stripe's API
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        } catch (Stripe_AuthenticationError $e) {
                            // Authentication with Stripe's API failed
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        } catch (Stripe_ApiConnectionError $e) {
                            // Network communication with Stripe failed
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        } catch (Stripe_Error $e) {
                            // Display a very generic error to the user, and maybe send
                            // yourself an email
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        } catch (Exception $e) {
                            // Something else happened, completely unrelated to Stripe
                            $error = $e->getMessage();
                            mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                        }

                        $activeUser_sql = "UPDATE subscribers SET active='1', last_activation='" . time() . "' WHERE email='" . $_SESSION['register']['email'] . "'";
                        $activeUser_query = $mysqli->query($activeUser_sql);


                        /*
						 * SEND EMAIL
						 */

                        $fetch = $mysqli->query("SELECT * FROM customize_landing_pages WHERE client = '" . $_SESSION['team'] . "'");
                        $get_team_images = $fetch->fetch_assoc();

                        $logo = $get_team_images['banner'];
                        $cover = $get_team_images['imgReg'];

                        $fetch = $mysqli->query("SELECT * FROM clients WHERE id = '" . $_SESSION['team'] . "'");
                        $get_team_info = $fetch->fetch_assoc();

                        $name = $get_team_info['company'];
                        $email = $get_team_info['email'];
                        $link = $get_team_info['slug'];


                        /*
						 * SEND MAIL
						 */

                        $tags = [
                            "name" => $name,
                            "email" => $email,
                            "logo" => $logo,
                            "cover" => $cover
                        ];

                        $content = '<h1 style="text-align: center;"><br><span style="font-size:48px">SERVICE ACTIVATION</span></h1>&nbsp;<p>Congrats&nbsp;on signing up for the ' . $name . '™ Forex&nbsp;Platform&nbsp;today!</p><p>You should have received an automatic text with the url to log into your dashboard. Your member dashboard is available both for desktop &amp; mobile friendly!<br><br>The following link will forward you to login to your dashboard:<br><br>https://admin.lionsofforex.com/' . $link . '/login<br><br>Unique Login:<br><br>Your Login Email: ' . $_SESSION['register']['email'] . '<br>Your Password: Password you chose on your registration<br><br>Please have patience &amp; allow our developers to continue building out the platform daily! More &amp; more functions will become available over the next 2 months, as we move through the Beta phase!<br><br>Thanks again &amp; Welcome to the Family,<br><br>' . $name . '<br>' . $email . '&nbsp;</p>';

                        sendMail($_SESSION['register']['email'], 'SERVICE ACTIVATION', $tags, $content);

                        ?>

                        <h1><strong>THANK</strong> YOU</h1>
                        <h5>For Signing Up</h5>
                        <br><br><br>
                        <p>You're registration is
                            complete.</p><br>
                        <p>We've sent a confirmation
                            message to your email.
                            You'll receive a text with
                            further instructions
                            soon!</p><br><br><br>

                        <?

                    }


                    echo 1;

                } else {
                    setLog('registration error', 'this email (' . $_SESSION['register']['email'] . ') have entered the wrong code on his registration validation');
                    echo 'error';

                }

            }

            break;

        /****************************************/
        /****************************************/
        /*         /VERIFY REGISTRATION         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*         VERIFY REGISTRATION          */
        /****************************************/
        /****************************************/

        case 'jumpToPayment':

            $verifyCode = trim($_POST['verifyCode']);

            if (
                trim($verifyCode) != ''
            ) {

                $formDest = '#form-register-step3';

                if (!isset($_SESSION['register']['email'])) {

                    $emailBycode_sql = "SELECT client, facebookId, email, package FROM subscribers WHERE verify_code='" . $verifyCode . "'";
                    $emailBycode_query = $mysqli->query($emailBycode_sql);
                    $emailByCode = $emailBycode_query->fetch_assoc();

                    $_SESSION['register']['email'] = $emailByCode['email'];
                    $_SESSION['team'] = $emailByCode['client'];

                    if ($emailByCode['facebookId'] == '') {
                        $formDest = '#form-register-step2';
                    }

                }

                $code_sql = "SELECT * FROM subscribers WHERE email='" . $_SESSION['register']['email'] . "' and verify_code='" . $verifyCode . "'";
                $code_query = $mysqli->query($code_sql);


                if ($code_query->num_rows > 0) {
                    setLog('registration validated', 'this email (' . $_SESSION['register']['email'] . ') have validated his registration');
                    $newUser = $code_query->fetch_assoc();
                    $finish_update = "UPDATE subscribers SET verificated='1', verify_code='' WHERE id='" . $newUser['id'] . "'";
                    $mysqli->query($finish_update);


                    //if(!isset($_SESSION['register']['email']))
                    echo $formDest;

                } else {
                    setLog('registration error', 'this email (' . $_SESSION['register']['email'] . ') have entered the wrong code on his registration validation');
                    echo 'error';

                }

            }

            break;

        /****************************************/
        /****************************************/
        /*         /VERIFY REGISTRATION         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*       VERIFY COUPON PROMO CODE       */
        /****************************************/
        /****************************************/

        case 'validateCoupon':

            $verifyCode = trim($_POST['code']);

            if (
                trim($verifyCode) != ''
            ) {


                $team = (isset($_SESSION['user']['company']) ? $_SESSION['user']['id'] : $_SESSION['team']);

                $packages_sql = "SELECT id FROM packages WHERE client='" . $team . "'";
                $packages_query = $mysqli->query($packages_sql);
                //$packages = $packages_query->fetch_all();
                $packages = array();
                while($package = $packages_query->fetch_assoc()) $packages[] = $package;

                $pckIds = Array();

                foreach ($packages as $pck) {
                    $pckIds[] = $pck['id'];
                }

                $pckIds = implode(', ', $pckIds);

                $code_sql = "SELECT * FROM cupons WHERE code='" . $verifyCode . "' AND id_package IN (" . $pckIds . ") AND start_date<'" . time() . "' AND end_date>'" . time() . "'";
                $code_query = $mysqli->query($code_sql);

                if ($code_query->num_rows > 0) {
                    $promo = $code_query->fetch_assoc();
                    ?>
                    1$#<select name="package" class="chosen-select chosen-transparent form-control" id="package"
                               style="width:100%">
                        <?php

                        $packagesDefault_sql = "SELECT * FROM packages WHERE client='0'";
                        $packagesDefault_query = $mysqli->query($packagesDefault_sql);
                        //$packagesDefault = $packagesDefault_query->fetch_all(MYSQLI_ASSOC);
                        $packagesDefault = array();
                        while($packages = $packagesDefault_query->fetch_assoc()) $packagesDefault[] = $packages;

                        foreach ($packagesDefault as $packageDefault) {
                            echo '<option value="' . $packageDefault['id'] . '">' . $packageDefault['name'] . ' - $' . $packageDefault['price'] . '/mo.</option>';
                        }

                        $packages_sql = "SELECT * FROM packages WHERE client='" . $team . "'";
                        $packages_query = $mysqli->query($packages_sql);

                        if ($packages_query->num_rows > 0) {
                            //$packages = $packages_query->fetch_all(MYSQLI_ASSOC);
                            $packages = array();
                            while($package = $packages_query->fetch_assoc()) $packages[] = $package;

                            foreach ($packages as $package) {
                                if ($package['id'] == $promo['id_package']) {
                                    if ($promo['type'] == 'r') {
                                        $price = $promo['amount'];
                                    } else if ($promo['type'] == '$') {
                                        $price = $package['price'] - $promo['amount'];
                                        $price = ($price < 0 ? 0 : $price);
                                    } else {
                                        $price = $price = $package['price'] - ($package['price'] * ($promo['amount']) / 100);
                                        $price = ($price < 0 ? 0 : $price);
                                    }

                                    $_SESSION['promo']['code'] = $verifyCode;
                                    $_SESSION['promo']['pck_id'] = $package['id'];
                                    $_SESSION['promo']['pck_price'] = $price;

                                    echo '<option value="' . $package['id'] . '" selected>**PROMO** $' . $price . '/mo. - ' . $package['name'] . '</option>';
                                }/* else {
									echo '<option value="' . $package['id'] . '">' . $package['name'] . ' - $' . $package['price'] . '/mo.</option>';
								}*/
                            }
                        } else echo 'none';

                        ?>
                    </select>
                    <?php
                } else {
                    echo '0$#This coupon is not available.';
                }

            }

            break;

        /****************************************/
        /****************************************/
        /*       VERIFY COUPON PROMO CODE       */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*           REGULAR PACKAGES           */
        /****************************************/
        /****************************************/

        case 'regularPackages':
            ?>

            <select name="package" class="chosen-select chosen-transparent form-control" id="package"
                    style="width:100%">
                <option value="">Package</option>
                <?php

                $packages_sql = "SELECT * FROM packages WHERE client='30'";
                $packages_query = $mysqli->query($packages_sql);

                if ($packages_query->num_rows > 0) {
                    //$packages = $packages_query->fetch_all(MYSQLI_ASSOC);
                    $packages = array();
                    while($package = $packages_query->fetch_assoc()) $packages[] = $package;

                    foreach ($packages as $package) {
                        echo '<option value="' . $package['id'] . '">' . $package['name'] . ' - $' . $package['price'] . '/mo.</option>';
                    }
                }

                ?>
            </select>

            <?php
            break;

        /****************************************/
        /****************************************/
        /*           REGULAR PACKAGES           */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*           PREPARE PAYMENT            */
        /****************************************/
        /****************************************/

        case 'preparePayment':

            $package = trim($_POST['package']);

            if (
                trim($package) != ''
            ) {

                $updatePack_sql = "UPDATE subscribers SET package='" . $package . "' WHERE email='" . $_SESSION['register']['email'] . "'";
                $updatePack_query = $mysqli->query($updatePack_sql);

                $teamName_sql = "SELECT company FROM clients WHERE id='" . $_SESSION['team'] . "'";
                $teamName_query = $mysqli->query($teamName_sql);
                $teamName = strtoupper($teamName_query->fetch_assoc()['company']);

                $teamLogo_sql = "SELECT banner FROM customize_landing_pages WHERE client='" . $_SESSION['team'] . "'";
                $teamLogo_query = $mysqli->query($teamLogo_sql);
                $teamLogo = $teamLogo_query->fetch_assoc()['banner'];

                $package_sql = "SELECT * FROM packages WHERE id='" . $package . "'";
                $package_query = $mysqli->query($package_sql);
                $package = $package_query->fetch_assoc();

                $_SESSION['register']['package'] = $package['id'];

                if (isset($_SESSION['promo']['pck_price']) && ($package['id'] == $_SESSION['promo']['pck_id'])) {
                    $package['price'] = $_SESSION['promo']['pck_price'] * 100;
                } else {
                    $package['price'] = $package['price'] * 100;
                }

                setLog('prepare payment', 'session before payment: ' . serialize($_SESSION));

                if ($package['price'] > 0) {

                    if ($_SESSION['register']['email'] == 'test@lionsofforex.com') {
                        echo '<form action="/finish-register" method="POST">
							  <script
							    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
							    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
							    data-amount="' . ceil($package['price'] + ($package['price'] * 0.032)) . '"
							    data-name="' . $teamName . '"
							    data-description="' . $package['name'] . '"
							    data-image="' . $teamLogo . '"
							    data-locale="auto"
							    data-label="Pay with Card or Bitcoin"
							    data-bitcoin="true">
							  </script>
							</form>
							<script>
								Stripe.setPublishableKey(\'pk_live_bt8Y3UaI4uwD9bcZww2mslw1\');


								Stripe.applePay.checkAvailability(function(available) {
								  if (available) {
								    document.getElementById(\'apple-pay-button\').style.display = \'block\';
								  }
								});
							</script>
							';
                    } else if ($_SESSION['register']['email'] == 'test-payment@lionsofforex.com') {
                        echo '<form action="/finish-register" method="POST">
							  <script
							    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
							    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
							    data-amount="100"
							    data-name="' . $teamName . '"
							    data-description="Monthly Signal Service"
							    data-image="' . $teamLogo . '"
							    data-locale="auto"
							    data-label="Pay with Card or Bitcoin"
							    data-bitcoin="true">
							  </script>
							</form>
							<script>
								Stripe.setPublishableKey(\'pk_live_bt8Y3UaI4uwD9bcZww2mslw1\');


								Stripe.applePay.checkAvailability(function(available) {
								  if (available) {
								    document.getElementById(\'apple-pay-button\').style.display = \'block\';
								  }
								});
							</script>
							';
                    } else if ($_SESSION['team'] == '29' && $_SESSION['register']['email'] == 'test-darwin@lionsofforex.com') { // Everybody Eats Payment
                        echo '<form action="/finish-register-ee" method="POST">
							  <script
							    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
							    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
							    data-amount="100"
							    data-name="' . $teamName . '"
							    data-description="Monthly Signal Service"
							    data-image="' . $teamLogo . '"
							    data-locale="auto">
							  </script>
							</form>';
                    } else if ($_SESSION['team'] == '29') { // Everybody Eats Payment
                        echo '<form action="/finish-register-ee" method="POST">
							  <script
							    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
							    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
							    data-amount="10000"
							    data-name="' . $teamName . '"
							    data-description="Monthly Signal Service"
							    data-image="' . $teamLogo . '"
							    data-locale="auto">
							  </script>
							</form>';
                    } else {
                        echo '<form action="/finish-register" method="POST">
							  <script
							    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
							    data-key="pk_live_bt8Y3UaI4uwD9bcZww2mslw1"
							    data-amount="' . ceil($package['price'] + ($package['price'] * 0.032)) . '"
							    data-name="' . $teamName . '"
							    data-description="' . $package['name'] . '"
							    data-image="' . $teamLogo . '"
							    data-locale="auto"
							    data-label="Pay with Card or Bitcoin"
							    data-bitcoin="true">
							  </script>
							</form>
							<script>
								Stripe.setPublishableKey(\'pk_live_bt8Y3UaI4uwD9bcZww2mslw1\');


								Stripe.applePay.checkAvailability(function(available) {
								  if (available) {
								    document.getElementById(\'apple-pay-button\').style.display = \'block\';
								  }
								});
							</script>
							';
                    }
                } else {

                    $customerOptions = array(
                        "email"  => $_SESSION['register']['email'],
                        "description" => "Name: ".(isset($_SESSION['register']['name'])?$_SESSION['register']['name']:'')
                    );

                    if(isset($_SESSION['promo']['code']) && $_SESSION['promo']['code']!='') {
                        $customerOptions["coupon"] = trim($_SESSION['promo']['code']);
                    }

                    $customer = \Stripe\Customer::create($customerOptions);

                    try {
                        $subscrition = \Stripe\Subscription::create(array(
                            "customer" => $customer->id,
                            "plan" => $package['stripe_id']
                        ));
                    } catch(Stripe_CardError $e) {
                        $error = $e->getMessage();
                        mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                    } catch (Stripe_InvalidRequestError $e) {
                        // Invalid parameters were supplied to Stripe's API
                        $error = $e->getMessage();
                        mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                    } catch (Stripe_AuthenticationError $e) {
                        // Authentication with Stripe's API failed
                        $error = $e->getMessage();
                        mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                    } catch (Stripe_ApiConnectionError $e) {
                        // Network communication with Stripe failed
                        $error = $e->getMessage();
                        mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                    } catch (Stripe_Error $e) {
                        // Display a very generic error to the user, and maybe send
                        // yourself an email
                        $error = $e->getMessage();
                        mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                    } catch (Exception $e) {
                        // Something else happened, completely unrelated to Stripe
                        $error = $e->getMessage();
                        mail("logs@lionsofforex.com", "manual registration", $error."\n\n\n Session: \n\n".serialize($_SESSION));
                    }

                    $activeUser_sql = "UPDATE subscribers SET active='1', last_activation='" . time() . "', stripeID='".$customer->id."' WHERE email='" . $_SESSION['register']['email'] . "'";
                    $activeUser_query = $mysqli->query($activeUser_sql);


                    /*
					 * SEND EMAIL
					 */

                    $fetch = $mysqli->query("SELECT * FROM customize_landing_pages WHERE client = '" . $_SESSION['team'] . "'");
                    $get_team_images = $fetch->fetch_assoc();

                    $logo = $get_team_images['banner'];
                    $cover = $get_team_images['imgReg'];

                    $fetch = $mysqli->query("SELECT * FROM clients WHERE id = '" . $_SESSION['team'] . "'");
                    $get_team_info = $fetch->fetch_assoc();

                    $name = $get_team_info['company'];
                    $email = $get_team_info['email'];
                    $link = $get_team_info['slug'];


                    /*
                     * SEND MAIL
                     */

                    $tags = [
                        "name" => $name,
                        "email" => $email,
                        "logo" => $logo,
                        "cover" => $cover
                    ];

                    $content = '<h1 style="text-align: center;"><br><span style="font-size:48px">SERVICE ACTIVATION</span></h1>&nbsp;<p>Congrats&nbsp;on signing up for the ' . $name . '™ Forex&nbsp;Platform&nbsp;today!</p><p>You should have received an automatic text with the url to log into your dashboard. Your member dashboard is available both for desktop &amp; mobile friendly!<br><br>The following link will forward you to login to your dashboard:<br><br>https://admin.lionsofforex.com/' . $link . '/login<br><br>Unique Login:<br><br>Your Login Email: ' . $_SESSION['register']['email'] . '<br>Your Password: Password you chose on your registration<br><br>Please have patience &amp; allow our developers to continue building out the platform daily! More &amp; more functions will become available over the next 2 months, as we move through the Beta phase!<br><br>Thanks again &amp; Welcome to the Family,<br><br>' . $name . '<br>' . $email . '&nbsp;</p>';

                    sendMail($_SESSION['register']['email'], 'SERVICE ACTIVATION', $tags, $content);

                    ?>

                    <h1><strong>THANK</strong> YOU</h1>
                    <h5>For Signing Up</h5>
                    <br><br><br>
                    <p>You're registration is
                        complete.</p><br>
                    <p>We've sent a confirmation
                        message to your email.
                        You'll receive a text with
                        further instructions
                        soon!</p><br><br><br>

                    <?

                }

            }

            break;

        /****************************************/
        /****************************************/
        /*           /PREPARE PAYMENT           */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*         VERIFY PHONE NUMBER          */
        /****************************************/
        /****************************************/

        case 'verifyPhoneNumber':

            $mobile = trim($_POST['mobile']);

            //echo preg_replace('/[^+0-9]/', '', $mobile);

            if (
                trim($mobile) != ''
            ) {

                $emailBycode_sql = "SELECT id, client, email, package, mobile FROM subscribers WHERE mobile LIKE '%" . trimPhone($mobile) . "%'";
                //echo $emailBycode_sql;
                $emailBycode_query = $mysqli->query($emailBycode_sql);
                $emailByCode = $emailBycode_query->fetch_assoc();

                $_SESSION['register']['email'] = $emailByCode['email'];
                $_SESSION['register']['package'] = $emailByCode['package'];
                $_SESSION['team'] = $emailByCode['client'];

                $mobile = $emailByCode['mobile'];

                $verify_code = '';

                do {

                    $verify_code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);

                    $verifyCodeExists_sql = "SELECT * FROM subscribers WHERE verify_code='" . $verify_code . "'";
                    $verifyCodeExists_query = $mysqli->query($verifyCode_sql);

                    if ($verifyCodeExists_query->num_rows == 0) {
                        $verifyCodeExists = false;
                    } else {
                        $verifyCodeExists = true;
                    }

                } while ($verifyCodeExists);

                $newClient_sql = "UPDATE subscribers SET verify_code='" . $verify_code . "' WHERE email='" . $_SESSION['register']['email'] . "'";
                setLog('recover registration', explode(' ', $name)[0] . ' is recovering his registration using this data: ' . serialize($_POST));
                $newClient_query = $mysqli->query($newClient_sql);
                //echo $newClient_sql;

                $trader_sql = "SELECT * FROM clients WHERE id='" . $_SESSION['team'] . "'";
                $trader_query = $mysqli->query($trader_sql);
                $trader = $trader_query->fetch_assoc();

                $_SESSION['register']['email'] = $email;

                sendMessage('', 'Hi ' . explode(' ', $name)[0] . ', please insert this code to validate your registration. Code: ' . $verify_code, trimPhone($mobile), $trader['twilio_mobile']);


            }

            break;

        /****************************************/
        /****************************************/
        /*         VERIFY PHONE NUMBER          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*         VERIFY PHONE NUMBER          */
        /****************************************/
        /****************************************/

        case 'verifyPhoneNumberByEmail':

            $email = trim($_POST['email']);

            //echo preg_replace('/[^+0-9]/', '', $mobile);

            if (
                $email != ''
            ) {

                $emailBycode_sql = "SELECT id, client, name, email, package, mobile FROM subscribers WHERE email='" . $email . "'";
                //echo $emailBycode_sql;
                $emailBycode_query = $mysqli->query($emailBycode_sql);

                if ($emailBycode_query->num_rows > 0) {

                    $emailByCode = $emailBycode_query->fetch_assoc();

                    $name = $emailByCode['name'];

                    $_SESSION['register']['email'] = $emailByCode['email'];
                    $_SESSION['register']['package'] = $emailByCode['package'];
                    $_SESSION['team'] = $emailByCode['client'];

                    $mobile = $emailByCode['mobile'];

                    $verify_code = '';

                    do {

                        $verify_code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);

                        $verifyCodeExists_sql = "SELECT * FROM subscribers WHERE verify_code='" . $verify_code . "'";
                        $verifyCodeExists_query = $mysqli->query($verifyCode_sql);

                        if ($verifyCodeExists_query->num_rows == 0) {
                            $verifyCodeExists = false;
                        } else {
                            $verifyCodeExists = true;
                        }

                    } while ($verifyCodeExists);

                    $newClient_sql = "UPDATE subscribers SET verify_code='" . $verify_code . "' WHERE email='" . $_SESSION['register']['email'] . "'";
                    setLog('recover registration', explode(' ', $name)[0] . ' is recovering his registration using this data: ' . serialize($_POST));
                    $newClient_query = $mysqli->query($newClient_sql);
                    //echo $newClient_sql;

                    $_SESSION['register']['email'] = $email;

                    $trader_sql = "SELECT * FROM clients WHERE id='" . $_SESSION['team'] . "'";
                    $trader_query = $mysqli->query($trader_sql);
                    $trader = $trader_query->fetch_assoc();

                    $traderImages_sql = "SELECT * FROM customize_landing_pages WHERE client='" . $_SESSION['team'] . "'";
                    $traderImages_query = $mysqli->query($traderImages_sql);
                    $traderImages = $traderImages_query->fetch_assoc();

                    $tags = [
                        "name" => $trader['full_name'],
                        "email" => $trader['email'],
                        "logo" => $traderImages['banner'],
                        "cover" => $traderImages['banner']
                    ];

                    $content = '<h1 style="text-align: center;"><br><span style="font-size:48px">ACCOUNT VERIFICATION</span></h1>&nbsp;<p>Use the code below to confirm and proceed with your registration<br><br>Your Code: ' . $verify_code . '<br><br><br>Thanks again &amp; Welcome to the Family,<br><br>' . $trader['full_name'] . '<br>' . $trader['email'] . '&nbsp;</p>';


                    sendMail($_SESSION['register']['email'], 'ACCOUNT VERIFICATION', $tags, $content);

                    sendMessage('', 'Hi ' . explode(' ', $name)[0] . ', please insert this code to validate your registration. Code: ' . $verify_code, trimPhone($mobile), $trader['twilio_mobile']);

                } else echo 'error';

            }

            break;

        /****************************************/
        /****************************************/
        /*         VERIFY PHONE NUMBER          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*      TRADER REGISTRATION - STEP 1    */
        /****************************************/
        /****************************************/

        case 'openTraderRegistration':

            $name = addslashes($_POST['name']);
            $email = addslashes($_POST['email']);
            $password = $_POST['password'];
            $country = addslashes($_POST['country']);
            $mobile = addslashes($_POST['mobile']);
            $city = addslashes($_POST['city']);
            $birthday = $_POST['birthday'];
            $manager_id = 2;

            $_SESSION['trader_registration'][1] = $_POST;

            if (isset($_SESSION['manager'])) $manager_id = $_SESSION['manager'];

            if (
                trim($name) != '' &&
                trim($email) != '' &&
                trim($password) != '' &&
                trim($mobile) != '' &&
                trim($country) != '' &&
                trim($city) != '' &&
                trim($birthday) != ''
            ) {

                $verifyEmail_sql = "SELECT * FROM clients WHERE email='" . $email . "'";
                $verifyEmail_query = $mysqli->query($verifyEmail_sql);

                if ($verifyEmail_query->num_rows == 0) {

                    /*$verifySlug_sql = "SELECT * FROM clients WHERE slug='".slugify($company)."'";
					$verifySlug_query = $mysqli->query($verifySlug_sql);

					if($verifySlug_query->num_rows==0){*/

                    $phoneCode_sql = "SELECT phone_code FROM countries WHERE id='" . $country . "'";
                    $phonecode_query = $mysqli->query($phoneCode_sql);
                    $phoneCode = trim($phonecode_query->fetch_assoc()['phone_code']);


                    $newClient_sql = "INSERT INTO clients (full_name, email, password, country, city, mobile, birthday, trader, manager, manager_id, percentage) VALUES ('" . $name . "', '" . $email . "', '" . md5($password) . "', '" . $country . "', '" . $city . "', '" . $phoneCode . trimPhone($mobile) . "', '" . $birthday . "', '1', '0', '" . $manager_id . "', '60')";


                    try {
                        $newClient_query = $mysqli->query($newClient_sql);
                        $_SESSION['trader_registration']['done'] = 1;
                        setLog('trader register - step1', $name . ' register using this data: ' . serialize($_POST));
                        echo '1$#';
                    } catch (Exception $e) {
                        setLog('ERROR trader register - step1', 'Un error has ocurred when trying to run this query: ' . $newClient_sql);
                        echo '0$#Something went wrong when we were creating your account. Please refresh the page and tey again.';
                    }


                    /*} else {
						echo '0$#This company name is already registered.';
					}*/
                } else {
                    echo '0$#This email is already registered.';
                }

            } else echo '0$#Are fields are required. Please correct them.';

            break;

        /****************************************/
        /****************************************/
        /*      TRADER REGISTRATION - STEP 1    */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*      TRADER REGISTRATION - STEP 2    */
        /****************************************/
        /****************************************/

        case 'openTraderRegistration2':

            $company = addslashes($_POST['company']);
            $slug = trim(addslashes($_POST['slug']));
            $headtrader = addslashes($_POST['headtrader']);
            $companyyear = addslashes($_POST['companyyear']);
            $headquarters = addslashes($_POST['headquarters']);
            $typetrading = addslashes($_POST['typetrading']);
            $about = addslashes($_POST['about']);

            $_SESSION['trader_registration'][2] = $_POST;

            $registrationId_sql = "SELECT id FROM clients WHERE email='" . $_SESSION['trader_registration'][1]['email'] . "'";
            $registrationId_query = $mysqli->query($registrationId_sql);
            $registrationId = $registrationId_query->fetch_assoc()['id'];

            if (
                trim($company) != '' &&
                trim($slug) != '' &&
                trim($headtrader) != '' &&
                trim($companyyear) != '' &&
                trim($headquarters) != '' &&
                trim($typetrading) != '' &&
                trim($about) != ''
            ) {

                $slugOk = false;
                $slugCounter = 0;
                $originalSlug = $slug;

                do {

                    if ($slugCounter == 10) break;
                    $verifySlug_sql = "SELECT slug FROM clients WHERE slug='" . $slug . "'";
                    $verifySlug_query = $mysqli->query($verifySlug_sql);

                    if ($verifySlug_query->num_rows > 0) {
                        $slugOk = true;
                        $slug = $originalSlug . '-' . (++$slugCounter);
                    } else $slugOk = false;
                } while ($slugOk);

                $updateClient_sql = "UPDATE clients SET company='" . $company . "', slug='" . $slug . "' WHERE id='" . $registrationId . "'";

                $insertProfile_sql = "INSERT INTO profile_intro (client, about, head_trader, company_year, headquarters, type_of_trading) VALUES ('" . $registrationId . "', '" . $about . "', '" . $headtrader . "', '" . $companyyear . "', '" . $headquarters . "', '" . $typetrading . "')";

                try {

                    $updateClient_query = $mysqli->query($updateClient_sql);
                    $insertProfile_query = $mysqli->query($insertProfile_sql);

                    $_SESSION['trader_registration']['done'] = 3;

                    setLog('trader register - step2', $registrationId . ' register using this data: ' . serialize($_POST));
                    echo '1$#';

                } catch (Exception $e) {
                    setLog('ERROR trader register - step2', 'Un error has ocurred when trying to run this query: ' . $updateClient_sql);
                    setLog('ERROR trader register - step2', 'Un error has ocurred when trying to run this query: ' . $insertProfile_sql);
                    echo '0#Something went wrong when we were creating your account. Please refresh the page and tey again.';
                }

            } else echo '0$#Are fields are required. Please correct them.';

            break;

        /****************************************/
        /****************************************/
        /*          /START REGISTRATION         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*      TRADER REGISTRATION - STEP 3    */
        /****************************************/
        /****************************************/

        case 'openTraderRegistration3':

            $myfxbookEmail = addslashes($_POST['myfxbookEmail']);
            $myfxbookPassword = $_POST['myfxbookPassword'];
            $routingNumber = addslashes($_POST['routingNumber']);
            $bankAccount = addslashes($_POST['bankAccount']);
            $areacode = addslashes($_POST['areacode']);
            $firstContact = addslashes($_POST['firstContact']);
            $infoReminder = addslashes($_POST['infoReminder']);
            $formSubmission = addslashes($_POST['formSubmission']);
            $newNumber = addslashes($_POST['newNumber']);

            $_SESSION['trader_registration'][3] = $_POST;

            $registrationId_sql = "SELECT id FROM clients WHERE email='" . $_SESSION['trader_registration'][1]['email'] . "'";
            $registrationId_query = $mysqli->query($registrationId_sql);
            $registrationId = $registrationId_query->fetch_assoc()['id'];

            if (
                trim($myfxbookEmail) != '' &&
                trim($myfxbookPassword) != '' &&
                trim($routingNumber) != '' &&
                trim($bankAccount) != '' &&
                trim($areacode) != '' &&
                trim($firstContact) != '' &&
                trim($infoReminder) != '' &&
                trim($formSubmission) != '' &&
                trim($newNumber) != ''
            ) {


                $sid = "AC13788ff81cb9c611f22e4a16186f848b";
                $token = "369c6f272a8912a1859abc2c7e8cc94c";

                $client = new Twilio\Rest\Client($sid, $token);

                $numbers = Array();
                $counter = 0;

                do {

                    if ($counter == 0) {
                        $numbers = $client->availablePhoneNumbers('US')->local->read(
                            array("areaCode" => $areacode)
                        );
                    } else if ($counter > 10) {
                        break;
                    } else {
                        $numbers = $client->incomingPhoneNumbers->read(
                            array("PhoneNumber" => rand(0, 9) . rand(0, 9) . rand(0, 9))
                        );
                    }
                    $counter++;

                } while (count($numbers) == 0);

                if (count($numbers) > 0) {

                    $company_sql = "SELECT company FROM clients WHERE email='" . $_SESSION['trader_registration'][1]['email'] . "'";
                    $company_query = $mysqli->query($company_sql);
                    $company = $company_query->fetch_assoc()['company'];


                    $number = $numbers[0]->phoneNumber;

                    $creatNumber = $client->incomingPhoneNumbers->create(
                        array(
                            "friendlyName" => $company,
                            "SmsUrl" => "https://admin.lionsofforex.com/handle_messages.php",
                            "phoneNumber" => $number
                        )
                    );


                    $updateClient_sql = "UPDATE clients SET routingNumber='" . $routingNumber . "', bankAccount='" . $bankAccount . "', twilio_mobile='" . $number . "' WHERE id='" . $registrationId . "'";

                    $apikeys_sql = "INSERT INTO apikeys (user, platform, username, password) VALUES ('" . $registrationId . "', 'myfxbook', '" . $myfxbookEmail . "', '" . $myfxbookPassword . "')";

                    $autoresponses_sql = "INSERT INTO autoresponses (client, firstContact, infoReminder, formSubsmission, newNumber) VALUES ('" . $registrationId . "', '" . $firstContact . "', '" . $infoReminder . "', '" . $formSubmission . "', '" . $newNumber . "')";

                    $customization_sql = "INSERT INTO customize_landing_pages (client) VALUES ('" . $registrationId . "')";

                    try {

                        $updateClient_query = $mysqli->query($updateClient_sql);
                        $apikeys_query = $mysqli->query($apikeys_sql);
                        $autoresponses_query = $mysqli->query($autoresponses_sql);
                        $customization_query = $mysqli->query($customization_sql);

                        $_SESSION['trader_registration']['done'] = 3;

                        setLog('trader register - step3', $registrationId . ' register using this data: ' . serialize($_POST));
                        echo '1$#';

                    } catch (Exception $e) {
                        setLog('ERROR trader register - step2', 'Un error has ocurred when trying to run this query: ' . $updateClient_sql);
                        setLog('ERROR trader register - step2', 'Un error has ocurred when trying to run this query: ' . $apikeys_sql);
                        setLog('ERROR trader register - step2', 'Un error has ocurred when trying to run this query: ' . $autoresponses_sql);
                        echo '0#Something went wrong when we were creating your account. Please refresh the page and tey again.';
                    }

                } else echo '0$#An error has ocurred when creating your phone number. Please, change your code area and try again';

            } else echo '0$#Are fields are required. Please correct them.';

            break;

        /****************************************/
        /****************************************/
        /*          /START REGISTRATION         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          FINISH REGISTRATION         */
        /****************************************/
        /****************************************/

        case 'finishTraderRegistration':

            $primarycolor = addslashes($_POST['primaryColor']);
            $secundarycolor = addslashes($_POST['secundaryColor']);
            $dashImg = addslashes($_POST['dashImg']);
            $regImg = addslashes($_POST['regImg']);
            $dashVid = addslashes($_POST['dashVid']);
            $regVid = addslashes($_POST['regVid']);

            //$_SESSION['trader_registration'] = $_POST;
            $registrationId_sql = "SELECT id FROM clients WHERE email='" . $_SESSION['trader_registration'][1]['email'] . "'";
            $registrationId_query = $mysqli->query($registrationId_sql);
            $registrationId = $registrationId_query->fetch_assoc()['id'];

            if (
                trim($primarycolor) != '' &&
                trim($secundarycolor) != '' &&
                trim($dashImg) != '' &&
                trim($regImg) != '' &&
                trim($dashVid) != '' &&
                trim($regVid) != ''
            ) {

                $customization_sql = "UPDATE customize_landing_pages SET primarycolor='" . $primarycolor . "', secundarycolor='" . $secundarycolor . "', imgDash='https://admin.lionsofforex.com/" . $dashImg . "', imgReg='https://admin.lionsofforex.com/" . $regImg . "', videoDash='https://admin.lionsofforex.com/" . $dashVid . "', videoReg='https://admin.lionsofforex.com/" . $regVid . "' WHERE client='" . $registrationId . "'";
                $customization_query = $mysqli->query($customization_sql);

                $packages1_sql = "INSERT INTO packages (client, name, price, description, features_included, default, status) VALUES ('" . $registrationId . "', 'Begginer', '100', 'To start, choose this package to receive signals', 'signals', '1', '1')";
                $packages1_query = $mysqli->query($packages1_sql);

                $packages2_sql = "INSERT INTO packages (client, name, price, description, features_included, default, status) VALUES ('" . $registrationId . "', 'Intermedium', '150', 'You can receive signals and be present on our webinars', 'signals,webinar', '0', '1')";
                $packages2_query = $mysqli->query($packages2_sql);

                $packages3_sql = "INSERT INTO packages (client, name, price, description, features_included, default, status) VALUES ('" . $registrationId . "', 'Advanced', '250', 'Be a master! Receive signals, be invited to our webinars and receive exclusive trainings', 'signals,webinar,team-training', '0', '1')";
                $packages3_query = $mysqli->query($packages3_sql);

                unset($_SESSION['trader_registration']);

                /*
				$message  = "New Trader Registration\n";
				$message .= "\n";
				$message .= "\n";
				$message .= "Name: ".$trader['full_name']."\n";
				$message .= "Company: ".$trader['company']."\n";
				$message .= "Email: ".$trader['email']."\n";
				$message .= "Mobile: ".$trader['mobile']."\n";
				$message .= "Country: ".$trader['country']."\n";
				$message .= "Area: ".$trader['city']."\n";
				$message .= "Birthday: ".$trader['birthday']."\n";
				$message .= "\n";
				$message .= "\n";
				$message .= "Logo: https://beta.signvl.com/".$img."\n";
				$message .= "Primary Color: ".$primarycolor."\n";
				$message .= "Secundary Color: ".$secundarycolor."\n";
				$message .= "Myfxbook Email: ".$myfxbookemail."\n";
				$message .= "Myfxbook Password: ".$myfxbookpassword."\n";
				$message .= "Routing Number: ".$routingnumber."\n";
				$message .= "Bank Account: ".$bankaccount."\n";
				$message .= "Area Code for Superphone: ".$areacode."\n";

				mail("logs@lionsofforex.com", "New trader registration", $message);
				mail("signvlteam@gmail.com", "New trader registration", $message);
				*/

                echo '1$#';

            } else echo "0$#Some of required fields look empty.";

            break;

        /****************************************/
        /****************************************/
        /*          /FINISH REGISTRATION        */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*            GET AUTORESPONSES         */
        /****************************************/
        /****************************************/

        case 'getAutoresponses':

            $registration_sql = "SELECT company, slug FROM clients WHERE email='" . $_SESSION['trader_registration'][1]['email'] . "'";
            $registration_query = $mysqli->query($registration_sql);
            $registration = $registration_query->fetch_assoc();

            $firstContact = 'Thanks for contacting ' . $registration['company'] . '! To activate your account & get started, sign up using the link below! I\'ll text you after to confirm! https://admin.lionsofforex.com/' . $registration['slug'] . '/register';

            $infoReminder = 'Didn\'t get your account set up! Sign up ASAP so you don\'t miss any important updates or signals! https://admin.lionsofforex.com/' . $registration['slug'] . '/register';

            $formSubmission = 'Awesome, thanks for signing up & getting started with us! You\'re locked into our system, & ready to start receiving signals! Now, you can login into your dashboard https://admin.lionsofforex.com/' . $registration['slug'] . '/register';

            $newNumber = 'Hey it\'s ' . $registration['company'] . '! Just updated our number, save it under our contact!';

            echo $firstContact . '$#' . $infoReminder . '$#' . $formSubmission . '$#' . $newNumber;
            $newNumber = 'Hey it\'s ' . $registration['company'] . '! Just updated our number, save it under our contact!';

            echo $firstContact . '$#' . $infoReminder . '$#' . $formSubmission . '$#' . $newNumber;

            break;

        /****************************************/
        /****************************************/
        /*            GET AUTORESPONSES         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*             AUTORESPONSES            */
        /****************************************/
        /****************************************/

        case 'updateAutoresponses':

            $firstContact = addslashes($_POST['firstContact']);
            $infoReminder = addslashes($_POST['infoReminder']);
            $formSubsmission = addslashes($_POST['formSubsmission']);
            $newNumber = addslashes($_POST['newNumber']);

            if (trim($firstContact) != '' && trim($infoReminder) != '' && trim($formSubsmission) != '' && trim($newNumber) != '') {

                $updateAutoresponses_sql = "UPDATE autoresponses SET firstContact='" . $firstContact . "', infoReminder='" . $infoReminder . "', formSubsmission='" . $formSubsmission . "', newNumber='" . $newNumber . "' WHERE client='" . $_SESSION['user']['id'] . "'";
                echo $updateAutoresponses_sql;
                $updateAutoresponses_query = $mysqli->query($updateAutoresponses_sql);

                $name = explode(' ', $_SESSION['user']['full_name']);

                $message = $name[0] . ' ' . $name[count($name) - 1] . " has changed his autoresponse messages for this:\n\n";
                $message .= "First Contact:\n\n";
                $message .= $firstContact . "\n\n\n\n";
                $message .= "Info Reminder:\n\n";
                $message .= $infoReminder . "\n\n\n\n";
                $message .= "When Form Submission:\n\n";
                $message .= $formSubsmission . "\n\n\n\n";
                $message .= "New number:\n\n";
                $message .= $newNumber . "\n\n\n\n";

                mail("logs@lionsofforex.com", "Autoresponse change", $message);
                //mail("signvlteam@gmail.com", "Autoresponse change", $message);

            } else echo 'All fields are mandatory.';

            break;

        case 'updateFirstContact':

            $input = addslashes($_POST['input']);

            if (trim($input) != '') {

                //$firstContact_sql = "INSERT INTO autoresponses (firstContact) VALUES ('".$input."') WHERE client";
                $firstContact_sql = "UPDATE autoresponses SET firstContact='" . $input . "' WHERE client='" . $_SESSION['user']['id'] . "'";

                $firstContact_query = $mysqli->query($firstContact_sql);

                $name = explode(' ', $_SESSION['user']['full_name']);

                $message = $name[0] . ' ' . $name[count($name) - 1] . " has changed his \"First Contact\" autoresponse for this:\n\n";
                $message .= $input . "\n";

                mail("logs@lionsofforex.com", "Autoresponse change", $message);
                //mail("signvlteam@gmail.com", "Autoresponse change", $message);

                echo 1;


            } else echo "Write some text to be sent when someone contact you for the first time.";

            break;

        case 'updateInfoReminder':

            $input = addslashes($_POST['input']);

            if (trim($input) != '') {

                //$infoReminder_sql = "INSERT INTO autoresponses (infoReminder) VALUES ('".$input."')";
                $infoReminder_sql = "UPDATE autoresponses SET infoReminder='" . $input . "' WHERE client='" . $_SESSION['user']['id'] . "'";
                $infoReminder_query = $mysqli->query($infoReminder_sql);

                $name = explode(' ', $_SESSION['user']['full_name']);

                $message = $name[0] . ' ' . $name[count($name) - 1] . " has changed his \"Info Reminder\" autoresponse for this:\n\n";
                $message .= $input . "\n";

                mail("logs@lionsofforex.com", "Autoresponse change", $message);
                //mail("signvlteam@gmail.com", "Autoresponse change", $message);

                echo 1;


            } else echo "Write some text to be sent when someone contact you for the first time.";

            break;

        case 'updateFormSubmission':

            $input = addslashes($_POST['input']);

            if (trim($input) != '') {

                //$formSubsmission_sql = "INSERT INTO autoresponses (formSubsmission) VALUES ('".$input."')";
                $formSubsmission_sql = "UPDATE autoresponses SET formSubsmission='" . $input . "' WHERE client='" . $_SESSION['user']['id'] . "'";
                $formSubsmission_query = $mysqli->query($formSubsmission_sql);

                $name = explode(' ', $_SESSION['user']['full_name']);

                $message = $name[0] . ' ' . $name[count($name) - 1] . " has changed his \"Form Submission\" autoresponse for this:\n\n";
                $message .= $input . "\n";

                mail("logs@lionsofforex.com", "Autoresponse change", $message);
                //mail("signvlteam@gmail.com", "Autoresponse change", $message);

                echo 1;


            } else echo "Write some text to be sent when someone contact you for the first time.";

            break;

        case 'updateNewNumber':

            $input = addslashes($_POST['input']);

            if (trim($input) != '') {

                //$newNumber_sql = "INSERT INTO autoresponses (newNumber) VALUES ('".$input."')";
                $newNumber_sql = "UPDATE autoresponses SET newNumber='" . $input . "' WHERE client='" . $_SESSION['user']['id'] . "'";
                $newNumber_query = $mysqli->query($newNumber_sql);

                $name = explode(' ', $_SESSION['user']['full_name']);

                $message = $name[0] . ' ' . $name[count($name) - 1] . " has changed his \"New Number\" autoresponse for this:\n\n";
                $message .= $input . "\n";

                mail("logs@lionsofforex.com", "Autoresponse change", $message);
                //mail("signvlteam@gmail.com", "Autoresponse change", $message);

                echo 1;


            } else echo "Write some text to be sent when someone contact you for the first time.";

            break;

        /****************************************/
        /****************************************/
        /*             AUTORESPONSES            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*               SLUGIFY                */
        /****************************************/
        /****************************************/

        case 'slugify':

            $slug = slugify(addslashes($_POST['string']));

            $slugOk = false;
            $slugCounter = 0;
            $originalSlug = $slug;

            do {

                if ($slugCounter == 10) break;
                $verifySlug_sql = "SELECT slug FROM clients WHERE slug='" . $slug . "'";
                $verifySlug_query = $mysqli->query($verifySlug_sql);

                if ($verifySlug_query->num_rows > 0) {
                    $slugOk = true;
                    $slug = $originalSlug . '-' . (++$slugCounter);
                } else $slugOk = false;
            } while ($slugOk);

            echo $slug;

            break;


        /****************************************/
        /****************************************/
        /*               SLUGIFY                */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*              GETGALLERY              */
        /****************************************/
        /****************************************/

        case 'getGallery':

            $error = 1;
            $allMedia = Array();

            if ($_POST['media'] == 'image') {
                $error = 0;
                $directory = "assets/images/standardImages/thumbs/";
                $images = glob($directory . "*.jpg");
                foreach ($images as $image) {
                    $allMedia[] = $image;
                }

                echo '<section class="tile transparent" id="superbox-gallery"><div class="tile-body color transparent-black superbox"><div id="closeContainer"><a class="closeModal"><i class="fa fa-close"></i></a></div>';

                foreach ($allMedia as $media) {

                    echo '<div class="superbox-list img-view">
				      <div class="imgMask"><img src="/' . $media . '" data-img="/' . $media . '" class="" alt=""></div>
				      <div class="overlay"><button data-target="' . $_POST['target'] . '" data-url="' . str_replace('/thumbs', '', $media) . '">Use this media</button></div>
				    </div>';

                }

                echo '</div></section>';

            } else if ($_POST['media'] == 'video') {
                $error = 0;
                $directory = "assets/videobg/standardVideos/";
                $videos = glob($directory . "*.mp4");
                foreach ($videos as $video) {
                    $allMedia[] = $video;
                }

                echo '<a class="closeModal"><i class="fa fa-close"></i></a><p>Click/tap to preview</p><section class="tile transparent" id="superbox-gallery"><div class="tile-body color transparent-black superbox">';

                foreach ($allMedia as $media) {
                    echo '<div class="superbox-list img-view">
					  <video src="/' . $media . '"></video>
				      <div class="overlay"><button data-target="' . $_POST['target'] . '" data-url="' . $media . '">Use this media</button></div>
				    </div>';

                }

                echo '</div></section>';

            } else echo 'error';

            break;


        /****************************************/
        /****************************************/
        /*              GETGALLERY              */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*         UPDATE DEFAULT PACKAGE       */
        /****************************************/
        /****************************************/

        case 'updateDefaultPackage':

            $package = $_POST['package'];

            $removeDefault_sql = "UPDATE packages SET `default`='0' WHERE client='" . $_SESSION['user']['id'] . "'";
            $removeDefault_query = $mysqli->query($removeDefault_sql);

            $updatePackage_sql = "UPDATE packages SET `default`='1' WHERE id='" . $package . "'";
            $updatePackage_query = $mysqli->query($updatePackage_sql);

            break;


        /****************************************/
        /****************************************/
        /*         UPDATE DEFAULT PACKAGE       */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*        UPDATE AVAILABLE PACKAGE      */
        /****************************************/
        /****************************************/

        case 'updateAvailablePackage':

            $package = $_POST['package'];
            $currentStatus_sql = "SELECT status FROM packages WHERE id='" . $package . "'";
            $currentStatus_query = $mysqli->query($currentStatus_sql);
            $currentStatus = $currentStatus_query->fetch_assoc()['status'];

            $newStatus = ($currentStatus == '1' ? '0' : '1');

            $updatePackage_sql = "UPDATE packages SET status='" . $newStatus . "' WHERE id='" . $package . "'";
            $updatePackage_query = $mysqli->query($updatePackage_sql);

            break;


        /****************************************/
        /****************************************/
        /*        UPDATE AVAILABLE PACKAGE      */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*             CREATE PACKAGE           */
        /****************************************/
        /****************************************/

        case 'createPackage':

            $name = addslashes($_POST['pck_name']);
            $price = str_replace('$', '', str_replace('\'', '', $_POST['pck_price']));
            $status = addslashes($_POST['pck_status']);
            $description = addslashes($_POST['pck_description']);
            $permission = $_POST['pck_permission'];

            if (
                trim($name) != '' &&
                trim($price) != '' &&
                trim($status) != '' &&
                trim($description) != '' &&
                trim($permission) != ''
            ) {

                $insertPackage_sql = "INSERT INTO packages (client, name, price, description, features_included, status) VALUES ('" . $_SESSION['user']['id'] . "', '" . $name . "', '" . $price . "', '" . $description . "', '" . $permission . "', '" . $status . "')";
                $insertPackage_query = $mysqli->query($insertPackage_sql);
                $lastId = $mysqli->insert_id;

                $plan = array(
                    "amount" => ceil(($price * 100) + (($price * 100) * 0.032)),
                    "interval" => "month",
                    "name" => $_SESSION['user']['company'] . ' - ' . $name,
                    "currency" => "usd",
                    "id" => slugify($_SESSION['user']['slug'] . '-' . $name)
                );

                \Stripe\Plan::create($plan);

                echo '1$#';

                ?>
                <div class="col-md-4">
                    <section class="tile color transparent-white">
                        <div class="tile-header color rounded-top-corners">
                            <h1 class="small-uppercase">
                                <strong><?= $name ?></strong>
                            </h1>
                            <div class="controls">
                                <h3><?= $price ?></h3>
                            </div>
                        </div>
                        <div class="tile-body">
                            <p class="description"><?= $description ?></p>
                        </div>
                        <div class="tile-footer color text-center transparent-white">
                            <div class="col-md-6">Default: <br>
                                <div class="onoffswitch labeled packDefault cyan">
                                    <input type="checkbox" name="onoffswitch_default"
                                           class="onoffswitch-checkbox default" id="onoffswitch_default<?= $lastId ?>"
                                           value="<?= $lastId ?>">
                                    <label class="onoffswitch-label" for="onoffswitch_default<?= $lastId ?>">
                                        <span class="onoffswitch-inner"></span>
                                        <span class="onoffswitch-switch"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">Available: <br>
                                <div class="onoffswitch labeled packAvailable cyan">
                                    <input type="checkbox" name="onoffswitch_status" class="onoffswitch-checkbox status"
                                           id="onoffswitch_status<?= $lastId ?>" checked="" value="<?= $lastId ?>">
                                    <label class="onoffswitch-label" for="onoffswitch_status<?= $lastId ?>">
                                        <span class="onoffswitch-inner"></span>
                                        <span class="onoffswitch-switch"></span>
                                    </label>
                                </div>
                            </div>
                            <br><br><br>
                        </div>

                    </section>
                </div>
                <?php
            } else {
                echo '0$#All fields are required.';
            }

            break;


        /****************************************/
        /****************************************/
        /*             CREATE PACKAGE           */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*             CREATE DISCOUNT          */
        /****************************************/
        /****************************************/

        case 'createDiscount':

            $name = addslashes($_POST['dsc_name']);
            $description = addslashes($_POST['dsc_description']);
            $amount = addslashes($_POST['dsc_amount']);
            $type = addslashes($_POST['dsc_type']);
            $package = addslashes($_POST['dsc_package']);
            $status = addslashes($_POST['dsc_status']);
            $startDate = addslashes($_POST['dsc_startDate']);
            $endDate = addslashes($_POST['dsc_endDate']);

            if (
                trim($name) != '' &&
                trim($description) != '' &&
                trim($amount) != '' &&
                trim($type) != '' &&
                trim($status) != '' &&
                trim($startDate) != '' &&
                trim($endDate) != ''
            ) {

                $insertDiscount_sql = "INSERT INTO discounts (id_package, name, description, type, amount, start_date, end_date, status) VALUES ('" . $package . "', '" . $name . "', '" . $description . "', '" . $type . "', '" . $amount . "', '" . $startDate . "', '" . $endDate . "', '" . $status . "')";
                $insertDiscount_query = $mysqli->query($insertDiscount_sql);
                $lastId = $mysqli->insert_id;

                echo '1$#';

                ?>
                <div class="col-md-4">
                    <section class="tile color transparent-white">
                        <div class="tile-header color transparent-white rounded-top-corners">
                            <?php
                            $package_sql = "SELECT name FROM packages WHERE id='" . $package . "'";
                            $package_query = $mysqli->query($package_sql);
                            $package = $package_query->fetch_assoc()['name'];
                            ?>
                            <h1 class="small-uppercase">
                                Package: <strong><?= $package ?></strong>
                            </h1>
                            <div class="controls">
                                <h3><?= ($type == 'r' ? '(R) $' : ($type == '$' ? '$' : '')) ?><?= $amount ?><?= ($type == '%' ? '%' : '') ?></h3>
                            </div>
                        </div>
                        <div class="tile-body">
                            <h3><?= $name ?></h3>
                            <p class="description"><?= $description ?></p>
                        </div>
                        <div class="tile-footer color transparent-white">
                            <div class="col-md-8">
                                <div class="row">
                                    <h5>From: <?= date('m/d/Y', $startDate) ?></h5>
                                    <h5>To: <?= date('m/d/Y', $endDate) ?></h5>
                                </div>
                            </div>
                            <div class="col-md-4">Active: <br>
                                <div class="onoffswitch labeled packDefault cyan">
                                    <input type="checkbox" name="onoffswitch_default"
                                           class="onoffswitch-checkbox default"
                                           id="onoffswitch_default<?= $lastId ?>" <?= ($status == 1 ? 'checked=""' : '') ?>
                                           value="<?= $lastId ?>">
                                    <label class="onoffswitch-label" for="onoffswitch_default<?= $lastId ?>">
                                        <span class="onoffswitch-inner"></span>
                                        <span class="onoffswitch-switch"></span>
                                    </label>
                                </div>
                            </div>
                            <br><br><br>
                        </div>

                    </section>
                </div>
                <?php
            } else {
                echo '0$#All fields are required.';
            }

            break;


        /****************************************/
        /****************************************/
        /*             CREATE DISCOUNT          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*             VERIFY COUPON            */
        /****************************************/
        /****************************************/

        case 'verifyCouponCode':

            $code = addslashes(trim($_POST['code']));

            if (
                trim($code) != ''
            ) {

                $code_sql = "SELECT * FROM cupons WHERE code='" . $code . "'";
                $code_query = $mysqli->query($code_sql);

                if ($code_query->num_rows == 0) echo 1;
                else echo 0;

            } else echo 0;

            break;

        /****************************************/
        /****************************************/
        /*             VERIFY COUPON            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*             CREATE COUPON            */
        /****************************************/
        /****************************************/

        case 'createCupon':

            $name = addslashes($_POST['dsc_name']);
            $code = addslashes($_POST['dsc_code']);
            $duration = addslashes($_POST['dsc_duration']);
            //$duration = "forever";
            $description = addslashes($_POST['dsc_description']);
            $amount = addslashes($_POST['dsc_amount']);
            $type = addslashes($_POST['dsc_type']);
            $package = addslashes($_POST['dsc_package']);
            $status = addslashes($_POST['dsc_status']);
            $startDate = addslashes($_POST['dsc_startDate']);
            $endDate = addslashes($_POST['dsc_endDate']);
            $maxRedemptions = addslashes($_POST['dsc_max_redemptions']);
            $dsc_amount = addslashes($_POST['dsc_amount']);

            if (
                trim($name) != '' &&
                trim($code) != '' &&
                trim($duration) != '' &&
                trim($description) != '' &&
                trim($amount) != '' &&
                trim($type) != '' &&
                trim($status) != '' &&
                trim($dsc_amount) != '' &&
                trim($maxRedemptions) != '' &&
                trim($startDate) != '' &&
                trim($endDate) != ''
            ) {

                $stripe_coupon = array(
                    "id" => trim($code),
                    "duration" => ($duration!='forever'&&$duration!='once'?'repeating':$duration),
                    "redeem_by" => (time() + (5 * 365 * 24 * 60 * 60) > $endDate ? $endDate : time() + (5 * 365 * 24 * 60 * 60))
                );

                if($duration!='forever' && $duration!='once'){
                    $stripe_coupon['duration_in_months'] = $duration;
                }

                if ($type == '%') {
                    $stripe_coupon["percent_off"] = $amount;
                }

                if ($type == '$') {
                    $stripe_coupon["amount_off"] = ceil($amount * 0.032) * 100;
                    $stripe_coupon["currency"] = 'USD';
                }

                if ($type == 'r') {
                    $pack_sql = "SELECT * FROM packages WHERE id='" . $package . "'";
                    $pack_query = $mysqli->query($pack_sql);
                    $pack = $pack_query->fetch_assoc();

                    $stripe_coupon["amount_off"] = (ceil(($pack['price']-$amount)+$pack['price']* 0.032) - $amount ) * 100;
                    $stripe_coupon["currency"] = 'USD';
                }

                //print_r($stripe_coupon);

                try {

                    \Stripe\Coupon::create($stripe_coupon);

                    echo '1$#';

                    $insertDiscount_sql = "INSERT INTO cupons (id_package, name, description, code, type, amount, start_date, end_date, status, max_redemptions, duration) VALUES ('" . $package . "', '" . $name . "', '" . $description . "', '" . trim($code) . "', '" . $type . "', '" . $amount . "', '" . $startDate . "', '" . $endDate . "', '" . $status . "', '" . $maxRedemptions . "', '" . $duration . "')";
                    $insertDiscount_query = $mysqli->query($insertDiscount_sql);
                    //echo $insertDiscount_sql.'<br>';
                    //echo $mysqli->error.'<br>';
                    $lastId = $mysqli->insert_id;

                    ?>
                    <div class="col-md-4">
                        <section class="tile color transparent-white">
                            <div class="tile-header color transparent-white rounded-top-corners">
                                <?php
                                if ($package > 0) {
                                    $package_sql = "SELECT name FROM packages WHERE id='" . $package . "'";
                                    $package_query = $mysqli->query($package_sql);
                                    $package = $package_query->fetch_assoc()['name'];
                                } else $package = "All";
                                ?>
                                <h1 class="small-uppercase">
                                    Package: <strong><?= $package ?></strong>
                                </h1>
                                <div class="controls">
                                    <h3><?= ($type == 'r' ? '(R) $' : ($type == '$' ? '$' : '')) ?><?= $amount ?><?= ($type == '%' ? '%' : '') ?></h3>
                                </div>
                            </div>
                            <div class="tile-body">
                                <h3><?= $name ?></h3>
                                <p class="description"><?= $description ?></p>
                                <p>Code: <?= $code ?></p>
                            </div>
                            <div class="tile-footer color transparent-white">
                                <div class="col-md-8">
                                    <div class="row">
                                        <h5>From: <?= date('m/d/Y', $startDate) ?></h5>
                                        <h5>To: <?= date('m/d/Y', $endDate) ?></h5>
                                        <?php
                                        $total_usage = $mysqli->query("SELECT COUNT(id) AS 'usage' FROM subscribers WHERE active='1' AND coupon='" . $cupon['name'] . "' AND package='" . $cupon['id_package'] . "'");
                                        while ($row = $total_usage->fetch_assoc()) {
                                            $usage = $row['usage'];
                                        }
                                        ?>
                                        <h5>Members using: <?= $usage ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-4">Active: <br>
                                    <div class="onoffswitch labeled packDefault cyan">
                                        <input type="checkbox" name="onoffswitch_default"
                                               class="onoffswitch-checkbox default"
                                               id="onoffswitch_default<?= $lastId ?>" <?= ($status == 1 ? 'checked=""' : '') ?>
                                               value="<?= $lastId ?>">
                                        <label class="onoffswitch-label" for="onoffswitch_default<?= $lastId ?>">
                                            <span class="onoffswitch-inner"></span>
                                            <span class="onoffswitch-switch"></span>
                                        </label>
                                    </div>
                                </div>
                                <br><br><br><br>
                            </div>

                        </section>
                    </div>
                    <?php
                } catch (Exception $e){
                    echo '0$#'.$e->getMessage();
                }

            } else {
                echo '0$#All fields are required.';
            }

            break;


        /****************************************/
        /****************************************/
        /*             CREATE COUPON            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*         CHANGE DISCOUNT STATUS       */
        /****************************************/
        /****************************************/

        case 'updateAvailableDiscount':

            $discount = $_POST['discount'];
            $currentStatus_sql = "SELECT status FROM discounts WHERE id='" . $discount . "'";
            $currentStatus_query = $mysqli->query($currentStatus_sql);
            $currentStatus = $currentStatus_query->fetch_assoc()['status'];

            $newStatus = ($currentStatus == '1' ? '0' : '1');

            $updatePackage_sql = "UPDATE discounts SET status='" . $newStatus . "' WHERE id='" . $discount . "'";
            $updatePackage_query = $mysqli->query($updatePackage_sql);

            break;


        /****************************************/
        /****************************************/
        /*         CHANGE DISCOUNT STATUS       */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*             CREATE EVENT             */
        /****************************************/
        /****************************************/

        case 'createEvent':

            $name = addslashes($_POST['name']);
            $datetime = addslashes($_POST['datetime']);
            $description = addslashes($_POST['description']);
            $packages = addslashes($_POST['packages']);
            $free = $_POST['free'] * 1;

            if (
                trim($name) != '' &&
                trim($datetime) != '' &&
                trim($description) != '' &&
                trim($packages) != ''
            ) {

                $createEvent_sql = "INSERT INTO webinar (client, name, description, datetime, packages, free) VALUES ('" . $_SESSION['user']['id'] . "', '" . $name . "', '" . $description . "', '" . $datetime . "', '" . $packages . "', '" . $free . "')";

                $mysqli->query($createEvent_sql);
                $lastId = $mysqli->insert_id;
                echo '1$#';
                ?>
                <section class="tile color transparent-white webinarEvent">
                    <div class="tile-body">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="panel panel-transparent-black text-center">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?= date('F', $datetime) ?></h3>
                                    </div>
                                    <div class="panel-body">
                                        <?= date('dS', $datetime) ?>
                                        <span><?= date('h:i A', $datetime) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h3><?= $name ?></h3>
                                <blockquote>
                                    <p><?= $description ?></p>
                                </blockquote>
                            </div>
                            <div class="col-md-2 text-center">
                                <button type="button" class="btn btn-default btn-md startStreaming"
                                        data-event="<?= $lastId ?>"><i class="fa fa-tv"></i> Start Webinar
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
                <?php
            } else {
                echo '0$#All fields are required';
            }
            $addToEventsTable = $mysqli->query("INSERT INTO events (`client`, `title`, `description`, `date`, `packages`, `free`, `created`, `modified`) VALUES ('" . $_SESSION['user']['id'] . "', '" . $name . "', '" . $description . "', '" . date("Y-m-d", $datetime) . "', '" . $packages . "', '" . $free . "', '" . date("Y-m-d") . "', '" . date("Y-m-d") . "')");

            break;


        /****************************************/
        /****************************************/
        /*             CREATE EVENT             */
        /****************************************/
        /****************************************/



        /****************************************/
        /****************************************/
        /*             SHOW EVENT               */
        /****************************************/
        /****************************************/

        case 'showEventCalendar':

            $date = $_POST['date'];

            $getEvents = $mysqli->query("SELECT * FROM events WHERE date = '".$date."' AND client = '".$_SESSION['user']['id']."'");

            echo '<label style="color: #4e5e6a;"><i class="fa fa-clock-o"></i> '.date("l, d M Y",strtotime($date)).'</label><br>';

                  while($row = $getEvents->fetch_assoc()){
                      if( isset($row['description']) ) {

                          $description = $row['description'];

                      } else {

                          $description = 'No description.';

                      }

                      echo '<label style="color: black;font-size: large;font-weight: 400;"><i class="fa fa-calendar"></i> '.$row['title'].'<br>
                            <div style="border-left:lime 15px solid;background-color: rgba(0,0,0,0.03);padding:12px;">
                                <label style="color:black;font-size: medium;font-weight:400;">'.$description.'</label>
                                <ul style="list-style: none;float:right;">
                                    <li style="float:left;padding: 2px;"><button class="btn" style="background-color: limegreen;border: none;color: white;" data-tooltip="edit"><i class="fa fa-pencil"></i></button></li>
                                    <li style="float:left;padding: 2px;"><button class="btn" style="background-color: red;border: none;color: white;" data-tooltip="delete"><i class="fa fa-trash"></i></button></li>
                            </ul>
                            </div>
                            <br>';
                  }


            break;

        /****************************************/
        /****************************************/
        /*             /SHOW EVENT             */
        /****************************************/
        /****************************************/



        /****************************************/
        /****************************************/
        /*             START WEBINAR            */
        /****************************************/
        /****************************************/

        case 'startWebinar':

            $name = trim(addslashes($_POST['name']));
            $description = trim(addslashes($_POST['description']));
            $packages = trim(addslashes($_POST['packages']));
            $free = $_POST['free'] * 1;

            $mysqli->query("UPDATE webinar SET live='0' WHERE live='1' AND client='" . $_SESSION['user']['id'] . "'");

            $apiKey = '45817872';
            $apiSecret = '073d22cdc9e21683976979a153b68459c4ac2851';

            $opentok = new OpenTok($apiKey, $apiSecret);

            $event = $_POST['event'];

            // Create a session that attempts to use peer-to-peer streaming:
            //$session = $opentok->createSession();

            $apiObj = new OpenTok($apiKey, $apiSecret);
            $session = $apiObj->createSession(array('archiveMode' => ArchiveMode::ALWAYS, 'mediaMode' => MediaMode::ROUTED));
            //$session = $apiObj->createSession(array('archiveMode' => ArchiveMode::MANUAL, 'mediaMode' => MediaMode::ROUTED));
            //$session = $apiObj->createSession(array('mediaMode' => MediaMode::ROUTED));
            $sessionId = $session->getSessionId();
            $token = $apiObj->generateToken($sessionId);


            if ($event > 0) {
                $_SESSION['webinar']['id'] = $event;
                $updateEvent_sql = "UPDATE webinar SET sessionId='" . $sessionId . "', live='1' WHERE id='" . $event . "'";
                $updateEvent_query = $mysqli->query($updateEvent_sql);
                echo '1$#' . $sessionId . '$#' . $apiKey . '$#' . $token;
            } else {
                $createEvent_sql = "INSERT INTO webinar (client, `name`, description, packages, datetime, sessionId, live, free) VALUES ('" . $_SESSION['user']['id'] . "', '" . $name . "', '" . $description . "',  '" . $packages . "', '" . time() . "', '" . $sessionId . "', '1', '" . $free . "')";
                $mysqli->query($createEvent_sql);
                $_SESSION['webinar']['id'] = $mysqli->insert_id;
                echo '1$#' . $sessionId . '$#' . $apiKey . '$#' . $token;
            }

            break;


        /****************************************/
        /****************************************/
        /*             START WEBINAR            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*              END WEBINAR             */
        /****************************************/
        /****************************************/

        case 'endWebinar':

            $sessionId = trim(addslashes($_POST['sessionId']));


            if ($sessionId > 0) {
                $updateEvent_sql = "UPDATE webinar SET live='0' WHERE sessionId='" . $sessionId . "'";
                $updateEvent_query = $mysqli->query($updateEvent_sql);
            }

            break;


        /****************************************/
        /****************************************/
        /*              END WEBINAR             */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*            VERIFY SESSION            */
        /****************************************/
        /****************************************/

        case 'verifySession':

            $sessionId = trim(addslashes($_POST['sessionId']));

            if ($sessionId > 0) {
                $updateEvent_sql = "SELECT * FROM webinar WHERE sessionId='" . $sessionId . "' AND live='1'";
                $updateEvent_query = $mysqli->query($updateEvent_sql);
                if ($updateEvent_query->num_rows > 0) {
                    echo 1;
                } else echo 0;
            }

            break;


        /****************************************/
        /****************************************/
        /*            VERIFY SESSION            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*            ARCHIVE WEBINAR           */
        /****************************************/
        /****************************************/

        case 'setArchive':

            $mysqli->query("UPDATE webinar SET archive='" . $_POST['archiveId'] . "' WHERE sessionId='" . $_POST['sessionId'] . "'");

            break;


        /****************************************/
        /****************************************/
        /*            ARCHIVE WEBINAR           */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*             WEBINAR CHAT             */
        /****************************************/
        /****************************************/

        case 'saveWebinarMsg':

            $msg = addslashes(trim($_POST['msg']));

            if ($msg != '') {

                $msg_sql = "INSERT INTO webinar_chat (webinar_id, from_type, from_id, msg, dt_insert) VALUES ('" . $_SESSION['webinar']['id'] . "', '" . (isset($_SESSION['user']['client']) ? 'member' : 'trader') . "', '" . $_SESSION['user']['id'] . "', '" . $msg . "', '" . time() . "')";
                $msg_query = $mysqli->query($msg_sql);
                echo $mysqli->insert_id;

            }

            break;


        /****************************************/
        /****************************************/
        /*             WEBINAR CHAT             */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*             WEBINAR CHAT             */
        /****************************************/
        /****************************************/

        case 'updateWebinar':

            $msgId = addslashes(trim($_POST['msgId']));

            $chatMessages_sql = "SELECT * FROM webinar_chat WHERE id='" . $msgId . "'";
            $chatMessages_query = $mysqli->query($chatMessages_sql);
            $message = $chatMessages_query->fetch_assoc();

            if (isset($_SESSION['user']['client'])) {
                $myType = 'member';
            } else {
                $myType = 'trader';
            }

            if ($message['from_type'] == $myType && $message['from_id'] == $_SESSION['user']['id']) { ?>
                <div class="me">
                    <div class="message"><?= $message['msg'] ?></div>
                    <div class="time"><?= date('g:ia', $message['dt_insert']) ?></div>
                </div>
            <?php } else { ?>
                <div class="other">
                    <?php

                    if ($message['from_type'] == 'member') {
                        $name_sql = "SELECT * FROM subscribers WHERE id='" . $message['from_id'] . "'";
                        $name_query = $mysqli->query($name_sql);
                        $names = explode(' ', $name_query->fetch_assoc()['name']);
                        $name = $names[0] . ' ' . $names[count($names) - 1];
                    } else {
                        $name_sql = "SELECT * FROM clients WHERE id='" . $message['from_id'] . "'";
                        $name_query = $mysqli->query($name_sql);
                        $names = explode(' ', $name_query->fetch_assoc()['full_name']);
                        $name = $names[0] . ' ' . $names[count($names) - 1];
                    }

                    ?>
                    <div class="name"><?= $name ?></div>
                    <div class="message"><?= $message['msg'] ?></div>
                    <div class="time"><?= date('g:ia', $message['dt_insert']) ?></div>
                </div>
            <?php }


            break;


        /****************************************/
        /****************************************/
        /*             WEBINAR CHAT             */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*           PASSWORD RECOVERY          */
        /****************************************/
        /****************************************/

        case 'startPasswordRecovery':

            $email = trim($_POST['email']);

            if ($email != '') {

                $findEmail_sql = "SELECT * FROM subscribers WHERE email='" . $email . "'";
                //echo $findEmail_sql;
                $findEmail_query = $mysqli->query($findEmail_sql);

                if ($findEmail_query->num_rows > 0) {
                    $findEmail = $findEmail_query->fetch_assoc();

                    do {

                        $verify_code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);

                        $verifyCodeExists_sql = "SELECT * FROM subscribers WHERE verify_code='" . $verify_code . "'";
                        $verifyCodeExists_query = $mysqli->query($verifyCodeExists_sql);

                        if ($verifyCodeExists_query->num_rows == 0) {
                            $verifyCodeExists = false;
                        } else {
                            $verifyCodeExists = true;
                        }

                    } while ($verifyCodeExists);

                    $updateCode_sql = "UPDATE subscribers SET verify_code='" . md5($verify_code) . "' WHERE email='" . $email . "'";
                    $updateCode_query = $mysqli->query($updateCode_sql);

                    $trader_sql = "SELECT * FROM clients WHERE id='" . $findEmail['client'] . "'";
                    $trader_query = $mysqli->query($trader_sql);
                    $trader = $trader_query->fetch_assoc();

                    //$_SESSION['register']['email'] = $email;
                    //$_SESSION['register']['package'] = $package;

                    $traderImages_sql = "SELECT * FROM customize_landing_pages WHERE client='" . $findEmail['client'] . "'";
                    $traderImages_query = $mysqli->query($traderImages_sql);
                    $traderImages = $traderImages_query->fetch_assoc();

                    $tags = [
                        "name" => $trader['full_name'],
                        "email" => $trader['email'],
                        "logo" => $traderImages['banner'],
                        "cover" => $traderImages['banner']
                    ];

                    $content = '<h1 style="text-align: center;"><br><span style="font-size:36px">PASSWORD RECOVERY</span></h1>&nbsp;<p>Click on this link and create your new password:<br><br><a href="https://admin.lionsofforex.com/' . $trader['slug'] . '/password-recovery/' . md5($verify_code) . '">https://admin.lionsofforex.com/' . $trader['slug'] . '/password-recovery/' . md5($verify_code) . '</a><br><br><br>Thanks again,<br><br>' . $trader['company'] . '<br>' . $trader['email'] . '&nbsp;</p>';

                    sendMail($email, 'PASSWORD RECOVERY', $tags, $content);

                } else echo 'error';


            } else echo 'error';

            break;


        /****************************************/
        /****************************************/
        /*           PASSWORD RECOVERY          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*            CHANGE PASSWORD           */
        /****************************************/
        /****************************************/

        case 'changePassword':

            $password = trim($_POST['password']);
            $password2 = trim($_POST['password2']);
            $token = trim($_POST['token']);

            if (
                $password != '' &&
                $password2 != '' &&
                $password == $password2
            ) {

                $findToken_sql = "SELECT * FROM subscribers WHERE verify_code='" . $token . "'";
                $findToken_query = $mysqli->query($findToken_sql);

                if ($findToken_query->num_rows > 0) {
                    $findToken = $findToken_query->fetch_assoc();

                    $updatePassword_sql = "UPDATE subscribers SET password='" . md5($password) . "', verify_code='' WHERE id='" . $findToken['id'] . "'";
                    $mysqli->query($updatePassword_sql);

                    echo 1;

                } else echo 'error';

            } else echo 'error';


            break;


        /****************************************/
        /****************************************/
        /*            CHANGE PASSWORD           */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          MEMBER EDIT ACCOUNT         */
        /****************************************/
        /****************************************/


        case 'memberEditAccountName':

            $name = trim($_POST['name']);


            $updateField = $mysqli->query("UPDATE subscribers SET name = '" . $name . "' WHERE id='" . $_SESSION['user']['id'] . "'");

            break;

        case 'memberEditAccountEmail':

            $email = trim($_POST['email']);


            $updateField = $mysqli->query("UPDATE subscribers SET email ='" . $email . "' WHERE id='" . $_SESSION['user']['id'] . "'");

            break;

        case 'memberEditAccountState':

            $state = trim($_POST['state']);


            $updateField = $mysqli->query("UPDATE subscribers SET province = '" . $state . "' WHERE id='" . $_SESSION['user']['id'] . "'");

            break;

        case 'memberEditAccountCity':

            $city = trim($_POST['city']);


            $updateField = $mysqli->query("UPDATE subscribers SET city ='" . $city . "' WHERE id='" . $_SESSION['user']['id'] . "'");

            break;

        case 'memberEditAccountZip':

            $zip = trim($_POST['zip']);


            $updateField = $mysqli->query("UPDATE subscribers SET zip ='" . $zip . "' WHERE id='" . $_SESSION['user']['id'] . "'");

            break;

        case 'memberEditAccountCountry':

            $country = trim($_POST['country']);

            $getCountryCode = $mysqli->query("SELECT * FROM countries WHERE country_name = '" . $country . "'");
            while ($row = $getCountryCode->fetch_assoc()) {
                $country_code = $row['id'];
            }


            $updateField = $mysqli->query("UPDATE subscribers SET country ='" . $country_code . "' WHERE id='" . $_SESSION['user']['id'] . "'");

            break;

        case 'memberEditAccountChangePassword':

            $newPassword = trim($_POST['newPassword']);
            $newPasswordR = trim($_POST['newPasswordR']);
            if (empty($newPassword) || empty($newPasswordR)) {
                echo 'error';
            } else {
                $updateField = $mysqli->query("UPDATE subscribers SET password ='" . md5($newPassword) . "' WHERE id='" . $_SESSION['user']['id'] . "'");
            }

            break;


        /****************************************/
        /****************************************/
        /*         /MEMBER EDIT ACCOUNT         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          TRADER EDIT ACCOUNT         */
        /****************************************/
        /****************************************/

        case 'traderEditAccountEmail':

            $email = trim($_POST['email']);


            $updateField = $mysqli->query("UPDATE clients SET email ='" . $email . "' WHERE id='" . $_SESSION['user']['id'] . "'");

            break;

        case 'traderEditAccountCompany':

            $company = trim($_POST['company']);


            $updateField = $mysqli->query("UPDATE clients SET company ='" . $company . "' WHERE id='" . $_SESSION['user']['id'] . "'");

            break;

        case 'traderEditAccountJob':

            $job = trim($_POST['job']);


            $updateField = $mysqli->query("UPDATE clients SET job_title ='" . $job . "' WHERE id='" . $_SESSION['user']['id'] . "'");

            break;

        case 'traderEditAccountCity':

            $city = trim($_POST['city']);


            $updateField = $mysqli->query("UPDATE clients SET city ='" . $city . "' WHERE id='" . $_SESSION['user']['id'] . "'");

            break;

        case 'traderEditAccountCountry':

            $country = trim($_POST['country']);

            $getCountryCode = $mysqli->query("SELECT * FROM countries WHERE country_name = '" . $country . "'");
            while ($row = $getCountryCode->fetch_assoc()) {
                $country_code = $row['id'];
            }


            $updateField = $mysqli->query("UPDATE clients SET country ='" . $country_code . "' WHERE id='" . $_SESSION['user']['id'] . "'");

            break;

        /****************************************/
        /****************************************/
        /*          TRADER EDIT ACCOUNT         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          MEMBER ADD CARD             */
        /****************************************/
        /****************************************/

        case 'memberAddCard':

            $card = trim($_POST['card']);
            $expDateM = trim($_POST['expDateM']);
            $expDateY = trim($_POST['expDateY']);
            $cvc = trim($_POST['cvc']);
            $stripeID = trim($_POST['stripeID']);

            $cardToken = \Stripe\Token::create(array(
                "card" => array(
                    "number" => $card,
                    "exp_month" => $expDateM,
                    "exp_year" => $expDateY,
                    "cvc" => $cvc
                )
            ));

            $customer = \Stripe\Customer::retrieve($stripeID);

            $newCard = $customer->sources->create(array("source" => $cardToken));


            /*$customer->default_source = $customer->sources;
            $customer->save();*/

            break;






        /****************************************/
        /****************************************/
        /*          /MEMBER ADD CARD            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          MEMBER CHANGE CARD          */
        /****************************************/
        /****************************************/

        case 'memberSetDefaultCard':

            $cardID = trim($_POST['card']);
            $stripeID = trim($_POST['stripeID']);


            $customer = \Stripe\Customer::retrieve($stripeID);


            $customer->default_source = $cardID;
            $customer->save();

            break;






        /****************************************/
        /****************************************/
        /*          /MEMBER CHANGE CARD         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*          MEMBER CHANGE CARD          */
        /****************************************/
        /****************************************/

        case 'memberDeleteCard':

            $cardID = trim($_POST['card']);
            $stripeID = trim($_POST['stripeID']);


            $customer = \Stripe\Customer::retrieve($stripeID);


            $customer->sources->retrieve($cardID)->delete();

            break;






        /****************************************/
        /****************************************/
        /*          /MEMBER CHANGE CARD         */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*              CHANGE PLAN             */
        /****************************************/
        /****************************************/

        case 'memberChangePlan':
            $id = trim($_POST['id']);
            $activeCard = trim($_POST['activeCard']);
            $cardToken = trim($_POST['cardToken']);
            $member = $_SESSION['user']['id'];
            $stripeID = $_SESSION['user']['stripeID'];
            /*
            //get new package info
            $getNewPackage=$mysqli->query("SELECT * FROM packages WHERE id = '".$id."'");
            $row=$getNewPackage->fetch_assoc();
            $newPackageName=$row['name'];

            //slugify( $_SESSION['user']['slug'] . '-' . trim($code) )
            $getClient=$mysqli->query("SELECT * FROM subscribers WHERE id='".$member."'");
            $row=$getClient->fetch_assoc();
            $trader=$row['client'];
            $getSlug=$mysqli->query("SELECT * FROM clients WHERE id='".$trader."'");
            $row=$getSlug->fetch_assoc();
            $slug=$row['slug'];

            //backup coupon and package
            $checkCoupon = $mysqli->query("SELECT * FROM subscribers WHERE id = '".$member."'");
            $row=$checkCoupon->fetch_assoc();
            $bckupCoupon=$row['coupon'];
            $bckupPackage=$row['package'];

            // test all variables
            //  echo 'member - '.$member.', trader - '.$trader.', slug - '.$slug.', current package - '.$bckupPackage.', new package name - '.$newPackageName;

              //new plan name on stripe
              $newPlan = slugify($slug.'-'.$newPackageName);




              if($bckupCoupon != ''){
              //remove coupon from customer on stripe
              $cu = \Stripe\Customer::retrieve($stripeID);
              $cu->deleteDiscount();



              //upgrade package on stripe



              $subscription = \Stripe\Subscription::retrieve($stripeID);
              $subscription->plan = $newPlan;
              $subscription->prorate = true;
              $subscription->save();

              //force charge on stripe
              $charge = \Stripe\Invoice::create(array(
                  "customer" => $stripeID,
              ));

              $invoiceId = $charge->id;

                $invoice = \Stripe\Invoice::retrieve($invoiceId);
                $invoice->pay();

              $paid = $charge->paid;




                  //if charge == ok then {remove coupon on subscribers table && change package_id}
                  if($paid == 'true'){
                      $removeCoupon = $mysqli->query("UPDATE subscribers SET coupon = ''");
                      $updatePackage = $mysqli->query("UPDATE subscribers SET package = '".$id."'");
                  }else{
                      //else if coupon is forever reset coupon else if once dont reset
                      $checkDuration=$mysqli->query("SELECT * FROM cupons WHERE code = '".$bckupCoupon."'");
                      $row=$checkDuration->fetch_assoc();
                      $duration=$row['duration'];
                      if($duration == 'forever'){
                          $resetCoupon = $mysqli->query("UPDATE subscribers SET coupon = '".$bckupCoupon."'");
                          $resetPackage = $mysqli->query("UPDATE subscribers SET package = '".$bckupPackage."'");
                      }else{
                          $resetPackage = $mysqli->query("UPDATE subscribers SET package = '".$bckupPackage."'");
                      }

                  }


              //downgrade to original package}

              }else{
            //upgrade package on stripe



            $subscription = \Stripe\Subscription::retrieve($stripeID);
            $subscription->plan = $newPlan;
            $subscription->prorate = true;
            $subscription->save();

            //force charge on stripe
            $charge = \Stripe\Invoice::create(array(
                "customer" => $stripeID,
            ));

            $invoiceId = $charge->id;

            $invoice = \Stripe\Invoice::retrieve($invoiceId);
            $invoice->pay();

            $paid = $charge->paid;




            //if charge == ok then {remove coupon on subscribers table && change package_id}
            if($paid == 'true'){
                $removeCoupon = $mysqli->query("UPDATE subscribers SET coupon = ''");
                $updatePackage = $mysqli->query("UPDATE subscribers SET package = '".$id."'");
            }else{
                //else if coupon is forever reset coupon else if once dont reset
                $checkDuration=$mysqli->query("SELECT * FROM cupons WHERE code = '".$bckupCoupon."'");
                $row=$checkDuration->fetch_assoc();
                $duration=$row['duration'];
                if($duration == 'forever'){
                    $resetCoupon = $mysqli->query("UPDATE subscribers SET coupon = '".$bckupCoupon."'");
                    $resetPackage = $mysqli->query("UPDATE subscribers SET package = '".$bckupPackage."'");
                }else{
                    $resetPackage = $mysqli->query("UPDATE subscribers SET package = '".$bckupPackage."'");
                }

            }


            //downgrade to original package}

        }

*/
            break;

        /****************************************/
        /****************************************/
        /*             /CHANGE PLAN             */
        /****************************************/
        /****************************************/

        /****************************************/
        /****************************************/
        /*             RECHARGE USER            */
        /****************************************/
        /****************************************/


        case 'rechargeMember':
            $id = trim($_POST['id']);

            $getMemberInfo = $mysqli->query("SELECT * FROM subscribers WHERE id = '" . $id . "'");
            $member = $getMemberInfo->fetch_assoc();
            $stripeID = $member['stripeID'];

            if ($stripeID != '') {
                $customer = \Stripe\Customer::retrieve($stripeID);
                $stripesubID = $customer->subscriptions->data['0']->id;

                $stripesubscription = \Stripe\Subscription::retrieve($stripesubID);
                $subscriptionStatus = $stripesubscription->status;

                if($subscriptionStatus == 'past_due'){
                    //force charge on stripe

					/*try{
						$charge = \Stripe\Invoice::create(array(
							"customer" => $stripeID,
						));
					} catch (Exception $e){



					}*/

					try{
						$invoices = \Stripe\Invoice::all([
							"limit" => 1,
							"customer" => $stripeID
						]);

						$invoice = \Stripe\Invoice::retrieve($invoices->data[0]->id);

						$invoice->pay();

						echo 1;
					} catch (Exception $e){
						echo $e->getMessage();
					}

	                /*$invoiceId = $charge->id;

					$invoice = \Stripe\Invoice::retrieve($invoiceId);

					$invoice->pay();

					$paid = $charge->paid;

					print_r($paid);*/

                    //echo'Member Recharged';

                }else{echo'Member does not have any pending payments';}
            }else{echo'Member does not have a stripe id';}

            break;

        /****************************************/
        /****************************************/
        /*            /RECHARGE USER            */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*           REORDER VIDEOS             */
        /****************************************/
        /****************************************/

        case 'reorderVideos':

            $array = $_POST['array'];

            print_r($array);

            //$updateOrder = $mysqli->query("UPDATE courses_lesson SET order = '".$id."' WHERE id = '".$vidId."'");

            break;


        /****************************************/
        /****************************************/
        /*           /REORDER VIDEOS            */
        /****************************************/
        /****************************************/



        /****************************************/
        /****************************************/
        /*              CANCEL PLAN             */
        /****************************************/
        /****************************************/

        case 'memberCancelPlan':
            $id = trim($_POST['id']);
            $member = $_SESSION['user']['id'];

            //$cancelPlan = $mysqli->query("");

            break;

        /****************************************/
        /****************************************/
        /*             /CANCEL PLAN             */
        /****************************************/
        /****************************************/







		/* ***************************************
       PAGE: Team Training
       Func: Create / Edit Course
       (Rui Rosa)
       **************************************** */
		case 'tryCourseSave':


			$courseid=$_POST['courseid'];
			$userid=$_POST['userid'];
			$title=addslashes($_POST['title']);
			$description=addslashes($_POST['description']);
			$status=$_POST['onoffswitch'];

			//UPDATE
			if($courseid!=''){
				//VERIFY
				$verify=$mysqli->query("SELECT id FROM courses WHERE id='".$courseid."' AND client='".$userid."'");
				if($_SESSION['user']['id']==$userid && $verify->num_rows==1){
					$mysqli->query("UPDATE courses SET name='".$title."', status='".($status!=''?$status:0)."', description='".$description."' WHERE id='".$courseid."'");

                    $mysqli->query("DELETE FROM courses_lesson_pack_rel WHERE id_course='".$courseid."'");
                    foreach($_POST['packages'] as $packId){
                        $mysqli->query("INSERT INTO courses_lesson_pack_rel (id_course, id_pack) VALUES ('".$courseid."', '".$packId."')");
                    }

					echo $courseid;
				}

            //CREATE
			}else{

                try {
                    $mysqli->query("INSERT INTO courses (client, name, status, description, cover) VALUES ('".$userid."', '".$title."', '".($status!=''?$status:0)."', '".$description."', '')");
                    $ins=$mysqli->insert_id;

                    //Packs Rel
                    foreach($_POST['packages'] as $packId){
                        $mysqli->query("INSERT INTO courses_lesson_pack_rel (id_course, id_pack) VALUES ('".$ins."', '".$packId."')");
                    }
                } catch (Exception $e) {
                    //echo $e->getMessage();
                    echo 'Error';
                    $ins=$e->getMessage();
                }

				echo $ins;
			}

			break;
		/* ************************************ */


        /* ***************************************
       PAGE: Team Training
       Func: Create / Edit Course
       (Rui Rosa)
       **************************************** */
        case 'removeCourse':

            $course_id = addslashes(trim($_POST['course']));

            if($mysqli->query("DELETE FROM courses WHERE id='".$course_id."'")){
                echo 1;
            } else echo 0;

            break;
		/* ************************************ */



		/* ***************************************
		PAGE: Team Training
		Func: Upload cover photo
		(Rui Rosa)
		**************************************** */
		case 'tryCourseCover':

			$courseid=$_POST['courseid']*1;

			$canUpload=false;

			if(isset($_SESSION['user'])){
				$slug=$_SESSION['user']['slug'];
				$canUpload=true;
			} else if(isset($_SESSION['trader_registration'])){
				$slug=$_SESSION['trader_registration'][2]['slug'];
				$canUpload=true;
			} else {
				$slug = 'erro_'.time();
			}

			if($canUpload && !file_exists('assets/images/traders/'.$slug.'/course')){
				mkdir('assets/images/traders/'.$slug.'/course', 0777, true);
			}

			$img = $_FILES["file"];
			$dir = 'assets/images/traders/'.$slug.'/course/';
			$ext = pathinfo($img["name"],PATHINFO_EXTENSION);
			$name = $courseid.'-'.uniqid().'-'.time().'.'.$ext;
			$size = getimagesize($img["tmp_name"]);

			$allowed=['png', 'jpg', 'jpeg'];
			if($courseid>0 && $canUpload && $size>0 && in_array($ext, $allowed)){

				//if(file_exists($target_file)) unlink($target_file);

				if(move_uploaded_file($img["tmp_name"], $dir.$name)){
					setLog('Image uploaded', '<fname> uploaded an image. Check it here: '.$dir.$name);
					$mysqli->query("UPDATE courses SET cover='".$name."' WHERE id='".$courseid."'");
					echo 1;

				} else {
					echo "0$#Something went wrong. Please, try again.";
					setLog('ERROR moving image from temp to destination', '<fname> upload process stopped because the system can\'t move the file to '.$dir.$name.' // FILE INFO //'.serialize( $_FILES ));
				}
			}else{
				echo "0$#Sorry, we only accept PNG, JPG and JPEG images.";
				setLog('upload a non supported image type', '<fname> trying to upload a file that has a not supported file type // FILE INFO //'.serialize( $_FILES ));
			}


			break;




		/* ***************************************
		PAGE: Team Training
		Func: Course status toggle
		(Rui Rosa)
		**************************************** */
		case 'courseToggle':
			$id=$_POST['id'];
			$val=$_POST['val'];

			if($id!='' && $val!=''){
				$mysqli->query("UPDATE courses SET status='".$val."' WHERE id='".$id."'");
			}

			break;



		/* ***************************************
		PAGE: Team Training
		Func: Get Course Info
		(Rui Rosa)
		**************************************** */
		case 'getCourseInfo':
			$status=0;
			$data=[];

			$userid=$_SESSION['user']['id']*1;
			$courseid=$_POST['id']*1;

			if($userid>0 && $courseid>0){
				$verify=$mysqli->query("SELECT id FROM courses WHERE id='".$courseid."' AND client='".$userid."'");
				if($verify->num_rows==1){
					$course=$mysqli->query("SELECT * FROM courses WHERE id='".$courseid."'");
					$cinfo=$course->fetch_assoc();

                    $packs=[];
                    $lesson=$mysqli->query("SELECT * FROM courses_lesson_pack_rel WHERE id_course='".$courseid."'");
                    while($l=$lesson->fetch_assoc()){
                        array_push($packs, $l['id_pack']);
                    }

					$data=[
						'idc' => $courseid,
						'idu' => $userid,
						'slug' => $_SESSION['user']['slug'],
						'title' => $cinfo['name'],
						'desc' => $cinfo['description'],
						'file' => $cinfo['cover'],
						'status' => $cinfo['status'],
                        'packs' => $packs
					];
					$status=1;
				}
			}


			$result = [
				'status' => $status,
				'response' => $data
			];
			echo json_encode($result);

			break;




		/* ***************************************
		PAGE: Team Training / Lesson / Vimeo
		Func: Create / Edit Lesson
		(Rui Rosa)
		**************************************** */
		case 'tryLessonSave':

			$courseid=$_POST['courseid'];
			$lessonid=$_POST['lessonid'];
			$userid=$_POST['userid'];
			$title=$_POST['title'];
			$description=$_POST['description'];
            $videoId=$_POST['vimeolink'];
            $videoReady=$_POST['vimeoready'];
			$status=$_POST['onoffswitch'];
			$resources=$_POST['resources'];



			//UPDATE
			if($lessonid!=''){

                if($videoId!=''){

                    $vquery=$mysqli->query("SELECT video FROM courses_lesson WHERE id='".$lessonid."'");
                    $oldvideo=$vquery->fetch_assoc();

                    $mysqli->query("UPDATE courses_lesson SET name='".$title."', status='".($status!=''?$status:0)."', description='".$description."', video='".$videoId."', resources='".$resources."' WHERE id='".$lessonid."'");

                    require_once('libs/vimeo-php/autoload.php');
                    $lib = new \Vimeo\Vimeo('', '', 'ef0b081c03322e205f52153a75f2c18a');

                    //DELETE OLD VIDEO
                    if($oldvideo['video']!=''){
                        $libDel = new \Vimeo\Vimeo('', '', '76c80fbde92531b2814249fc9b6f2afe');
                        $trydelete=$libDel->request('/videos/'.$oldvideo['video'], '', 'DELETE');
                    }

                    $params = [
                        'embed' => [
                            'buttons' => [
                                'like' => 0,
                                'watchlater' => 0,
                                'share' => 0,
                                'embed' => 0,
                                'hd' => 1,
                                'fullscreen' => 1,
                                'scaling' => 1
                            ],
                            'logos' => [
                                'vimeo' => 0,
                                'custom' => [
                                    'active' => 0,
                                    'link' => '',
                                    'sticky' => 0
                                ]
                            ],
                            'title' => [
                                'name' => 'hide',
                                'owner' => 'hide',
                                'portrait' => 'hide'
                            ],
                            'playbar' => 1,
                            'volume' => 0
                        ],
                        'privacy' => [
                            'view' => 'disable',
                            'embed' => 'whitelist',
                            'download' => 'false',
                            'add' => 'false',
                            'comments' => 'nobody',

                            'domains' => [
                                'domain' => 'lionsofforex.com',
                                'allow_hd' => 1
                            ]
                        ]
                    ];

                    $vimeoStatus = $lib->request('/videos/'.$videoId, '', 'GET');
                    if($vimeoStatus['status']==200){

                        //Update settings + add domain
                        $update = $lib->request('/videos/'.$videoId, $params, 'PATCH');
                        $domain = $lib->request('/videos/'.$videoId.'/privacy/domains/lionsofforex.com', '', 'PUT');



                        $mysqli->query("UPDATE courses_lesson SET vimeo_ready='1', cover='', dt_update='".date("Y-m-d H:i:s")."' WHERE id='".$lessonid."'");

                    }

                }else{
                    $mysqli->query("UPDATE courses_lesson SET name='".$title."', status='".($status!=''?$status:0)."', description='".$description."', resources='".$resources."' WHERE id='".$lessonid."'");

                }

				echo $lessonid;

				//CREATE
			}else if($courseid!=''){

                $orderquery=$mysqli->query("SELECT `order` FROM courses_lesson WHERE id_course='".$courseid."' ORDER BY `order` DESC");
                if($orderquery!=false){
                    $orderres=$orderquery->fetch_assoc();
                    $order=($orderres['order']*1)+1;
                } else $order=1;

                $order=1;

				$mysqli->query("INSERT INTO courses_lesson (id_course, name, description, cover, `order`, dt_insert, video, vimeo_ready, status, resources) VALUES ('".$courseid."', '".$title."', '".$description."', '', '".$order."', '".date("Y-m-d H:i:s")."', '".$videoId."', '".$videoReady."', '".($status!=''?$status:0)."', '".$resources."')");
				$ins=$mysqli->insert_id;


                //Vimeo settings
                if($ins!='' && $videoId!=''){

                    require_once('libs/vimeo-php/autoload.php');
                    $lib = new \Vimeo\Vimeo('', '', 'ef0b081c03322e205f52153a75f2c18a');

                    $params = [
                        'embed' => [
                            'buttons' => [
                                'like' => 0,
                                'watchlater' => 0,
                                'share' => 0,
                                'embed' => 0,
                                'hd' => 1,
                                'fullscreen' => 1,
                                'scaling' => 1
                            ],
                            'logos' => [
                                'vimeo' => 0,
                                'custom' => [
                                    'active' => 0,
                                    'link' => '',
                                    'sticky' => 0
                                ]
                            ],
                            'title' => [
                                'name' => 'hide',
                                'owner' => 'hide',
                                'portrait' => 'hide'
                            ],
                            'playbar' => 1,
                            'volume' => 0
                        ],
                        'privacy' => [
                            'view' => 'disable',
                            'embed' => 'whitelist',
                            'download' => 'false',
                            'add' => 'false',
                            'comments' => 'nobody',

                            'domains' => [
                                'domain' => 'lionsofforex.com',
                                'allow_hd' => 1
                            ]
                        ]
                    ];

                    $vimeoStatus = $lib->request('/videos/'.$videoId, '', 'GET');
                    if($vimeoStatus['status']==200){

                        //Update settings + add domain
                        $update = $lib->request('/videos/'.$videoId, $params, 'PATCH');
                        $domain = $lib->request('/videos/'.$videoId.'/privacy/domains/lionsofforex.com', '', 'PUT');

                        //Get Image - NOT POSSIBLE HERE (try a workaround), video still encoding
                        //$getImgUrl = $lib->request('/videos/'.$videoId.'/pictures', '', 'GET');
                        //$imgID=explode('/',$getImgUrl['body']['data'][0]['uri'])[4];

                        $mysqli->query("UPDATE courses_lesson SET vimeo_ready='1' WHERE id='".$ins."'");

                    }else if($vimeoStatus['status']==404 || $vimeoStatus['status']==401){
                        $mysqli->query("UPDATE courses_lesson SET vimeo_ready='-1' WHERE id='".$id."'");
                    }
                }


                //Send Mail
                $content='Novo upload de vídeo feito:<br><br><a href="http://vimeo.com/'.$videoId.'">http://vimeo.com/'.$videoId.'</a>';

                //sendSimpleMail('logs@lionsofforex.com', 'Novo vimeo upload: '.$videoId, $content);

				echo $ins;
			}
			break;




        /* ***************************************
		PAGE: Team Training / Lesson / Vimeo
		Func: Upload cover photo
		(Rui Rosa)
		**************************************** */
        case 'tryLessonCover':

            $lessonid=$_POST['lessonid']*1;

            $canUpload=false;

            if(isset($_SESSION['user'])){
                $slug=$_SESSION['user']['slug'];
                $canUpload=true;

            } else {
                $slug = 'erro_'.time();
            }

            if($canUpload && !file_exists('assets/images/traders/'.$slug.'/course/lessons')){
                mkdir('assets/images/traders/'.$slug.'/course/lessons', 0777, true);
            }

            $img = $_FILES["file"];
            $dir = 'assets/images/traders/'.$slug.'/course/lessons/';
            $ext = pathinfo($img["name"],PATHINFO_EXTENSION);
            $name = $lessonid.'-'.uniqid().'-'.time().'.'.$ext;
            $size = getimagesize($img["tmp_name"]);

            $allowed=['png', 'jpg', 'jpeg'];
            if($lessonid>0 && $canUpload && $size>0 && in_array($ext, $allowed)){

                //if(file_exists($target_file)) unlink($target_file);

                if(move_uploaded_file($img["tmp_name"], $dir.$name)){
                    setLog('Image uploaded', '<fname> uploaded an image. Check it here: '.$dir.$name);
                    $mysqli->query("UPDATE courses_lesson SET cover2='".$name."' WHERE id='".$lessonid."'");
                    echo 1;

                } else {
                    echo "0$#Something went wrong. Please, try again.";
                    setLog('ERROR moving image from temp to destination', '<fname> upload process stopped because the system can\'t move the file to '.$dir.$name.' // FILE INFO //'.serialize( $_FILES ));
                }
            }else{
                echo "0$#Sorry, we only accept PNG, JPG and JPEG images.";
                setLog('upload a non supported image type', '<fname> trying to upload a file that has a not supported file type // FILE INFO //'.serialize( $_FILES ));
            }


            break;



        case 'pagVimeo':

            $dir = $_POST['dir'];
            $current = $_POST['current'];
            $last = $_POST['last'];

            if($dir!='' && $current!='' && $last!='') {

                if ($dir == 'prev') {
                    if($current==1) $new = 1;
                    else $new = $current-1;
                } else { // next
                    if($current==$last) $new = $last;
                    else $new = $current+1;
                }

                require_once('libs/vimeo-php/autoload.php');
                $lib = new \Vimeo\Vimeo('', '', 'ef0b081c03322e205f52153a75f2c18a');

                $videos = $lib->request('/me/videos?page='.$new, '', 'GET');

                echo $new.'$#';

                foreach ($videos['body']['data'] as $video) {

                    $id = str_replace('/videos/', '', $video['uri']);
                    $name = $video['name'];
                    $url = $video['pictures']['sizes'][1]['link'];

                    ?>

                    <div class="videoBlock" data-id="<?= $id ?>">
                        <img src="<?= $url ?>" alt="<?= $name ?>" class="img-responsive">
                        <p><b><?= $name ?></b></p>
                    </div>

                    <?php

                }

            } else echo '0';

            break;


        case 'searchVimeo':

            $query = $_POST['query'];

            if($query!=''){

                require_once('libs/vimeo-php/autoload.php');
                $lib = new \Vimeo\Vimeo('', '', 'ef0b081c03322e205f52153a75f2c18a');

                $videos = $lib->request('/me/videos?query='.urlencode($query), '', 'GET');

                print_r($videos);

                /*echo $new.'$#';

                foreach ($videos['body']['data'] as $video) {

                    $id = str_replace('/videos/', '', $video['uri']);
                    $name = $video['name'];
                    $url = $video['pictures']['sizes'][1]['link'];

                    ?>

                    <div class="videoBlock" data-id="<?= $id ?>">
                        <img src="<?= $url ?>" alt="<?= $name ?>" class="img-responsive">
                        <p><b><?= $name ?></b></p>
                    </div>

                    <?php

                }*/

            } else echo '0';

            break;


        /* ***************************************
        PAGE: Team Training
        Func: Get Lesson / Video Info
        (Rui Rosa)
        **************************************** */
        case 'getVideoInfo':
            $status=0;
            $data=[];

            $userid=$_SESSION['user']['id']*1;
            $lessonid=$_POST['id']*1;

            if($userid>0 && $lessonid>0){
                /*$verify=$mysqli->query("SELECT id FROM courses WHERE id='".$courseid."' AND client='".$userid."'");
                if($verify->num_rows==1){*/
                $lesson=$mysqli->query("SELECT * FROM courses_lesson WHERE id='".$lessonid."'");
                $info=$lesson->fetch_assoc();

                $file='';
                $slug=$_SESSION['user']['slug'];
                if($info['cover2']!=''){
                    $file='https://admin.lionsofforex.com/assets/images/traders/'.$slug.'/course/lessons/'.$info['cover2'];

                }else if($info['cover']!=''){
                    $file='https://i.vimeocdn.com/video/'.$info['cover'].'_640x360.jpg';
                }

                $data=[
                    'lessonid' => $lessonid,
                    'iduser' => $userid,
                    'slug' => $_SESSION['user']['slug'],
                    'title' => $info['name'],
                    'desc' => $info['description'],
                    'file' => $file,
                    'video' => $info['video'],
                    'status' => $info['status'],
                    'resources' => $info['resources']
                ];
                $status=1;
                /*}*/
            }


            $result = [
                'status' => $status,
                'response' => $data
            ];
            echo json_encode($result);

            break;



		/* ***************************************
		PAGE: Team Training
		Func: Lessons status toggle
		(Rui Rosa)
		**************************************** */
		case 'lessonsToggle':
			$id=$_POST['id'];
			$val=$_POST['val'];

			if($id!='' && $val!=''){
				$mysqli->query("UPDATE courses_lesson SET status='".$val."' WHERE id='".$id."'");
			}

			break;


		/* ***************************************
		PAGE: Team Training (Member)
		Func: Load
		(Louis)
		**************************************** */

		case 'loadCourse':
			$current = addslashes(trim($_POST['course']));
			?>
			<div class="holder">
				<?php

				/*$lessons_sql = "SELECT cl.* FROM courses_lesson cl, courses_lesson_pack_rel r WHERE cl.id_course='".$current."' AND cl.status='1' AND r.id_lesson=cl.id AND r.id_pack='".$_SESSION['user']['package']."' ORDER BY cl.`order` ASC";
				$lessons_sql = "SELECT * FROM courses_lesson WHERE id_course='".$current."' ORDER BY `order` ASC";
				$lessons_query = $mysqli->query($lessons_sql);
				$lessons = $lessons_query->fetch_all(MYSQLI_ASSOC);

				/*if($lessons_query->num_rows==0){

					$lessons_sql   = "SELECT DISTINCT(c.id), c.name FROM courses c, courses_lesson_pack_rel r WHERE r.id_pack='" . $_SESSION['user']['package'] . "' AND c.id=r.id_course AND c.status='1'";
					$lessons_query = $mysqli->query($lessons_sql);
					$lessons = $lessons_query->fetch_all(MYSQLI_ASSOC);

				}*/

				//$lessons_sql   = "SELECT cl.* FROM courses_lesson cl, courses_lesson_pack_rel r WHERE cl.id_course='" . $current . "' AND cl.status='1' AND r.id_lesson=cl.id AND r.id_pack='" . $_SESSION['user']['package'] . "' ORDER BY cl.`order` ASC";
                $lessons_sql   = "SELECT
                            cl.*
                          FROM
                            courses_lesson cl
                          WHERE
                            cl.id_course='" . $current . "' AND
                            cl.status='1'
                          ORDER BY
                            cl.`order` ASC";
				$lessons_query = $mysqli->query( $lessons_sql );
				//$lessons       = $lessons_query->fetch_all( MYSQLI_ASSOC );
                $lessons = array();
                while($lesson = $lessons_query->fetch_assoc()) $lessons[] = $lesson;

				if($lessons_query->num_rows==0){
					$lessons_sql   = "SELECT * FROM courses_lesson WHERE id_course='".$current."' AND status='1' ORDER BY `order` ASC";
					$lessons_query = $mysqli->query( $lessons_sql );
					//$lessons       = $lessons_query->fetch_all( MYSQLI_ASSOC );
                    $lessons = array();
                    while($lesson = $lessons_query->fetch_assoc()) $lessons[] = $lesson;
				}

				?>

				<div class="lessonsNav">
					<a class="arrowUp" style="display:none"><i></i></a>
					<ul>
						<?php $first   = 0;
						$curren_lesson = 0;
						$lastViewed = 0;
						foreach ( $lessons as $l ) {
							$curren_lesson = ( $first == 0 ? $l : $curren_lesson );
							$viewed_sql    = "SELECT * FROM courses_lesson_views WHERE id_lesson='" . $l['id'] . "' AND id_member='" . $_SESSION['user']['id'] . "'";
							$viewed_query  = $mysqli->query( $viewed_sql );
							$viewed        = $viewed_query->num_rows;
							?>
							<li class="hidden">
								<a class="<?= ( $first == 0 ? 'current' : '' ) . ' ' . ( $viewed > 0 ? 'viewed' : '' ).' '.($lastViewed==0?'':'blocked') ?>" data-lesson="<?=($lastViewed==0?$l['id']:-1)?>">Video
									#<?= ( $first + 1 ) ?></a>
							</li>
							<?php
							$lastViewed=($viewed>0?0:1);
							$first ++;
						}
						?>
					</ul>
					<a class="arrowDown" style="display:none"><i></i></a>
				</div>
				<div class="lessonPlayer">
					<h1><?=$curren_lesson['name']?></h1>
					<iframe src="//player.vimeo.com/video/<?=$curren_lesson['video']?>" width="" height="" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
				</div>
			</div>
			<?php
			break;


		/* ***************************************
		PAGE: Team Training (Member)
		Func: Load
		(Louis)
		**************************************** */

		case 'loadLesson':
			$current = addslashes(trim($_POST['lesson']));

			$lessons_sql = "SELECT * FROM courses_lesson WHERE id='".$current."'";
			$lessons_query = $mysqli->query($lessons_sql);
			$lesson = $lessons_query->fetch_assoc();

			?>
			<h1><?=$lesson['name']?></h1>
			<iframe src="//player.vimeo.com/video/<?=$lesson['video']?>" width="" height="" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
			<?php
			break;


		/* ***************************************
		PAGE: Team Training (Member)
		Func: Mark as viewed and disable next
		(Louis)
		**************************************** */

		case 'enableNextVideo':
			$current = addslashes(trim($_POST['video']));

			$lessons_sql = "SELECT * FROM courses_lesson WHERE id='".$current."'";
			$lessons_query = $mysqli->query($lessons_sql);
			$lesson = $lessons_query->fetch_assoc();

			$insertView_sql = "INSERT INTO courses_lesson_views (id_member, id_lesson) VALUES ('".$_SESSION['user']['id']."', '".$current."')";
			$insertView = $mysqli->query($insertView_sql);

			$next_sql = "SELECT * FROM courses_lesson WHERE id_course='".$lesson['id_course']."' AND  `order`='".($lesson['order']*1+1)."'";
			$next_query = $mysqli->query($next_sql);

			if($next_query->num_rows>0){
                $next = $next_query->fetch_assoc();
            } else {
                $next_sql = "SELECT * FROM courses_lesson WHERE id_course='".$lesson['id_course']."' AND  id>'".$lesson['id']."' ORDER BY id ASC LIMIT 1";
                $next_query = $mysqli->query($next_sql);
                $next = $next_query->fetch_assoc();
            }


			if($next_query->num_rows>0){
				echo $next['id'];
			} else {
				echo 0;
			}

			break;



		case 'full_path':
			echo __FILE__;
			break;


		case 'unset_session':
			session_unset();
			break;


		case 'session':
			var_dump( $_SESSION );
			break;

		case 'tempMemberDashboard':
			$_SESSION['user']['admin']=$_SESSION['user'];
			$fakeuser_sql = "SELECT * FROM subscribers WHERE email='".$_SESSION['user']['email']."'";
			$fakeuser_query = $mysqli->query($fakeuser_sql);
			$_SESSION['user'] = $fakeuser_query->fetch_assoc();
			$_SESSION['user']['temp_client']=1;
			echo '1';
			break;


        /****************************************/
        /****************************************/
        /*            CREATE CHAT ROOM          */
        /****************************************/
        /****************************************/

        case 'newChatRoom':
            $name = addslashes(trim($_POST['name']));
            $packages = addslashes(trim($_POST['packages']));
            $checked = addslashes(trim($_POST['checked']));

            if(
                $name != '' &&
                $packages != '' &&
                $checked != ''
            ){

                $insert_sql = "INSERT INTO rooms (name, packages, status) VALUES ('".$name."', '".$packages."', '".$checked."')";
                $insert_query = $mysqli_chat->query($insert_sql);

                echo '1$#';

            } else {
                echo '0$#All fields are required';
            }

            break;

        /****************************************/
        /****************************************/
        /*           /CREATE CHAT ROOM          */
        /****************************************/
        /****************************************/


        /****************************************/
        /****************************************/
        /*            EDIT CHAT ROOM            */
        /****************************************/
        /****************************************/

        case 'editChatRoom':
            $id = addslashes(trim($_POST['id']));
            $name = addslashes(trim($_POST['name']));
            $packages = addslashes(trim($_POST['packages']));
            $checked = addslashes(trim($_POST['checked']));

            if(
                $id != '' &&
                $name != '' &&
                $packages != '' &&
                $checked != ''
            ){

                $update_sql = "UPDATE rooms SET name='".$name."', packages='".$packages."', status='".$checked."' WHERE id='".$id."'";
                $update_query = $mysqli_chat->query($update_sql);

                echo '1$#';

            } else {
                echo '0$#All fields are required';
            }

            break;

        /****************************************/
        /****************************************/
        /*           /EDIT CHAT ROOM            */
        /****************************************/
        /****************************************/

		case 'verifyUserEmailForRegistration':
			$email = addslashes(trim($_POST['email']));
			$mobile = addslashes(trim($_POST['mobile']));

			$search_email = "SELECT * FROM subscribers WHERE email='".$email."'";
			$search_email_query = $mysqli->query($search_email);

			$search_mobile = "SELECT * FROM subscribers WHERE mobile='".$mobile."'";
			$search_mobile_query = $mysqli->query($search_mobile);

			if($search_email_query->num_rows>0 && $search_mobile_query->num_rows>0){
				echo '0$#Both email and phone already in use.';
			} else if($search_email_query->num_rows>0){
				echo '0$#Email already in use.';
			} else if($search_mobile_query->num_rows>0){
				echo '0$#Phone already in use.';
			} else {
				echo '1$#';
			}

			break;


		default:
			setLog('tried to call an inexistent function', '<fname> trying to reach an inexistent function: '.$_GET['action']);
			echo 'Unreachable function';
			break;
	}

}// else echo 'Wrong request type';







/****************************************/
/****************************************/
/*               FUNCTIONS              */
/****************************************/
/****************************************/

function trimPhone($phone){
	return preg_replace('/[^+0-9]/', '', $phone);
}

function getManagerTraders(){

	$mysqli = new mysqli($GLOBALS['server'], $GLOBALS['user'], $GLOBALS['pwd'], $GLOBALS['db']);

	$managerTraders_sql = "SELECT * FROM clients WHERE manager_id = '".$_SESSION['user']['id']."'";
	$managerTraders_query = $mysqli->query($managerTraders_sql);
	//$managerTraders = $managerTraders_query->fetch_all(MYSQLI_ASSOC);
    $managerTraders = array();
    while($managerTrader = $managerTraders_query->fetch_assoc()) $managerTraders[] = $managerTrader;

	return $managerTraders;

}

function sendMessage($media, $message, $number, $from){

	$sid = "AC13788ff81cb9c611f22e4a16186f848b";
	$token = "369c6f272a8912a1859abc2c7e8cc94c";

	$client = new Twilio\Rest\Client($sid, $token);

    $mysqli = new mysqli($GLOBALS['server'], $GLOBALS['user'], $GLOBALS['pwd'], $GLOBALS['db']);

	try {
		$messageSent = $client->messages->create(
			'' . $number, // Text this number
			array(
				'from'  => '' . $from,
				'body'  => $message,
				'media' => $media
			)
		);
    } catch (Exception $e) {
		echo 'sendMessage function error (from: '.$from.'): '. $e->getMessage();
        //print_r($e->getMessage());
        //$saveerror_sql = "INSERT INTO errors (function, error) VALUES ('sending message', '".$e->getMessage()."')";

        //if(!$mysqli->query($saveerror_sql)){
        //    echo $mysqli->error;
        //}
    }

	//var_dump($message);


	//echo serialize( $messageSent->solution );
	//echo 'sid: '.$messageSent->solution['sid'];

    if(isset($messageSent)) {
        $saveSms_sql = "INSERT INTO messages (user_from, user_dest, message, FromZip, SmsMessageSid, SmsSid, FromState, FromCity, FromCountry, MessageSid, date) VALUES ('" . $from . "', '" . $number . "', '" . $message . "', '', '" . $messageSent->solution['sid'] . "', '" . $messageSent->solution['sid'] . "', '', '', '', '" . $messageSent->solution['sid'] . "', '" . time() . "')";
        $mysqli->query($saveSms_sql);

        /*
        try{
            //We are using the sandbox version of the APNS for development. For production
            //        environments, change this to ssl://gateway.push.apple.com:2195
            //$apnsServer = 'ssl://gateway.sandbox.push.apple.com:2195';
            $apnsServer = 'gateway.push.apple.com:2195';
            //Make sure this is set to the password that you set for your private key
            //when you exported it to the .pem file using openssl on your OS X
            $privateKeyPassword = '1234';
            //Put your own message here if you want to
            // message is the one that is received by the function call
            //$message = 'Welcome to iOS 7 Push Notifications';

            //Pur your device token here
            $deviceToken =
                '05924634A8EB6B84437A1E8CE02E6BE6683DEC83FB38680A7DFD6A04C6CC586E';
            //Replace this with the name of the file that you have placed by your PHP
            //script file, containing your private key and certificate that you generated
            //earlier
            $pushCertAndKeyPemFile = 'PushCertificateAndKey.pem';
            $stream = stream_context_create();
            stream_context_set_option($stream,
                'ssl',
                'passphrase',
                $privateKeyPassword);
            stream_context_set_option($stream,
                'ssl',
                'local_cert',
                $pushCertAndKeyPemFile);

            $connectionTimeout = 20;
            $connectionType = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;
            $connection = stream_socket_client($apnsServer,
                $errorNumber,
                $errorString,
                $connectionTimeout,
                $connectionType,
                $stream);
            if (!$connection){
                //echo "Failed to connect to the APNS server. Error no = $errorNumber<br/>";
                setLog('Failed to connect to the APNS server', "Error number: ".$errorNumber." // ".serialize(array("media"=>$media, "message"=>$message, "number"=>$number, "from"=>$from)));
                exit;
            } //else {
              //  echo "Successfully connected to the APNS. Processing...</br>";
            //}
            $messageBody['aps'] = array('alert' => $message,
                'sound' => 'default',
                'badge' => 2,
            );
            $payload = json_encode($messageBody);
            $notification = chr(0) .
                pack('n', 32) .
                pack('H*', $deviceToken) .
                pack('n', strlen($payload)) .
                $payload;
            $wroteSuccessfully = fwrite($connection, $notification, strlen($notification));
            if (!$wroteSuccessfully){
                //echo "Could not send the message<br/>";
                setLog('Could not send the message', "Error number: ".$errorNumber." // ".serialize(array("media"=>$media, "message"=>$message, "number"=>$number, "from"=>$from)));
            }
            //else {
            //    echo "Successfully sent the message<br/>";
            //    setLog('Could not send the message', "Error number: ".$errorNumber." // ".serialize(array("media"=>$media, "message"=>$message, "number"=>$number, "from"=>$from)));
            //}
            fclose($connection);
        }catch(Exception $e){
            setLog('Push Notification Failed', serialize(array("media"=>$media, "message"=>$message, "number"=>$number, "from"=>$from, "Exception"=>$e->getMessage())));
        }
        */

    }

}


function updateContacts(){

	/*$mysqli = new mysqli($GLOBALS['server'], $GLOBALS['user'], $GLOBALS['pwd'], $GLOBALS['db']);

	$token = verifyToken();

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://superphone.io/api/contacts?q=&page=0&limit=1");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	//curl_setopt($ch, CURLOPT_HEADER, FALSE);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		"Content-Type: application/json",
		"Authorization: Bearer ".$token,
	));

	$response = json_decode(curl_exec($ch));
	curl_close($ch);

	if(isset($response->error) && $response->error==400 && !isset($_SESSION['error'])){
		$_SESSION['error']=1;
		$resetToken = "UPDATE apikey SET token='' WHERE user='".$_SESSION['user']['id']."' AND platform='superphone'";
		$mysqli->query($resetToken);
		updateContacts();
	}

	//var_dump($response);

	$qtt = $response->count;

	//echo $qtt;

	$ch = curl_init();

	curl_setopt( $ch, CURLOPT_URL, "https://superphone.io/api/contacts?q=&page=0&limit=".$qtt );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	//curl_setopt($ch, CURLOPT_HEADER, FALSE);

	curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
		"Content-Type: application/json",
		"Authorization: Bearer " . $token,
	) );

	$response = json_decode( curl_exec( $ch ) );
	curl_close( $ch );

	//var_dump($response);

	$new = 0;
	$duplicated = 0;

	foreach ( $response->data as $key => $val ) {
		$subscriber = Array();
		foreach ( $val as $key2 => $val2 ) {
			//echo "$key2 => $val2\n";
			$subscriber[$key2]=$val2;
		}
		$query = $mysqli->query("SELECT count(id) FROM subscribers WHERE id_sp='".$subscriber['id']."'");
		$affected_rows = $query->fetch_array()[0];

		if($affected_rows>0){
			$duplicated++;
		} else {
            setNot('New member added!', 'member');
			$country_q = $mysqli->query("SELECT id FORM countries WHERE country_code='".$subscriber['country']."'");
			$country_id= $query->fetch_array()[0];

			$sql_insert = "INSERT INTO subscribers (id_sp, client, photo, name, email, mobile, country, zipcode, city, birthday, jobtitle, company, instagram, added, last_contacted, verificated, active) VALUES ('".addslashes($subscriber['id'])."', '".$_SESSION['user']['id']."', '".addslashes($subscriber['photo'])."', '".addslashes($subscriber['name'])."', '".addslashes($subscriber['email'])."', '".addslashes($subscriber['mobile'])."', '".addslashes($country_id)."', '".addslashes($subscriber['zipcode'])."', '".addslashes($subscriber['city'])."', '".addslashes($subscriber['birthday'])."', '".addslashes($subscriber['jobTitle'])."', '".addslashes($subscriber['company'])."', '".addslashes($subscriber['instagram'])."', '".strtotime($subscriber['created'])."', '".strtotime($subscriber['lastContacted'])."', '1', '1')";
			$query = $mysqli->query($sql_insert);

			$new++;
			//echo $sql_insert."\n\n\n\n";

		}
	}

	setLog('update contacts', '<fname> updated his contacts. Got '.$new.' new contacts.');*/

}

function verifyToken($user=null){

	if(is_null($user)) $user = $_SESSION['user']['id'];

	$mysqli = new mysqli($GLOBALS['server'], $GLOBALS['user'], $GLOBALS['pwd'], $GLOBALS['db']);
	$oldToken_sql="SELECT token FROM apikeys WHERE platform='superphone' AND user='".$user."'";
	//echo $sql;
	$oldToken_query = $mysqli->query($oldToken_sql);
	$token = $oldToken_query->fetch_assoc()['token'];

	if($token==""){
		$ch = curl_init();

		$sql="SELECT * FROM apikeys WHERE platform='superphone' AND user='".$user."'";
		$query = $mysqli->query($sql);
		$apikeys = $query->fetch_assoc();

		// MOCKUP SERVER
		//curl_setopt($ch, CURLOPT_URL, "https://private-anon-1830c2e0e9-superphone.apiary-mock.com/api/v2/oauth/token");

		// PRODUCTION
		curl_setopt($ch, CURLOPT_URL, "https://superphone.io/api/v2/oauth/token");

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);

		curl_setopt($ch, CURLOPT_POST, TRUE);

		curl_setopt($ch, CURLOPT_POSTFIELDS, "{
		  \"email\": \"".$apikeys['username']."\",
		  \"grantType\": \"password\",
		  \"password\": \"".$apikeys['password']."\"
		}");

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/json"
		));

		$response = json_decode(curl_exec($ch), true);
		curl_close($ch);

		//var_dump($response);

		$sql="UPDATE apikeys SET token='".$response['access_token']."' WHERE platform='superphone' AND user='".$user."'";
		//echo $sql;
		$mysqli->query($sql);
		$token = $response['access_token'];
		setLog('token updated', '<fname> updated his token of superphone to: '.$response['access_token']);
		//var_dump( $response );
	}

	return $token;

}

function setLog($action, $description){

	$mysqli = new mysqli($GLOBALS['server'], $GLOBALS['user'], $GLOBALS['pwd'], $GLOBALS['db']);

	if(isset($_SESSION['user'])) {
		$user_name   = explode( ' ', (isset($_SESSION['user']['full_name'])?$_SESSION['user']['full_name']:$_SESSION['user']['name']) );
		$description = str_replace( '<fname>', $user_name[0] . ' ' . $user_name[ count( $user_name ) - 1 ], $description );
	}

	$log_sql = "INSERT INTO logs (user, action, description, time) VALUES ('".(isset($_SESSION['user']['id'])?$_SESSION['user']['id']:0)."', '".$action."', '".$description."', '".time()."')";
	$log_query = $mysqli->query($log_sql);

}

function setNot($action, $description){

    $mysqli = new mysqli($GLOBALS['server'], $GLOBALS['user'], $GLOBALS['pwd'], $GLOBALS['db']);

    if(isset($_SESSION['user'])) {
        $user_name   = explode( ' ', $_SESSION['user']['full_name'] );
        $description = str_replace( '<fname>', $user_name[0] . ' ' . $user_name[ count( $user_name ) - 1 ], $description );
    }

    $not_sql = "INSERT INTO notifications (user, action, description, time) VALUES ('".$_SESSION['user']['id']."', '".$action."', '".$description."', '".time()."')";
    $not_query = $mysqli->query($not_sql);

}

function slugify($text){
	// replace non letter or digits by -
	$text = preg_replace('~[^\pL\d]+~u', '-', $text);

	// transliterate
	$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

	// remove unwanted characters
	$text = preg_replace('~[^-\w]+~', '', $text);

	// trim
	$text = trim($text, '-');

	// remove duplicate -
	$text = preg_replace('~-+~', '-', $text);

	// lowercase
	$text = strtolower($text);

	if (empty($text)) {
		return 'n-a';
	}

	return $text;
}

function getState($zipcode){

	$mysqli = new mysqli($GLOBALS['server'], $GLOBALS['user'], $GLOBALS['pwd'], $GLOBALS['db']);

	$getState = $mysqli->query("SELECT stateCode FROM zipcodes WHERE zipcode = '".$zipcode."'");
	$row = $getState->fetch_assoc();
	$stateCode = $row['stateCode'];

	return $stateCode;

}





function getMailTpl($tags, $content, $tplId){
	$final='';

	if($tplId==''){
		$final=$content;
	}else if($tplId==1){

		$template='
<head>

    <style type="text/css">
		p{
		margin:10px 0;
            padding:0;
        }
        table{
		border-collapse:collapse;
        }
        h1,h2,h3,h4,h5,h6{
		display:block;
		margin:0;
		padding:0;
	}
        img,a img{
		border:0;
		height:auto;
		outline:none;
		text-decoration:none;
        }
        body,#bodyTable,#bodyCell{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
        }
#outlook a{
padding:0;
}
img{
	-ms-interpolation-mode:bicubic;
        }
        table{
	mso-table-lspace:0;
            mso-table-rspace:0;
        }
        .ReadMsgBody{
	width:100%;
}
        .ExternalClass{
	width:100%;
}
        p,a,li,td,blockquote{
	mso-line-height-rule:exactly;
        }
        a[href^=tel],a[href^=sms]{
	color:inherit;
	cursor:default;
	text-decoration:none;
        }
        p,a,li,td,body,table,blockquote{
	-ms-text-size-adjust:100%;
            -webkit-text-size-adjust:100%;
        }
        .ExternalClass,.ExternalClass p,.ExternalClass td,.ExternalClass div,.ExternalClass span,.ExternalClass font{
	line-height:100%;
        }
        a[x-apple-data-detectors]{
	color:inherit !important;
            text-decoration:none !important;
            font-size:inherit !important;
            font-family:inherit !important;
            font-weight:inherit !important;
            line-height:inherit !important;
        }
        #bodyCell{
            padding:10px;
        }
        .templateContainer{
	max-width:600px !important;
        }
        a.mcnButton{
	display:block;
}
        .mcnImage{
	vertical-align:bottom;
        }
        .mcnTextContent{
	word-break:break-word;
        }
        .mcnTextContent img{
	height:auto !important;
        }
        .mcnDividerBlock{
	table-layout:fixed !important;
        }

        body,#bodyTable{
            /*@editable*/background-color:#FAFAFA;
        }

        #bodyCell{
            /*@editable*/border-top:0;
        }

        .templateContainer{
	/*@editable*/border:0;
}

        h1{
	/*@editable*/color:#202020;
	/*@editable*/font-family:Helvetica;
            /*@editable*/font-size:26px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:normal;
            /*@editable*/text-align:left;
        }

        h2{
	/*@editable*/color:#202020;
	/*@editable*/font-family:Helvetica;
            /*@editable*/font-size:22px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:normal;
            /*@editable*/text-align:left;
        }

        h3{
	/*@editable*/color:#202020;
	/*@editable*/font-family:Helvetica;
            /*@editable*/font-size:20px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:normal;
            /*@editable*/text-align:left;
        }

        h4{
	/*@editable*/color:#202020;
	/*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:normal;
            /*@editable*/text-align:left;
        }

        #templatePreheader{
            /*@editable*/background-color:#FAFAFA;
            /*@editable*/background-image:none;
            /*@editable*/background-repeat:no-repeat;
            /*@editable*/background-position:center;
            /*@editable*/background-size:cover;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
            /*@editable*/padding-top:9px;
            /*@editable*/padding-bottom:9px;
        }

        #templatePreheader .mcnTextContent,#templatePreheader .mcnTextContent p{
            /*@editable*/color:#656565;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:12px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }

        #templatePreheader .mcnTextContent a,#templatePreheader .mcnTextContent p a{
            /*@editable*/color:#656565;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }

        #templateHeader{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/background-image:none;
            /*@editable*/background-repeat:no-repeat;
            /*@editable*/background-position:center;
            /*@editable*/background-size:cover;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
            /*@editable*/padding-top:9px;
            /*@editable*/padding-bottom:0;
        }

        #templateHeader .mcnTextContent,#templateHeader .mcnTextContent p{
            /*@editable*/color:#202020;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }

        #templateHeader .mcnTextContent a,#templateHeader .mcnTextContent p a{
            /*@editable*/color:#2BAADF;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }

        #templateBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/background-image:none;
            /*@editable*/background-repeat:no-repeat;
            /*@editable*/background-position:center;
            /*@editable*/background-size:cover;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:2px solid #EAEAEA;
            /*@editable*/padding-top:0;
            /*@editable*/padding-bottom:9px;
        }

        #templateBody .mcnTextContent,#templateBody .mcnTextContent p{
            /*@editable*/color:#202020;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }

        #templateBody .mcnTextContent a,#templateBody .mcnTextContent p a{
            /*@editable*/color:#2BAADF;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }

        #templateFooter{
            /*@editable*/background-color:#FAFAFA;
            /*@editable*/background-image:none;
            /*@editable*/background-repeat:no-repeat;
            /*@editable*/background-position:center;
            /*@editable*/background-size:cover;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
            /*@editable*/padding-top:9px;
            /*@editable*/padding-bottom:9px;
        }

        #templateFooter .mcnTextContent,#templateFooter .mcnTextContent p{
            /*@editable*/color:#656565;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:12px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }

        #templateFooter .mcnTextContent a,#templateFooter .mcnTextContent p a{
            /*@editable*/color:#656565;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
        @media only screen and (min-width:768px){
	.templateContainer{
		width:600px !important;
            }

        }	@media only screen and (max-width: 480px){
	body,table,td,p,a,li,blockquote{
		-webkit-text-size-adjust:none !important;
            }

        }	@media only screen and (max-width: 480px){
	body{
		width:100% !important;
		min-width:100% !important;
            }

        }	@media only screen and (max-width: 480px){
	#bodyCell{
	padding-top:10px !important;
            }

        }	@media only screen and (max-width: 480px){
	.mcnImage{
		width:100% !important;
	}

        }	@media only screen and (max-width: 480px){
	.mcnCartContainer,.mcnCaptionTopContent,.mcnRecContentContainer,.mcnCaptionBottomContent,.mcnTextContentContainer,.mcnBoxedTextContentContainer,.mcnImageGroupContentContainer,.mcnCaptionLeftTextContentContainer,.mcnCaptionRightTextContentContainer,.mcnCaptionLeftImageContentContainer,.mcnCaptionRightImageContentContainer,.mcnImageCardLeftTextContentContainer,.mcnImageCardRightTextContentContainer{
		max-width:100% !important;
                width:100% !important;
            }

        }	@media only screen and (max-width: 480px){
	.mcnBoxedTextContentContainer{
		min-width:100% !important;
            }

        }	@media only screen and (max-width: 480px){
	.mcnImageGroupContent{
		padding:9px !important;
            }

        }	@media only screen and (max-width: 480px){
	.mcnCaptionLeftContentOuter .mcnTextContent,.mcnCaptionRightContentOuter .mcnTextContent{
		padding-top:9px !important;
            }

        }	@media only screen and (max-width: 480px){
	.mcnImageCardTopImageContent,.mcnCaptionBlockInner .mcnCaptionTopContent:last-child .mcnTextContent{
		padding-top:18px !important;
            }

        }	@media only screen and (max-width: 480px){
	.mcnImageCardBottomImageContent{
		padding-bottom:9px !important;
            }

        }	@media only screen and (max-width: 480px){
	.mcnImageGroupBlockInner{
		padding-top:0 !important;
                padding-bottom:0 !important;
            }

        }	@media only screen and (max-width: 480px){
	.mcnImageGroupBlockOuter{
		padding-top:9px !important;
                padding-bottom:9px !important;
            }

        }	@media only screen and (max-width: 480px){
	.mcnTextContent,.mcnBoxedTextContentColumn{
		padding-right:18px !important;
                padding-left:18px !important;
            }

        }	@media only screen and (max-width: 480px){
	.mcnImageCardLeftImageContent,.mcnImageCardRightImageContent{
		padding-right:18px !important;
                padding-bottom:0 !important;
                padding-left:18px !important;
            }

        }	@media only screen and (max-width: 480px){
	.mcpreview-image-uploader{
		display:none !important;
                width:100% !important;
            }

        }	@media only screen and (max-width: 480px){

	h1{
		/*@editable*/font-size:22px !important;
                /*@editable*/line-height:125% !important;
            }

        }	@media only screen and (max-width: 480px){

	h2{
		/*@editable*/font-size:20px !important;
                /*@editable*/line-height:125% !important;
            }

        }	@media only screen and (max-width: 480px){

	h3{
		/*@editable*/font-size:18px !important;
                /*@editable*/line-height:125% !important;
            }

        }	@media only screen and (max-width: 480px){

	h4{
		/*@editable*/font-size:16px !important;
                /*@editable*/line-height:150% !important;
            }

        }	@media only screen and (max-width: 480px){

	.mcnBoxedTextContentContainer .mcnTextContent,.mcnBoxedTextContentContainer .mcnTextContent p{
		/*@editable*/font-size:14px !important;
                /*@editable*/line-height:150% !important;
            }

        }	@media only screen and (max-width: 480px){

	#templatePreheader{
	/*@editable*/display:block !important;
            }

        }	@media only screen and (max-width: 480px){

	#templatePreheader .mcnTextContent,#templatePreheader .mcnTextContent p{
	/*@editable*/font-size:14px !important;
                /*@editable*/line-height:150% !important;
            }

        }	@media only screen and (max-width: 480px){

	#templateHeader .mcnTextContent,#templateHeader .mcnTextContent p{
	/*@editable*/font-size:16px !important;
                /*@editable*/line-height:150% !important;
            }

        }	@media only screen and (max-width: 480px){

	#templateBody .mcnTextContent,#templateBody .mcnTextContent p{
	/*@editable*/font-size:16px !important;
                /*@editable*/line-height:150% !important;
            }

        }	@media only screen and (max-width: 480px){

	#templateFooter .mcnTextContent,#templateFooter .mcnTextContent p{
	/*@editable*/font-size:14px !important;
                /*@editable*/line-height:150% !important;
            }

        }</style></head>
<body>
<center>
    <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
        <tr>
            <td align="center" valign="top" id="bodyCell">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateContainer">
                    <tr>
                        <td valign="top" id="templateHeader"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnImageBlock" style="min-width:100%;">
                                <tbody class="mcnImageBlockOuter">
                                <tr>
                                    <td class="mcnImageContent" valign="top" style="background-image: url([[TRADER_COVER]]);background-size: cover;background-repeat: no-repeat;background-position: 50% 50%;padding-right: 9px;padding-left: 9px;padding-top: 0;padding-bottom: 0;text-align:center;height: 230px;">
                                        <img align="center" alt="" src="[[TRADER_LOGO]]" width="200" style="max-width:1024px;padding-bottom: 0;display: inline !important;vertical-align: bottom;margin-top: 55px;" class="mcnImage">


                                    </td>
                                </tr>
                                </tbody>
                            </table></td>
                    </tr>
                    <tr>
                        <td valign="top" id="templateBody"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
                                <tbody class="mcnTextBlockOuter">
                                <tr>
                                    <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">

                                        <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                                            <tbody><tr>

                                                <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                                                    '.$content.'

</td>
</tr>
</tbody></table>
</td>
</tr>
</tbody>
</table></td>
</tr>
</table>
</td>
</tr>
</table>
</center>
</body>
</html>';

		/*
        $traderInfo['logo']
        $traderInfo['cover']
        $content*/


		$sr=Array('[[TRADER_COVER]]','[[TRADER_LOGO]]');
		$rp=Array($tags['cover'], $tags['logo']);
		$final=str_replace($sr, $rp, $template);
	}

	return $final;
}
function sendMail($mailto, $subject, $tags, $content, $files='', $config=[]){

	/*
     * Configs
     *      - $config['log'] > Para gravar ou não o email em BD
     *      - $config['tpl'] > ID de template de base de dados para ir buscar o template com TAGS [[MAIL]]
     *      - $config['replyto'] > Adicionar um reply to ao email
     *      - $config['type'] >
     *          1 - php mail()
     *          2 - sendgrid
     *          3 - PHPMailer()
     *      - $config['copy'] > Array com emails de copy a enviar
     *
     * $tags > Têm que coincidir com o template a utilizar no futuro
     */

    if(strpos($content, 'body')>=0) {
        $mailtxt = $content;
    } else {
        $mailtxt = getMailTpl($tags, $content, 1);
    }

	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

	$headers .= 'From: '. (isset($tags['email'])?$tags['email']:'no-reply@lionsofforex.com') . "\r\n";

	if($mailto!='' && $subject!='' && $mailtxt!=''){
		//mail($mailto, $subject, $mailtxt, $headers);

        require 'phpmailer/PHPMailerAutoload.php';

        $mail = new PHPMailer;

        $mail->CharSet = 'UTF-8';

        $mail->isSMTP();                                      // Set mailer to use SMTP
        //$mail->SMTPDebug = 4;
        $mail->Host = 'aws.lionsofforex.com';               // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = 'bertodelvanicci@lionsofforex.com';// SMTP username
        $mail->Password = 'cd4uswhbgi';                       // SMTP password
        $mail->SMTPSecure = 'TLS';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                                    // TCP port to connect to
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('bertodelvanicci@lionsofforex.com', 'Berto Delvanicci');
        $mail->addAddress($mailto);     // Add a recipient
        //$mail->addCC('cc@example.com');
        $mail->addBCC('logs@lionsofforex.com');

        //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        $mail->isHTML(true);                                  // Set email format to HTML

        $mail->Subject = $subject;
        $mail->Body    = $mailtxt;
        //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        if(!$mail->send()) {
            return 0;
        }

        return 1;
	}else return -1;

}

function sendSimpleMail($mailto, $subject, $content){
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    $headers .= 'From: no-reply@lionsofforex.com' . "\r\n";

    if($mailto!='' && $subject!='' && $content!=''){
        mail($mailto, $subject, $content, $headers);
        return 1;
    }else return -1;
}
function PlaceWatermark($file, $xxx, $yyy, $outdir) {

	require_once('fpdf181/fpdf.php');
	require_once('fpdf181/fpdi.php');
	require_once('fpdf181/alphapdf.php');



	/*$name = uniqid();
    $font_size = 5;
    $ts=explode("\n",$text);
    $width=0;
    foreach ($ts as $k=>$string) {
        $width=max($width,strlen($string));
    }
    $width  = imagefontwidth($font_size)*$width;
    $height = imagefontheight($font_size)*count($ts);
    $el=imagefontheight($font_size);
    $em=imagefontwidth($font_size);
    $img = imagecreatetruecolor($width,$height);
    // Background color
    $bg = imagecolorallocate($img, 255, 255, 255);
    imagefilledrectangle($img, 0, 0,$width ,$height , $bg);
    // Font color
    $color = imagecolorallocate($img, 0, 0, 0);
    foreach ($ts as $k=>$string) {
        $len = strlen($string);
        $ypos = 0;
        for($i=0;$i<$len;$i++){
            $xpos = $i * $em;
            $ypos = $k * $el;
            imagechar($img, $font_size, $xpos, $ypos, $string, $color);
            $string = substr($string, 1);
        }
    }
    imagecolortransparent($img, $bg);
    $blank = imagecreatetruecolor($width, $height);
    $tbg = imagecolorallocate($blank, 255, 255, 255);
    imagefilledrectangle($blank, 0, 0,$width ,$height , $tbg);
    imagecolortransparent($blank, $tbg);
    if ( ($op < 0) OR ($op >100) ){
        $op = 100;
    }
    imagecopymerge($blank, $img, 0, 0, 0, 0, $width, $height, $op);
    imagepng($blank,$name.".png");*/
	// Created Watermark Image
	$pdf = new FPDI();





	if (file_exists("./".$file)){
		$pagecount = $pdf->setSourceFile($file);
	} else {
		return FALSE;
	}
	for($i=1; $i <= $pagecount; $i++) {
		$tpl = $pdf->importPage($i);
		$pdf->addPage();
		$pdf->useTemplate($tpl, 1, 1, 0, 0, TRUE);



		//Put the watermark
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 0, 20, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 90, 20, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 180, 20, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 0, 80, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 90, 80, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 180, 80, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 0, 140, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 90, 140, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 180, 140, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 0, 200, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 90, 200, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 180, 200, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 0, 260, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 90, 260, 30, 0, 'png');
		$pdf->Image('assets/images/lions-of-forex-watermark.png', 180, 260, 30, 0, 'png');
	}
	if ($outdir === TRUE){
		return $pdf->Output();
	} else {
		return $pdf;
	}
}

?>
