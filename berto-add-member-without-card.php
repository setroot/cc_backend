<?php

$demo = array(
	"stripe_pk" => 'pk_test_V5uUiovWhPPkaBTeLxIKY7sj',
	"stripe_sk" => 'sk_test_erGeCPLdFRwauBa1ecANrwPk',
	"packages"  => array(
		"13" => 'plan_DiJi6tsh7NORiD',
		"14" => 'plan_DiJj7jiMSkihkk',
		"15" => 'prod_DiJkgwSGdwrxse',
		"19" => 'plan_Dz8JITnyzhZhma',
		"31" => 'plan_EqIhtXwChkakZR',
	)
);

$live = array(
	"stripe_pk" => 'pk_live_XnSvhN60wCyTh70EFQf0w4Nt',
	"stripe_sk" => 'sk_live_sK7tnuS2MPzWmfsgzCOPeJUy',
	"packages"  => array(
		"13" => 'plan_FY8M4T8qlyVd6Y',
		"14" => 'plan_FY8NkLcxlqZ0tJ',
		"15" => 'plan_FY8NOXuGXiVGbk',
		"31" => 'plan_FY8OGOLxBwAWgB',
	)
);

set_time_limit(0);

error_reporting(E_ERROR);
ini_set('display_errors', 1);

session_start();

$server = 'localhost';
$user = 'admin_beta1';
$pwd = 'vALA6T7Slc';
$db = 'admin_beta1';

$mysqli = new mysqli($server, $user, $pwd, $db);

require_once('stripe/init.php');

\Stripe\Stripe::setApiKey($live['stripe_sk']);

function trimPhone($phone){
	return preg_replace('/[^+0-9]/', '', $phone);
}

function setLog($action, $description){

	$mysqli = new mysqli($GLOBALS['server'], $GLOBALS['user'], $GLOBALS['pwd'], $GLOBALS['db']);

	$log_sql = "INSERT INTO logs (user, action, description, time) VALUES (0, '".$action."', '".$description."', '".time()."')";
	$log_query = $mysqli->query($log_sql);

}

if(isset($_POST['package'])){

	$name = addslashes(trim($_POST['name']));
	$email = addslashes(trim($_POST['email']));
	$password = addslashes(trim($_POST['password']));
	$country = addslashes(trim($_POST['country']));
	$mobile = addslashes(trim($_POST['mobile']));
	$forexExperience = addslashes(trim($_POST['forexExperience']));
	$promocode = addslashes(trim($_POST['promocode']));
	$package = addslashes(trim($_POST['package']));


	$error = 0;
	$error_msg = '';

	$success = 0;
	$success_msg = '';

	if (
		trim($name) != '' &&
		trim($email) != '' &&
		trim($password) != '' &&
		trim($country) != '' &&
		trim($mobile) != '' &&
		trim($forexExperience) != '' &&
		trim($package) != ''
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
					$verifyCodeExists_query = $mysqli->query($verifyCodeExists_sql);

					if ($verifyCodeExists_query->num_rows == 0) {
						$verifyCodeExists = false;
					} else {
						$verifyCodeExists = true;
					}

				} while ($verifyCodeExists);


				try {

					$customerOptions = array(
						"email"       => $email,
						"description" => "Name: ".$name
					);

					if ( $promocode != '' ) {
						$customerOptions["coupon"] = trim( $promocode );
					}

					$customer = \Stripe\Customer::create( $customerOptions );

					$subscrition = \Stripe\Subscription::create(array(
						"customer" => $customer->id,
						"plan" => $demo['packages'][$package]
					));



					$newClient_sql = "INSERT INTO subscribers (client, name, email, password, mobile, country, forexExperience, added, verify_code, active, package, coupon, stripeID, verificated, last_activation, first_activation) VALUE ('30', '" . $name . "', '" . $email . "', '" . md5($password) . "', '" . $phoneCode . trimPhone($mobile) . "', '" . $country . "', '" . $forexExperience . "', '" . time() . "', '', '1', '" . $package . "', '" . $promocode . "', '".$customer->id."', '1', '".time()."', '".time()."')";
					//setLog( 'register', explode(' ', $name)[0].' register using this data: '.serialize($_POST) .'and query: '.$newClient_sql);
					$newClient_query = $mysqli->query($newClient_sql);
					//echo $newClient_sql;


					$success = 1;
					$success_msg = 'User registered with success.';

				} catch (Exception $e){
					$error++;
					$error_msg=$e->getMessage();
				}

				if(isset($success) && $success>0)setLog('manual registration', 'session before payment: ' . serialize($_POST));

			} else {
				$error++;
				$error_msg =  'This phone is already registered.';
			}
		} else {

			$error++;
			$error_msg =  'This email is already in use.';

		}

	}


}

?>
<html>
	<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link href="/assets/css/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
		<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
		<link rel="stylesheet" href="/assets/css/vendor/animate/animate.min.css">
		<link type="text/css" rel="stylesheet" media="all" href="/assets/js/vendor/mmenu/css/jquery.mmenu.all.css">
		<link rel="stylesheet" href="/assets/js/vendor/videobackground/css/jquery.videobackground.css">
		<link rel="stylesheet" href="/assets/css/vendor/bootstrap-checkbox.css">
		<link rel="stylesheet" href="/assets/css/vendor/bootstrap/bootstrap-dropdown-multilevel.css">

		<link rel="stylesheet" href="/assets/js/vendor/rickshaw/css/rickshaw.min.css">
		<link rel="stylesheet" href="/assets/js/vendor/morris/css/morris.css">
		<link rel="stylesheet" href="/assets/js/vendor/tabdrop/css/tabdrop.css">
		<link rel="stylesheet" href="/assets/js/vendor/summernote/css/summernote.css">
		<link rel="stylesheet" href="/assets/js/vendor/summernote/css/summernote-bs3.css">
		<link rel="stylesheet" href="/assets/js/vendor/chosen/css/chosen.min.css">
		<link rel="stylesheet" href="/assets/js/vendor/chosen/css/chosen-bootstrap.css">
		<link rel="stylesheet" href="/assets/js/vendor/sweetalert/sweetalert.css">
		<link rel="stylesheet" href="/assets/js/vendor/datatables/css/dataTables.bootstrap.css">
		<link rel="stylesheet" href="/assets/js/vendor/chosen/css/chosen.min.css">
		<link rel="stylesheet" href="/assets/js/vendor/chosen/css/chosen-bootstrap.css">
		<link rel="stylesheet" href="/assets/js/vendor/dropzone/dropzone.css">
		<link rel="stylesheet" href="/assets/js/vendor/datepicker/css/bootstrap-datetimepicker.css">
		<link rel="stylesheet" href="assets/js/vendor/colorpicker/css/bootstrap-colorpicker.css">


		<link href="/assets/css/minimal.css" rel="stylesheet">
		<link href="/assets/css/custom.css" rel="stylesheet">
		<style>
			body{
				padding-top: 50px;
				background: rgba(39, 39, 39, 1);
				overflow: auto;
			}
			body.team .addMemberForm{
				position: relative;
				left: auto;
				top: auto;
				width: 100%;
				height: auto;
			}
			body.team .addMemberForm > div{
				position: relative;
			}
			.erro_message {
				max-width: 460px;
				margin: 0 auto;
				text-align: center;
				background: #ff9d9d;
				border: 2px solid red;
				padding: 7px 20px;
				font-weight: 600;
				border-radius: 100px;
			}
			.success_message {
				max-width: 460px;
				margin: 0 auto;
				text-align: center;
				background: #f0ffbf;
				border: 2px solid #a2d200;
				padding: 7px 20px;
				font-weight: 600;
				border-radius: 100px;
			}
		</style>
	</head>
	<body class="team">
		<?php if(isset($error) && $error>0){ ?>
			<div class="erro_message"><?=$error_msg?></div>
		<?php }  ?>
		<?php if(isset($success) && $success>0){ ?>
			<div class="success_message"><?=$success_msg?></div>
		<?php }  ?>
		<div class="col-md-2"></div>
		<div class="col-md-8 addMemberForm" style="">
			<div>
				<form id="form-register-step1" class="form-register" method="post">
					<section>
						<div class="input-group">
							<input type="text" class="form-control" name="name" id="name" placeholder="Full Name" <?=(isset($error)&&$error>0?'value="'.$name.'"':'')?>>
							<div class="input-group-addon"><i class="fa fa-user"></i></div>
						</div>
						<div class="input-group">
							<input type="email" class="form-control" name="email" id="email" placeholder="Email" <?=(isset($error)&&$error>0?'value="'.$email.'"':'')?>>
							<div class="input-group-addon"><i class="fa fa-at"></i></div>
						</div>
						<div class="input-group">
							<input type="password" class="form-control" name="password" id="password" placeholder="Create Password"  <?=(isset($error)&&$error>0?'value="'.$password.'"':'')?>>
							<div class="input-group-addon"><i class="fa fa-key"></i></div>
						</div>
						<div class="input-group fakeSelect ">
							<select name="country" class="chosen-select chosen-transparent form-control" id="country" style="width:100%">
								<option value="">Country</option>
								<?php

								$countries_sql = "SELECT * FROM countries WHERE open='1' ORDER BY id=230 DESC";
								$countries_query = $mysqli->query($countries_sql);
								//$countries = $countries_query->fetch_all(MYSQLI_ASSOC);
								$countries = array();
								while($country_select = $countries_query->fetch_assoc()) $countries[] = $country_select;
								$flags='';
								$flagsIds='';
								$phoneCodes='';

								foreach($countries as $country_option){
									$flagsIds .= ($flagsIds==''?$country_option['id']:','.$country_option['id']);
									$flags .= ($flags==''?$country_option['country_code']:','.$country_option['country_code']);
									$phoneCodes .= ($phoneCodes==''?$country_option['phone_code']:','.$country_option['phone_code']);
									echo '<option value="'.$country_option['id'].'" '.(isset($error)&&$error>0&&$country==$country_option['id']?'selected="selected"':'').'>'.$country_option['country_name'].'</option>';
								}

								?>
							</select>
						</div>
						<div class="input-group">
							<input type="hidden" id="flagsIds" value="230,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120,121,122,123,124,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,176,177,178,179,180,181,182,183,184,185,186,187,188,189,190,191,192,193,194,195,196,197,198,199,200,201,202,203,204,205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,231,232,233,234,235,236,237,238,239,240,241,242,243,244,245">
							<input type="hidden" id="flags" value="US,AF,AL,DZ,DS,AD,AO,AI,AQ,AG,AR,AM,AW,AU,AT,AZ,BS,BH,BD,BB,BY,BE,BZ,BJ,BM,BT,BO,BA,BW,BV,BR,IO,BN,BG,BF,BI,KH,CM,CA,CV,KY,CF,TD,CL,CN,CX,CC,CO,KM,CG,CK,CR,HR,CU,CY,CZ,DK,DJ,DM,DO,TP,EC,EG,SV,GQ,ER,EE,ET,FK,FO,FJ,FI,FR,FX,GF,PF,TF,GA,GM,GE,DE,GH,GI,GK,GR,GL,GD,GP,GU,GT,GN,GW,GY,HT,HM,HN,HK,HU,IS,IN,IM,ID,IR,IQ,IE,IL,IT,CI,JE,JM,JP,JO,KZ,KE,KI,KP,KR,XK,KW,KG,LA,LV,LB,LS,LR,LY,LI,LT,LU,MO,MK,MG,MW,MY,MV,ML,MT,MH,MQ,MR,MU,TY,MX,FM,MD,MC,MN,ME,MS,MA,MZ,MM,NA,NR,NP,NL,AN,NC,NZ,NI,NE,NG,NU,NF,MP,NO,OM,PK,PW,PS,PA,PG,PY,PE,PH,PN,PL,PT,PR,QA,RE,RO,RU,RW,KN,LC,VC,WS,SM,ST,SA,SN,RS,SC,SL,SG,SK,SI,SB,SO,ZA,GS,ES,LK,SH,PM,SD,SR,SJ,SZ,SE,CH,SY,TW,TJ,TZ,TH,TG,TK,TO,TT,TN,TR,TM,TC,TV,UG,UA,AE,GB,UM,UY,UZ,VU,VA,VE,VN,VG,VI,WF,EH,YE,ZR,ZM,ZW">
							<input type="hidden" id="phoneCodes" value="+1,+93,+355,+213,,+376,+244,+1264,,+1268,+54,+374,+297,+61,+43,+994,+1242,+973,+880,+1246,+375,+32,+501,+229,+1441,+975,+591,+387,+267,,+55,+246,+673,+359,+226,+257,+855,+237,+1,+238,+1345,+236,+235,+56,+86,+61,+61,+57,+269,+242,+682,+506,+385,+53,+357,+420,+45,+253,+1767,+1809,,+593,+20,+503,+240,+291,+372,+251,+500,+298,+679,+358,+33,,+594,+689,,+241,+220,+995,+49,+233,+350,,+30,+299,+1473,+590,+1671,+502,+224,+245,+592,+509,+ ,+504,+852,+36,+354,+91,+441624,+62,+98,+964,+353,+972,+39,+225,+441534,+1876,+81,+962,+7,+254,+686,+850,+82,,+965,+996,+856,+371,+961,+266,+231,+218,+423,+370,+352,+853,+389,+261,+265,+60,+960,+223,+356,+692,+596,+222,+230,,+52,+691,+373,+377,+976,+382,+1664,+212,+258,+95,+264,+674,+977,+31,,+687,+64,+505,+227,+234,+683,+672,+1670,+47,+968,+92,+680,+970,+507,+675,+595,+51,+63,+870,+48,+351,+1787,+974,+262,+40,+7,+250,+1869,+1758,+1784,+685,+378,+239,+966,+221,+381,+248,+232,+65,+421,+386,+677,+252,+27,,+34,+94,+290,+508,+249,+597,+47,+268,+46,+41,+963,+886,+992,+255,+66,+228,+690,+676,+1868,+216,+90,+993,+1649,+688,+256,+380,+971,+44,+1,+598,+998,+678,+379,+58,+84,+1284,+1340,+681,+212,+967,,+260,+263">
							<div class="flags-mobile" <?=(isset($error)&&$error>0?'data-preselected="'.$country.'"':'')?>></div>
							<input type="text" class="form-control" name="mobile" id="mobile" placeholder="Mobile"  <?=(isset($error)&&$error>0?'value="'.$mobile.'"':'')?>>
							<div class="input-group-addon"><i class="fa fa-phone"></i></div>
						</div>
					</section>
					<hr class="dash-sep">
					<section>
						<div class="input-group fakeSelect" style="width:100%">
							<select name="forexExperience" class="chosen-select chosen-transparent form-control" id="forexExperience" style="width: 100%; display: none;">
								<option value="">Forex Experience</option>
								<option value="I've never made a trade in my life!" <?=(isset($error)&&$error>0&&$forexExperience=="I\'ve never made a trade in my life!"?'selected="selected"':'')?>>I've never made a trade in my life!</option>
								<option value="I'm ready to take trading more seriously!" <?=(isset($error)&&$error>0&&$forexExperience=="I\'m ready to take trading more seriously!"?'selected="selected"':'')?>>I'm ready to take trading more seriously!</option>
								<option value="I'm a forex day trader, ready to improve!" <?=(isset($error)&&$error>0&&$forexExperience=="I\'m a forex day trader, ready to improve!"?'selected="selected"':'')?>>I'm a forex day trader, ready to improve!</option>
								<option value="Forex is my life, I just need more signals!" <?=(isset($error)&&$error>0&&$forexExperience=="Forex is my life, I just need more signals!"?'selected="selected"':'')?>>Forex is my life, I just need more signals!</option>
							</select>
						</div>
					</section>
					<hr class="dash-sep">
					<section>
						<div class="input-group">
							<input type="text" class="form-control" name="promocode" id="promocode" placeholder="Coupon Code" <?=(isset($error)&&$error>0?'value="'.$promocode.'"':'')?>>
							<span class="input-group-btn">
								<button class="btn btn-default" id="validatePromoCode" type="button">Validate Coupon</button>
							</span>
						</div>
						<div class="input-group fakeSelect fakeSelectPackage  text-left">
							<select name="package" class="chosen-select chosen-transparent form-control" id="package" style="width: 100%; display: none;">
								<option value="">Package</option>
								<option value="13">Signals - $100/mo.</option><option value="14">Essentials - $200/mo.</option><option value="15">Advanced - $300/mo.</option><option value="19">Grandfathered Package - $300/mo.</option><option value="31">Elite - $3000/mo.</option>                        </select>
						</div>
					</section>
					<hr class="dash-sep">

						<!--div class="row">
							<input type="hidden" name="card_token" id="card_token">
							<button class="btn-danger" id="ValidateCard">Validate Card</button>
						</div-->
					<hr class="dash-sep">
					<section class="">
						<button class="btn btn-greensea" id="doRegistration">Register without paying</button>
					</section>
				</form>
			</div>
		</div>
		<div class="col-md-2"></div>

		<script src="https://code.jquery.com/jquery.js"></script>

		<script src="/assets/js/vendor/bootstrap/bootstrap.min.js"></script>
		<script src="/assets/js/vendor/bootstrap/bootstrap-dropdown-multilevel.js"></script>
		<script type="text/javascript" src="/assets/js/vendor/nicescroll/jquery.nicescroll.min.js"></script>

		<script type="text/javascript" src="/assets/js/vendor/mmenu/js/jquery.mmenu.min.js"></script>
		<script type="text/javascript" src="/assets/js/vendor/sparkline/jquery.sparkline.min.js"></script>
		<script type="text/javascript" src="/assets/js/vendor/nicescroll/jquery.nicescroll.min.js"></script>
		<script type="text/javascript" src="/assets/js/vendor/animate-numbers/jquery.animateNumbers.js"></script>
		<script type="text/javascript" src="/assets/js/vendor/videobackground/jquery.videobackground.js"></script>
		<script type="text/javascript" src="/assets/js/vendor/blockui/jquery.blockUI.js"></script>

		<script src="/assets/js/vendor/summernote/summernote.min.js"></script>

		<script src="/assets/js/vendor/chosen/chosen.jquery.min.js"></script>

		<script src="/assets/js/vendor/momentjs/moment-with-langs.min.js"></script>
		<script src="/assets/js/vendor/datepicker/bootstrap-datetimepicker.min.js"></script>

		<script src="/assets/js/vendor/sweetalert/sweetalert.min.js"></script>
		<script src="/assets/js/vendor/vimeo-upload.js"></script>
		<script src="/assets/js/vendor/dropzone/dropzone.min.js"></script>

		<script src="https://cdn.pubnub.com/sdk/javascript/pubnub.4.3.1.min.js"></script>

		<script src="/assets/js/vendor/video2canvas/video2canvas.js"></script>
		<script src="/assets/js/minimal.js"></script>
		<script src="/assets/js/script.js"></script>


		<script src="/assets/js//vendor/datatables/jquery.dataTables.min.js"></script>
		<script src="/assets/js//vendor/datatables/ColReorderWithResize.js"></script>
		<script src="/assets/js//vendor/datatables/colvis/dataTables.colVis.min.js"></script>
		<script src="/assets/js//vendor/datatables/tabletools/ZeroClipboard.js"></script>
		<script src="/assets/js//vendor/datatables/tabletools/dataTables.tableTools.min.js"></script>
		<script src="/assets/js//vendor/datatables/dataTables.bootstrap.js"></script>
		<script src="/assets/js/vendor/masks/masks.js"></script>
		<!--script src="/assets/js/script_subscriptions.js"></script-->
		<!--script src="/assets/js/script_team.js"></script-->
		<script>
            $(".chosen-select").chosen({disable_search_threshold: 10});

            var flags = ($('#flags').val()).split(','),
                flagIds = ($('#flagsIds').val()).split(','),
                phoneCodes = ($('#phoneCodes').val()).split(',');

            if($('.flags-mobile').data('preselected')!==undefined && $('.flags-mobile').data('preselected')!=''){
                var actualFlag = $('#country').val(),
                    actualId = flagIds.indexOf(actualFlag),
                    flagToUse = flags[actualId],
                    image_url = '/assets/images/flags/'+flagToUse.toLowerCase()+'.png';

                if(actualFlag=='230'){
                    $('#mobile').mask('(000) 000-0000', {placeholder: "(___) ___-____"});
                } else {
                    $('#mobile').unmask().attr('placeholder','');
                }

                $('.flags-mobile').html('<img src="'+image_url+'"><span>'+phoneCodes[actualId]+'</span>');
            }

            if($('#promocode').val()!=''){
                var code = $('#promocode').val();

                if($.trim(code)!='') {

                    if (!$('#validatePromoCode').hasClass('couponApplied')) {

                        $.post('/actions/validateCoupon', {'code': code}, function (data) {
                            var res = data.split('$#');
                            console.log(res);
                            if (res[0] * 1 == 1) {

                                $('#validatePromoCode').addClass('couponApplied').text('Remove Coupon');

                                if (res[1] != 'none') {

                                    $('.fakeSelectPackage').html(res[1]);
                                    $(".chosen-select").chosen({disable_search_threshold: 10});

                                }

                            }
                        });

                    } else {

                        $.post('/actions/regularPackages', {}, function (data) {

                            $('#validatePromoCode').removeClass('couponApplied').text('Validate Coupon');
                            $('#promocode').val('');

                            $('.fakeSelectPackage').html(data);
                            $(".chosen-select").chosen({disable_search_threshold: 10});

                            //swal('success', 'Thank you for validate your coupon.', 'success');

                        });

                    }

                }
            }

            $('body')
	            .on('change', '#country', function(){

	                var actualFlag = $(this).val(),
	                    actualId = flagIds.indexOf(actualFlag),
	                    flagToUse = flags[actualId],
	                    image_url = '/assets/images/flags/'+flagToUse.toLowerCase()+'.png';

	                if(actualFlag=='230'){
	                    $('#mobile').mask('(000) 000-0000', {placeholder: "(___) ___-____"});
	                } else {
	                    $('#mobile').unmask().attr('placeholder','');
	                }

	                $('.flags-mobile').html('<img src="'+image_url+'"><span>'+phoneCodes[actualId]+'</span>');


	            })
                .on('blur', '#promocode', function(e){
                    $('#validatePromoCode').trigger('click');
                })
	            /*.on('click', '#ValidateCard', function(e){
	                e.preventDefault();
	                e.stopPropagation();

                    var stripe = Stripe('');
                    var elements = stripe.elements();

                    var card = elements.create('card');
                    card.mount('#card-element');

                    var promise = stripe.createToken(card);
                    promise.then(function(result) {
                        // result.token is the card token.
                    });

	            })*/
                .on('click', '#validatePromoCode', function(e){
                    e.preventDefault();
                    e.stopPropagation();

                    var code = $('#promocode').val();
					var pckg = $('#package').val();

                    if($.trim(code)!='') {

                        if (!$('#validatePromoCode').hasClass('couponApplied')) {

                            $.post('/actions/validateCoupon', {'code': code, 'package': pckg}, function (data) {
                                var res = data.split('$#');
                                console.log(res);
                                if (res[0] * 1 == 1) {

                                    $('#validatePromoCode').addClass('couponApplied').text('Remove Coupon');

                                    if (res[1] != 'none') {

                                        $('.fakeSelectPackage').html(res[1]);
                                        $(".chosen-select").chosen({disable_search_threshold: 10});

                                        swal('success', 'Thank you for validate your coupon.', 'success');

                                    } else {
                                        $('#promocode').val('');
                                        swal('info', 'None package is available for this coupon.', 'info');
                                    }

                                } else {
                                    swal('error', res[1], 'error');
                                }
                            });

                        } else {

                            $.post('/actions/regularPackages', {}, function (data) {

                                $('#validatePromoCode').removeClass('couponApplied').text('Validate Coupon');
                                $('#promocode').val('');

                                $('.fakeSelectPackage').html(data);
                                $(".chosen-select").chosen({disable_search_threshold: 10});

                                swal('success', 'Thank you for validate your coupon.', 'success');

                            });

                        }

                    }

                })
	            .on('submit', '#form-register-step1', function(e){

                    var fields = true,
                        form = $(this),
                        name = form.find('#name'),
                        email = form.find('#email'),
                        password = form.find('#password'),
                        country = form.find('#country'),
                        mobile = form.find('#mobile'),
                        forexExperience = form.find('#forexExperience'),
                        promocode = form.find('#promocode'),
                        pack = form.find('#package');


                    fields = (validateField(true, name) && fields ? true : false);
                    fields = (validateField(true, email) && fields ? true : false);
                    fields = (validateField(true, password) && fields ? true : false);
                    fields = (validateField(true, country) && fields ? true : false);
                    fields = (validateField(true, mobile) && fields ? true : false);
                    fields = (validateField(true, forexExperience) && fields ? true : false);
                    fields = (validateField(true, pack) && fields ? true : false);


                    if (!fields) {
                        e.preventDefault();
                        e.stopPropagation();
                        swal('error', 'Fix the marked fields.', 'error');
                    } /*else {
                        $.post('/actions/verifyUserEmailForRegistration', {
                            'email': email.val(),
                            'mobile': mobile.val()
                        }, function (data) {

                            var res = data.split('$#');

                            if(res[0]*1==0) {
                                e.preventDefault();
                                e.stopPropagation();
                                swal('error', res[1], 'error');
                            }



                        });
                    }*/
	            })
			;
		</script>
	</body>
</html>
