<?php
 	error_reporting(1);
    require_once("includes/config.php");
	require_once('fedex/fedex-common.php5');
	require_once('includes/xml2array.php');
	require_once('usps/USPSTrackConfirm.php');
	
	

	class ShipmentTrackingApi{

		public function validateInput($action){
			
			//****************
			/* @parameter: $action is string
			/* @return : true if validation is success or error message
			****************/
			$check_keys = array_keys($_REQUEST);
			switch($action){
				case 'createUser':
					$check_for = array('name', 'password', 'email','sq', 'sqhint','deviceToken');
					if(!empty($_REQUEST['name']) && !empty($_REQUEST['password']) && !empty($_REQUEST['email']) && !empty($_REQUEST['sq']) && !empty($_REQUEST['sqhint']) && !empty($_REQUEST['deviceToken'])){
						$rtnval = true;
					}else{
						$rtnval = "Invalid parameters supplied. Provide valid input ". implode(', ', array_diff($check_for, $check_keys));
					}
				break;
				case 'authenticateUser':
 					$check_for = array('password', 'email','deviceToken');
					if(!empty($_REQUEST['password']) && !empty($_REQUEST['email']) && !empty($_REQUEST['deviceToken'])){
						$rtnval = true;
					}else{
						$rtnval = "Invalid parameters supplied. Provide valid input ". implode(', ', array_diff($check_for, $check_keys));
					}
				break;
				case 'selectShipmentType':
 					$check_for = array('shipType', 'trackno', 'userid', 'lastTracktime');
					if(!empty($_REQUEST['shipType']) && !empty($_REQUEST['trackno']) && !empty($_REQUEST['userid'])){
						$rtnval = true;
					}else{
						$rtnval = "Invalid parameters supplied. Provide valid input ". implode(', ', array_diff($check_for, $check_keys));
					}
				break;
				case 'getAllTrackDetails':
 					$check_for = array('userid','lastTracktime');
					if(!empty($_REQUEST['userid']) && !empty($_REQUEST['lastTracktime'])){
						$rtnval = true;
					}else{
						$rtnval = "Invalid parameters supplied. Provide valid input ". implode(', ', array_diff($check_for, $check_keys));
					}
				break;
				case 'updateSettings':
 					$check_for = array('userid');
					if(!empty($_REQUEST['userid'])){
						$rtnval = true;
					}else{
						$rtnval = "Invalid parameters supplied. Provide valid input ". implode(', ', array_diff($check_for, $check_keys));
					}
				break;
				case 'updateMyprofile':
 					$check_for = array('userid');
					if(!empty($_REQUEST['userid'])){
						$rtnval = true;
					}else{
						$rtnval = "Invalid parameters supplied. Provide valid input ". implode(', ', array_diff($check_for, $check_keys));
					}
				break; 
				case 'forgotPassword':
 					$check_for = array('email', 'sqhint');
					if(!empty($_REQUEST['email']) && !empty($_REQUEST['sqhint'])){
						$rtnval = true;
					}else{
						$rtnval = "Invalid parameters supplied. Provide valid input ". implode(', ', array_diff($check_for, $check_keys));
					}
				break;
				case 'changePass':
 					$check_for = array('newPass', 'passHint','userId');
					if(!empty($_REQUEST['newPass']) && !empty($_REQUEST['passHint']) && !empty($_REQUEST['userId'])){
						$rtnval = true;
					}else{
						$rtnval = "Invalid parameters supplied. Provide valid input ". implode(', ', array_diff($check_for, $check_keys));
					}
				break;
				case 'updatePackageName':
					$check_for = array('type', 'trackno','packagename','userid');
					if(!empty($_REQUEST['type']) && !empty($_REQUEST['trackno']) && !empty($_REQUEST['packagename']) && !empty($_REQUEST['userid'])){
						$rtnval = true;
					}else{
						$rtnval = "Invalid parameters supplied. Provide valid input ". implode(', ', array_diff($check_for, $check_keys));
					}

				break;
				default:
					$rtnval = 'Invalid request';

			}

			return $rtnval;
	
		}

		public function emailPushNotification($USERID,$TRACKID,$TRACKSTATUS,$TRACK_INS_ID=""){

			global  $G_dbconn, $G_dbdatetime;

			if(!empty($TRACK_INS_ID)){
				$prepack_qry = $G_dbconn->prepare('SELECT * FROM tbl_tracking WHERE trackid = :trackid');
				$prepack_qry->execute(array(':trackid'=>$TRACK_INS_ID));
				$row_count = $prepack_qry->rowCount();
				if($row_count>0){
					$fetch_data = $prepack_qry->fetch(PDO::FETCH_ASSOC);
					$get_ship_type		=	$fetch_data['ship_type']; 
					$get_packagename	=	$fetch_data['packagename'];
					$get_status			=	$fetch_data['status'];
				}
			}else{
				$get_ship_type		=	""; 
				$get_packagename	=	"";
				$get_status			=	"";
			}

			$prep_qry = $G_dbconn->prepare('SELECT * FROM tbl_users WHERE userid = :userid');
			$prep_qry->execute(array(':userid'=>$USERID));
			$row_count = $prep_qry->rowCount();
			if($row_count>0){
				$fetch_data = $prep_qry->fetch(PDO::FETCH_ASSOC);
				$get_email        = $fetch_data['email']; 
				$get_email_notify = $fetch_data['enable_email_notification'];
				$get_push_notify  = $fetch_data['enable_push_notification'];
				$get_device_token = $fetch_data['device_token'];

				if($get_push_notify=="yes"){

					$this->pushNotification($TRACKID,$TRACKSTATUS,$get_packagename,$get_device_token);
				}

				if($get_email_notify=="yes"){
					
					$this->emailNotification($get_email,$TRACKID,$TRACKSTATUS,$get_packagename,$get_ship_type);
				}
			}


		
		}

		public function pushNotification($track_id,$track_status,$package_name="",$get_device_token){
			ob_start();
			$chs = curl_init(); 
 
			$arrs = array();
			array_push($arrs, "X-Parse-Application-Id: UyszakBcO8kmYUGmLaV5TXDAiCJ3q8ZnZwO7m3Xo");
			array_push($arrs, "X-Parse-REST-API-Key: 85CUr7a8srpRbAa7Fn2CAmMKd7OPJNWPRbMNu5Cq");
			array_push($arrs, "Content-Type: application/json");

			$unique_user	=	array(
                  'deviceType' => 'ios',
                  'deviceToken' => $get_device_token,
                );
			 
				
				// f583085122c060d0edd9da45e86e9ba9ebfa8ef4b6252503696cce18376918a7   ----- iPad token.
				//$alert = "Your tracking number " .$tnt_track_id. " has been ".$tnt_track_status;
				
			curl_setopt($chs, CURLOPT_HTTPHEADER, $arrs);
			//curl_setopt($chs, CURLOPT_HEADER, false);
			curl_setopt($chs, CURLOPT_URL, 'https://api.parse.com/1/push');
			curl_setopt($chs, CURLOPT_POST, true);
			curl_setopt($chs, CURLOPT_POSTFIELDS, '{ "where": {"deviceType":"ios","deviceToken":"'.$get_device_token.'"},"data": { "alert": "Number#'.$track_id .', Status: '.$track_status.', Package Name: '.$package_name.'!" } }');

			
			 
			$res=curl_exec($chs);
			curl_close($chs);
			ob_clean();
		}

		public function emailNotification($get_email,$track_id,$track_status,$track_packagename,$track_ship_type){

			
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= "From: Tracking Shipment App<noreply@example.com>\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$subject  ="Tracking Details";	
			$message  ="Dear User,<br><br> Here are the tracking details on your package<br>";
			$message.="<table><tr><td>Package Carrier :</td><td>{$track_ship_type}</td></tr>";
			if(!empty($track_packagename)){
				$message.="<tr><td>Package Name :</td><td>{$track_packagename}</td></tr>";
			}
			$message.="<tr><td>Tracking Number :</td><td>{$track_id}</td></tr>";
			$message.="<tr><td>Package Status/ Update:</td><td>{$track_status}</td></tr>";
			$message.="</table>";
			$message.="<br>";
			$message.="Thanks & Regards<br>Tracking Support Team";

			mail($get_email, $subject, $message, $headers);
				
		}

		public function checkUserExists(){
			//****************
			/* @parameter: 
			/* @return : true if user exists else false
			****************/

			global  $G_dbconn, $G_dbdatetime;
			$email = trim($_REQUEST['email']);

			$prep_qry = $G_dbconn->prepare('select count(*) as cnt from tbl_users where email = :uemail');
			$prep_qry->execute(array(':uemail'=>$email));
			if($prep_qry->fetchColumn() > 0){
				return true;
			}else
				return false;
		}


		public function getUserDetails(){
			//****************
			/* @parameter:  
			/* @return : user details array if user exists else false
			****************/

			global  $G_dbconn, $G_dbdatetime;
			$email = trim($_REQUEST['email']);
			$password = sha1($_REQUEST['password']);

 			$prep_qry = $G_dbconn->prepare('select userid, name, email, enable_push_notification, enable_email_notification,security_question from tbl_users where email = :uemail and password = :upassword');
			$prep_qry->execute(array(':uemail'=>$email, ':upassword'=>$password));
			if($prep_qry->rowCount() > 0){
				return $prep_qry->fetch(PDO::FETCH_ASSOC);
			}else
				return false;
		}

		public function getUserSecurityQuestion(){
			//****************
			/* @parameter :  
			/* @return : user details array if user exists else false
			****************/
 			global  $G_dbconn, $G_dbdatetime;
			if(!empty($_REQUEST['email'])){
				$email = trim($_REQUEST['email']);
				$prep_qry = $G_dbconn->prepare('select security_question from tbl_users where email = :uemail');
				$prep_qry->execute(array(':uemail'=>$email));
				if($prep_qry->rowCount() > 0){
					$secq = $prep_qry->fetch(PDO::FETCH_ASSOC);
					if(is_numeric($secq['security_question']) && strlen($secq['security_question']) >= 3 ){
						$get_ques = $G_dbconn->prepare('SELECT question FROM tbl_security_questions WHERE ques_id = :qid');
						$get_ques->execute(array(':qid'=> $secq['security_question']));
						if($get_ques->rowCount()>0){
							$ques_arr = $get_ques->fetch(PDO::FETCH_ASSOC);
							$return_arr['error_code'] = 0;
							$return_arr['question'] = stripslashes($ques_arr['question']);
						}
					}else{
						$return_arr['error_code'] = 0;
						$return_arr['question'] = stripslashes($secq['security_question']);
					}
				}else{
					$return_arr['error_code'] = 1;
					$return_arr['error_msg'] = "Invalid Email Address.";
				}
			}else{
					$return_arr['error_code'] = 1;
					$return_arr['error_msg'] = "Email address is required";
			}

			$this->printErrorMessage($return_arr);
		}

		public function checkPackageNameExists(){
			//****************
			/* @parameter: 
			/* @return : true if user exists else false
			****************/

			global  $G_dbconn, $G_dbdatetime;
			$trackno = trim($_REQUEST['trackno']);

			$prep_qry = $G_dbconn->prepare('select count(*) as cnt from tbl_tracking where trackno = :utrackno');
			$prep_qry->execute(array(':utrackno'=>$trackno));
			if($prep_qry->fetchColumn() > 0){
				return true;
			}else
				return false;
		}


		public function updatePackageName(){
			
			global  $G_dbconn, $G_dbdatetime;
			$isvalid = $this->validateInput('updatePackageName');
			if($isvalid === true){
				$get_track_num		=	trim($_REQUEST['trackno']);
				$get_package_name	=	trim($_REQUEST['packagename']);
				$get_user_id		=	trim($_REQUEST['userid']);
				$return_arr			=	array();

				if($this->checkPackageNameExists() === true){
					

					$pre_update_query = $G_dbconn->prepare('UPDATE tbl_tracking SET packagename=:packagename,modified_date=:currentdate WHERE trackno=:trackno and userid=:userid');

					$check_qry = $pre_update_query->execute(
											array(
													':packagename'=> $get_package_name,
													':trackno'=>$get_track_num,
													':userid'=>$get_user_id,
													':currentdate'=> $G_dbdatetime
												 )
											);
					if($check_qry){
						$return_arr['error_code'] = 0;
						$return_arr['error_msg']  = "Updated Successfully";
					}
					
				}else{
					$return_arr['error_code'] = 1;
					$return_arr['error_msg']  = "Invalid Track Number";
				}

				$this->printErrorMessage($return_arr);
			}

		}

		public function createUser(){
			//****************
			/* @parameter:  
			/* @return : user details array if user is created successfully else error message
			****************/

			global  $G_dbconn, $G_dbdatetime;
			$isvalid = $this->validateInput('createUser');
 			if($isvalid === true){ 
				if($this->checkUserExists() === false){
					if(is_numeric(trim($_REQUEST['sq']))){
						$pre_sq = $G_dbconn->prepare('SELECT ques_id FROM tbl_security_questions WHERE 	ques_id = :qid');
						$pre_sq->execute(array(':qid'=> trim($_REQUEST['sq'])));
						if($pre_sq->rowCount()>0){
							$set_ques['question'] =  trim($_REQUEST['sq']);
						}else{
							$return_arr['error_code'] = 1;
							$return_arr['error_msg'] = "Invalid security question.";
						}
					}else{
						$set_ques['question'] =  trim($_REQUEST['sq']);
					}
					$set_ques['hintanswer'] =  trim($_REQUEST['sqhint']);
					if(empty($return_arr['error_code'])){
						$prequery = $G_dbconn->prepare('INSERT INTO tbl_users(name, email, password, device_token, security_question, security_hint_answer, created_date, modified_date) values(:username, :useremail, :password, :device_token, :sec_ques, :sec_answer, :currentdate, :currentdate)');
						$check_qry = $prequery->execute(
											array(
													':username'=> trim($_REQUEST['name']),
													':useremail'=> trim($_REQUEST['email']), 
													':password'=> sha1($_REQUEST['password']),
													':device_token'=> trim($_REQUEST['deviceToken']),
													':sec_ques'=> addslashes($set_ques['question']),
													':sec_answer'=> addslashes($set_ques['hintanswer']),
													':currentdate'=> $G_dbdatetime
												 )
											);
						if($check_qry){
							$return_arr['error_code'] = 0;
							$return_arr['userid'] = $G_dbconn->lastInsertId();
							$return_arr['name'] = trim($_REQUEST['name']);
                            $return_arr['question'] =  trim($_REQUEST['sq']);
						}else{
							$return_arr['error_code'] = 1;
							$return_arr['error_msg'] = "Unable to process your request";
						}
					}
				}else{
					$return_arr['error_code'] = 1;
 					$return_arr['error_msg'] = 'User already exists';
				}
			}else{
				$return_arr['error_code'] = 1;
 				$return_arr['error_msg'] = $isvalid;
			}
			$this->printErrorMessage($return_arr);
		}
		
		public function authenticateUser(){
			//****************
			/* @Description: Get user details from DB using Email address and password from query string 
			/* @parameter:  
			/* @return : user details array if email and password macthes else error message
			****************/

			global  $G_dbconn, $G_dbdatetime;
			$isvalid = $this->validateInput('authenticateUser');
 			if($isvalid === true){ 
				$user_data = $this->getUserDetails();
				if($user_data){
                    $quest_value =$user_data['security_question'];
                    
                    $pre_sq = $G_dbconn->prepare('SELECT question FROM tbl_security_questions WHERE 	ques_id = :qid');
                    $pre_sq->execute(array(':qid'=> $quest_value));
                    if($pre_sq->rowCount()>0){
                        $ques_arr = $pre_sq->fetch(PDO::FETCH_ASSOC);
                        $return_arr['question'] = stripslashes($ques_arr['question']);
                    }
                    
                    $update_user_id = $user_data['userid'];
					$pre_update_query = $G_dbconn->prepare('UPDATE tbl_users SET device_token=:device_token,modified_date=:currentdate WHERE userid=:userid');
					$check_qry = $pre_update_query->execute(
										array(
												':userid'=> $update_user_id,
												':device_token'=>trim($_REQUEST['deviceToken']),
												':currentdate'=> $G_dbdatetime
											 )
										);

					$return_arr['error_code'] = 0;
					$return_arr['userid'] = $user_data['userid'];
					$return_arr['name'] = $user_data['name'];
					$return_arr['email'] = $user_data['email'];
					$return_arr['device_token'] = $user_data['device_token'];
					$return_arr['push_notify'] = $user_data['enable_push_notification'];
					$return_arr['email_notify'] = $user_data['enable_email_notification'];

				}else{
					$return_arr['error_code'] = 1;
 					$return_arr['error_msg'] = 'Invalid email or password';
				}
			}else{
				$return_arr['error_code'] = 1;
 				$return_arr['error_msg'] = $isvalid;
			}

			$this->printErrorMessage($return_arr);
	
		}

		public function selectShipmentType(){
			//****************
			/* @Description: Select ship type from the query string type 
			/* @parameter:  
			/* @return : 
			****************/

			global  $G_dbconn, $G_dbdatetime;
			$vendors = array('fedex','ups','usps','tnt','dhl','canada_post');

			$isvalid = $this->validateInput('selectShipmentType');
			if($isvalid === true){
				$vendor = trim(strtolower($_REQUEST['shipType']));
				if(in_array($vendor, $vendors)){
					switch($vendor){
						case 'ups':
							$this->trackUpsShippment();
						break;
						case 'fedex':
							$this->trackFedexShippment();
						break;
						case 'usps':
							$this->trackUspsShippment();
						break;
						case 'tnt':
							$this->trackTntShippment();
						break;
						case 'dhl':
							$this->trackDhlShippment();
						break;
						case 'canada_post':
							$this->trackCanadaPostShippment();
						break;
					}
				}else{
					$return_arr['error_code'] = 1;
 					$return_arr['error_msg'] = 'Invalid tracking vendor selected';
				}
			}else{
				$return_arr['error_code'] = 1;
 				$return_arr['error_msg'] = $isvalid;
			}
			if(!empty($return_arr))
				$this->printErrorMessage($return_arr);
		}

		public function checkTrackExists(){
			//****************
			/* @parameter: 
			/* @return : true if trackno,userid,ship_type exists else false
			****************/

			global  $G_dbconn, $G_dbdatetime;
			$trackno	= trim($_REQUEST['trackno']);
			$ship_type	= trim($_REQUEST['shipType']);
			$userid		= trim($_REQUEST['userid']);

			$trk_prep_qry = $G_dbconn->prepare('select count(*) as cnt,trackid from tbl_tracking where trackno = :trackno and userid = :userid and ship_type = :ship_type');
			$trk_prep_qry->execute(array(':trackno'=>$trackno,':userid'=>$userid,':ship_type'=>$ship_type));
			$fet_data = $trk_prep_qry->fetch();

			$trk_count = $fet_data['cnt'];
			$trk_id   = $fet_data['trackid'];

			
			if($trk_count > 0){
				return $trk_id;
			}else{
				return false;
			}
		}

		public function insertTrackStatus($trackdata){
			//****************
			/* @Description: Insert all the tracking details into tbl_tracking
			/* @parameter:  $trackdata is array 
			/* @return : true if data is inserted to DB else false
			****************/

			global $G_dbconn, $G_dbdatetime;
			$prequery = $G_dbconn->prepare('INSERT INTO tbl_tracking(userid, ship_type, trackno, status, destination, service, arrivaltime, deptime, lastlocation, created_date, modified_date) VALUES(:userid, :shiptype, :trackno, :status, :destination, :service, :arrivetime, :deptime, :lastlocation, :created_date, :modified_date)');
 			$check_qry = $prequery->execute(
								array(
										':userid'=> $trackdata['userid'],
										':shiptype'=> $trackdata['type'],
										':trackno'=> $trackdata['trackno'],
										':status'=> $trackdata['status'],
										':destination'=> $trackdata['destination'],
										':service'=> $trackdata['service'],
										':arrivetime'=> date('Y-m-d H:i:s', strtotime($trackdata['arrtime'])),
										':deptime'=> date('Y-m-d H:i:s', strtotime($trackdata['deptime'])),
										':lastlocation'=> $trackdata['lastlocation'],
										':created_date'=> $G_dbdatetime,
										':modified_date'=> $G_dbdatetime
									 )
								);
			if($check_qry){
				return $G_dbconn->lastInsertId();
			}else
				return false;
		}


		public function updateTrackStatus($trackdata,$track_update_id){
			//****************
			/* @Description: Insert all the tracking details into tbl_tracking
			/* @parameter:  $trackdata is array 
			/* @return : true if data is inserted to DB else false
			****************/

			global $G_dbconn, $G_dbdatetime;
			$prequery = $G_dbconn->prepare('UPDATE tbl_tracking SET status=:status, destination=:destination, service=:service, arrivaltime=:arrivetime, deptime=:deptime, lastlocation=:lastlocation, modified_date=:modified_date WHERE trackid=:trackid');
 			$check_qry = $prequery->execute(
								array(
										':status'=> $trackdata['status'],
										':destination'=> $trackdata['destination'],
										':service'=> $trackdata['service'],
										':arrivetime'=> date('Y-m-d H:i:s', strtotime($trackdata['arrtime'])),
										':deptime'=> date('Y-m-d H:i:s', strtotime($trackdata['deptime'])),
										':lastlocation'=> $trackdata['lastlocation'],
										':modified_date'=> $G_dbdatetime,
										':trackid'=> $track_update_id,
										
									 )
								);
			if($check_qry){

				$pregetquery	= $G_dbconn->prepare('SELECT * FROM tbl_tracking WHERE trackid=:trackid');
 				$check_get_qry	= $pregetquery->execute(array(':trackid'=> $track_update_id));
				if($check_get_qry){
					$fet_get_pack		=	$pregetquery->fetch();
					return $get_packagename	=	$fet_get_pack['packagename'];
				}

				//return true;
			}else
				return false;
		}

		public function getAllTrackDetails(){
			//****************
			/* @Description: Get all tracking details from Database
			/* @parameter:  $trackdata is array 
			/* @return : true if data is inserted to DB else false
			****************/

			global $G_dbconn;
			
			$isvalid = $this->validateInput('getAllTrackDetails');
			if($isvalid === true){
				$userid = trim($_REQUEST['userid']);
				$return_arr['error_code'] = 0;
				$lasttimestamp = trim($_REQUEST['lastTracktime']);
				$main_track_data = array();
				$prep_qry = $G_dbconn->prepare('SELECT * FROM tbl_tracking WHERE userid = :uid AND UNIX_TIMESTAMP(created_date) >= :credate');
				$prep_qry->execute(array(':uid'=>$userid, ':credate'=>$lasttimestamp));
				if($prep_qry->rowCount() > 0){
					
					while($list_data = $prep_qry->fetch(PDO::FETCH_ASSOC)){
						$track_data = array();
						$track_data['userid'] = $list_data['userid'];
						$track_data['type'] = $list_data['ship_type'];
						$track_data['trackno'] = $list_data['trackno'];
						$track_data['packagename'] = $list_data['packagename'];
						$track_data['status'] = $list_data['status'];
						$track_data['destination'] = $list_data['destination'];
						$track_data['service'] = $list_data['service'];
						$track_data['arrtime'] = date("M j, Y, H:i:s", $list_data['arrivaltime']);
						$track_data['deptime'] = date("M j, Y, H:i:s", $list_data['deptime']);
						$track_data['lastlocation'] = $list_data['lastlocation'];
						$track_data['trackid'] = $list_data['trackid'];
						$main_track_data[] = $track_data;
					}
					$return_arr['tracklist']  = $main_track_data;
				}else{
					$return_arr['error_code'] = 1;
 					$return_arr['error_msg'] = 'No records found for the user';
				}
			}else{
				$return_arr['error_code'] = 1;
 				$return_arr['error_msg'] = $isvalid;
			}

			$this->printErrorMessage($return_arr);
		}

		public function trackUpsShippment(){
				
				//****************
				/* @Description: To get UPS tracking detials
				/* @parameter:   
				/* @return :  
				****************/

				global $G_upscredentials;

				$ups_track_no = trim($_REQUEST['trackno']);
				$track_userid = trim($_REQUEST['userid']);

				$xmlfile = file_get_contents('xml/ups.xml', true);
				$to_replace = array('{{licencenumber}}', '{{userid}}', '{{password}}', '{{tracknumber}}');
				if(!empty($G_upscredentials['licensekey']) && !empty($G_upscredentials['userid']) && !empty($G_upscredentials['password'])){
					$replace_with = $G_upscredentials;
					$replace_with['tracknumber'] = trim($_REQUEST['trackno']);
					$xmldata = str_replace($to_replace, $replace_with, $xmlfile);
					//$track_url = "https://www.ups.com/ups.app/xml/Track"; //for live
					$track_url = "https://wwwcie.ups.com/ups.app/xml/Track"; //for testing
					$data = $this->curlPost($track_url, $xmldata);
					
 					 if($data !== false ){
						 $vals = xml2array( $data);
						
   						 if(!empty($vals['TrackResponse']['Response']['ResponseStatusCode']) && $vals['TrackResponse']['Response']['ResponseStatusCode'] == 1){
							 //ResponseStatusCode 1 = success , 0 = failure
							$si=0;

							if(empty($vals['TrackResponse']['Shipment']['Package']['Activity']['ActivityLocation'])){
								
								 $return_arr['error_code'] = 0;
								 $track_data['userid'] = trim($_REQUEST['userid']);
								 
								 $track_data['trackno'] = $vals['TrackResponse']['Shipment']['Package']['TrackingNumber'];
								
								 $track_data['type']    = 'ups';
								 $activity_arr = end($vals['TrackResponse']['Shipment']['Package']['Activity']); //get only last activity
								 $package_weight = !empty($vals['TrackResponse']['Shipment']['Package']['PackageWeight']['Weight'])?sprintf('%s %s', $vals['TrackResponse']['Shipment']['Package']['PackageWeight']['Weight'], $vals['TrackResponse']['Shipment']['Package']['PackageWeight']['UnitOfMeasurement']['Code']):'N/A';
								 $track_data['package_weight'] = $package_weight;
								 $track_data['package_dimensions'] = 'N/A';

								if($vals['TrackResponse']['Shipment']['Package']['Activity']['Status']['StatusType']['Description'] != ""){
									$record_status = $vals['TrackResponse']['Shipment']['Package']['Activity']['Status']['StatusType']['Description'];
									
								}else{
									$record_status = "pending";
									
								}
								 
								 $track_data['status'] = $record_status;
								 $track_data['service'] = $vals['TrackResponse']['Shipment']['Service']['Description'];

								$ups_city = $vals['TrackResponse']['Shipment']['Package']['Activity'][0]['ActivityLocation']['Address']['City'];

								$ups_state = $vals['TrackResponse']['Shipment']['Package']['Activity'][0]['ActivityLocation']['Address']['StateProvinceCode'];

								$ups_country = $vals['TrackResponse']['Shipment']['Package']['Activity'][0]['ActivityLocation']['Address']['CountryCode'];

								 $usp_track_loc = sprintf('City:%s, StateProvinceCode:%s, Country:%s', $ups_city,$ups_state,$ups_country);

								 $track_data['lastlocation'] = $usp_track_loc;

								$evnt_act_date					=	date("M j, Y",strtotime($vals['TrackResponse']['Shipment']['Package']['Activity'][0]['Date']));

								$explode_date= explode(",",$evnt_act_date);
								$date_month	= $explode_date[0];
								$year		= $explode_date[1];
								

								$evnt_act_time	=	$vals['TrackResponse']['Shipment']['Package']['Activity'][0]['Time'];
										$evnt_act_time_hr	=	date("H", strtotime($evnt_act_time));
										$evnt_act_time_min	=	date("i", strtotime($evnt_act_time));
										$evnt_act_time_sec	=	date("s", strtotime($evnt_act_time));

								$arr_time = sprintf('%s, %04d %02d:%02d:%02d', $date_month,$year, $evnt_act_time_hr, $evnt_act_time_min, $evnt_act_time_sec);
								
								if(!empty($vals['TrackResponse']['Shipment']['Package']['Activity'][0]['Date'])){
									//echo "comes in time";
									$track_data['arrtime']		=	$arr_time;
								}else{
									$track_data['arrtime']		=	"";
									
								}
								$track_data['deptime']			= "";

								$track_data['destination'] = $usp_track_loc;
								$track_data['devlivery']   = $usp_track_loc;

								$track_data['trackhistory'] = array();
							 

								foreach($vals['TrackResponse']['Shipment']['Package']['Activity'] as $key){
									
									if($si>0){
										$track_address				=	array();

										$hist_ups_city = $key['ActivityLocation']['Address']['City'];
										$hist_ups_state = $key['ActivityLocation']['Address']['StateProvinceCode'];
										$hist_ups_country = $key['ActivityLocation']['Address']['CountryCode'];

										$hist_usp_track_loc = sprintf('City:%s, StateProvinceCode:%s, Country:%s', $hist_ups_city,$hist_ups_state,$hist_ups_country);

										$track_address['location'] = $hist_usp_track_loc;


										$track_address['activity']	=	$key['Status']['StatusType']['Description'];

										if($key['Date'] != ""){
											$act_date					=	date("M j, Y",strtotime($key['Date']));

											//$evnt_act_date		=	date("M j, Y");
											$actexplode_date= explode(",",$act_date);
											$actdate_month	= $actexplode_date[0];
											$actyear		= $actexplode_date[1];
										}else{
											$actdate_month	= "";
											$actyear		= "";
										}

										if($key['Time'] != ""){
											$act_time					=	$key['Time'];

											$act_time_hr	=	date("H", strtotime($act_time));
											$act_time_min	=	date("i", strtotime($act_time));
											$act_time_sec	=	date("s", strtotime($act_time));
										}else{
											$act_time_hr	=	"";
											$act_time_min	=	"";
											$act_time_sec	=	"";
										
										}

										if($key['Date'] != ""){

											$track_address['tdate']		=	sprintf('%s, %04d %02d:%02d:%02d',$actdate_month,$actyear , $act_time_hr, $act_time_min, $act_time_sec);
										}else{
											$track_address['tdate']		= "";
										}

										$track_data['trackhistory'][] = $track_address;
									}
									
									$si++;
								}
							}else{

								 $return_arr['error_code'] = 0;
								 $track_data['userid'] = trim($_REQUEST['userid']);
								 
								 $track_data['trackno'] = $vals['TrackResponse']['Shipment']['Package']['TrackingNumber'];
								
								 $track_data['type']    = 'ups';
								 $activity_arr = end($vals['TrackResponse']['Shipment']['Package']['Activity']); //get only last activity
								 $package_weight = !empty($vals['TrackResponse']['Shipment']['Package']['PackageWeight']['Weight'])?sprintf('%s %s', $vals['TrackResponse']['Shipment']['Package']['PackageWeight']['Weight'], $vals['TrackResponse']['Shipment']['Package']['PackageWeight']['UnitOfMeasurement']['Code']):'N/A';
								 $track_data['package_weight'] = $package_weight;
								 $track_data['package_dimensions'] = 'N/A';

								if($vals['TrackResponse']['Shipment']['Package']['Activity']['Status']['StatusType']['Description'] != ""){
									$record_status = $vals['TrackResponse']['Shipment']['Package']['Activity']['Status']['StatusType']['Description'];
									
								}else{
									$record_status = "pending";
									
								}
								 
								 $track_data['status'] = $record_status;
								 $track_data['service'] = $vals['TrackResponse']['Shipment']['Service']['Description'];

								$ups_city = $vals['TrackResponse']['Shipment']['Package']['Activity']['ActivityLocation']['Address']['City'];

								$ups_state = $vals['TrackResponse']['Shipment']['Package']['Activity']['ActivityLocation']['Address']['StateProvinceCode'];

								$ups_country = $vals['TrackResponse']['Shipment']['Package']['Activity']['ActivityLocation']['Address']['CountryCode'];

								 $usp_track_loc = sprintf('City:%s, StateProvinceCode:%s, Country:%s', $ups_city,$ups_state,$ups_country);

								 $track_data['lastlocation'] = $usp_track_loc;

								$evnt_act_date					=	date("M j, Y",strtotime($vals['TrackResponse']['Shipment']['Package']['Activity']['Date']));

								$explode_date= explode(",",$evnt_act_date);
								$date_month	= $explode_date[0];
								$year		= $explode_date[1];
								

								$evnt_act_time	=	$vals['TrackResponse']['Shipment']['Package']['Activity']['Time'];
										$evnt_act_time_hr	=	date("H", strtotime($evnt_act_time));
										$evnt_act_time_min	=	date("i", strtotime($evnt_act_time));
										$evnt_act_time_sec	=	date("s", strtotime($evnt_act_time));

								$arr_time = sprintf('%s, %04d %02d:%02d:%02d', $date_month,$year, $evnt_act_time_hr, $evnt_act_time_min, $evnt_act_time_sec);
								
								if(!empty($vals['TrackResponse']['Shipment']['Package']['Activity']['Date'])){
									$track_data['arrtime']		=	$arr_time;
								}else{
									$track_data['arrtime']		=	"";
									
								}
								$track_data['deptime']			= "";

								$track_data['destination'] = $usp_track_loc;
								$track_data['devlivery']   = $usp_track_loc;

							}

							$track_update_id = $this->checkTrackExists();

							if($track_update_id === false){
								$track_ins_id = $this->insertTrackStatus($track_data);
								if($track_ins_id !==false){
									$track_data['trackid'] = $track_ins_id;
									$this->emailPushNotification($track_userid,$ups_track_no,$record_status);
								}
							}else{
									$track_package_name		=	$this->updateTrackStatus($track_data,$track_update_id);
									$track_data['trackid']	=	$track_update_id;
									$track_data['packagename']	=	$track_package_name;	

									$this->emailPushNotification($track_userid,$ups_track_no,$record_status,$track_update_id);
							}

							$return_arr['tracklist'][] = $track_data;

						 }else{		
								$error_msg = !empty($vals['TrackResponse']['Response']['Error']['ErrorDescription'])?$vals['TrackResponse']['Response']['Error']['ErrorDescription']:'UPS unable to process your request';
								$return_arr['error_code'] = 1;
 								$return_arr['error_msg'] = $error_msg;
						 }
					 }else{
						$return_arr['error_code'] = 1;
 						$return_arr['error_msg'] = 'Unable to access UPS API';
					 }
				}else{
					$return_arr['error_code'] = 1;
 					$return_arr['error_msg'] = 'Missing UPS configuration';
				}
				
				$this->printErrorMessage($return_arr);

				

		}

		public function trackFedexShippment(){
			
			    //****************
				/* @Description: To get FEDEX tracking detials
				/* @parameter:   
				/* @return :  
				****************/

				global $G_fedexcredentials;

				$fedex_track_no = trim($_REQUEST['trackno']);
				$track_userid = trim($_REQUEST['userid']);

				if(!empty($G_fedexcredentials['key']) && !empty($G_fedexcredentials['password']) && !empty($G_fedexcredentials['shipaccount']) && !empty($G_fedexcredentials['meter'])){
					$path_to_wsdl = "fedex/TrackService_v9.wsdl";

					$client = new SoapClient($path_to_wsdl, array('trace' => 1));  
					
					$request['WebAuthenticationDetail'] = array('UserCredential' =>array('Key' => $G_fedexcredentials['key'], 'Password' => $G_fedexcredentials['password']));
					$request['ClientDetail'] = array('AccountNumber' => $G_fedexcredentials['shipaccount'], 'MeterNumber' => $G_fedexcredentials['meter']);
					//$request['TransactionDetail'] = array('CustomerTransactionId' => 'Track By Number_v9');
					$request['Version'] = array('ServiceId' => 'trck', 'Major' => '9', 'Intermediate' => '1', 'Minor' => '0');
 					$request['SelectionDetails'] = array('PackageIdentifier'  => 'FDXE','PackageIdentifier'  => array('Type'  => 'TRACKING_NUMBER_OR_DOORTAG','Value' => trim($_REQUEST['trackno'])));
					
					try
					{
						$response = $client ->track($request);
						// print_r($response);
 						if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR')
						{
							if(!empty($response->CompletedTrackDetails->TrackDetails->StatusDetail)){
								if($response->CompletedTrackDetails->TrackDetails->Notification->Severity == 'SUCCESS'){
									$return_arr['error_code'] = 0;
									$track_data['userid'] = trim($_REQUEST['userid']);
									$description = $response->CompletedTrackDetails->TrackDetails->StatusDetail->Description;
									$desitnation_address = $response->CompletedTrackDetails->TrackDetails->DestinationAddress;
									$delivery_address = $response->CompletedTrackDetails->TrackDetails->ActualDeliveryAddress;
									$track_data['trackno'] = $response->CompletedTrackDetails->TrackDetails->TrackingNumber;
									$service = $response->CompletedTrackDetails->TrackDetails->Service->Description;
									if(!empty($response->CompletedTrackDetails->TrackDetails->PackageWeight->Value))
										$package_weight = sprintf('%s %s', $response->CompletedTrackDetails->TrackDetails->PackageWeight->Value, $response->CompletedTrackDetails->TrackDetails->PackageWeight->Units);
									else
										$package_weight = 'N/A';

									if(!empty($response->CompletedTrackDetails->TrackDetails->PackageDimensions->Length))
										$package_dimensions = sprintf('L=%s, W=%s, H=%s(%s)', $response->CompletedTrackDetails->TrackDetails->PackageDimensions->Length, $response->CompletedTrackDetails->TrackDetails->PackageDimensions->Width, $response->CompletedTrackDetails->TrackDetails->PackageDimensions->Height, $response->CompletedTrackDetails->TrackDetails->PackageDimensions->Units);
									else
										$package_dimensions = 'N/A';

									$track_data['package_weight'] = $package_weight;
									$track_data['package_dimensions'] = $package_dimensions;
									$track_data['type'] = 'fedex';

									if($description != ""){
										$record_status = $description;
									 }else{
										 $record_status = "pending";
									 }

									$track_data['status'] = $record_status;

									$track_data['service'] = $service; 
									$currentlocation = $response->CompletedTrackDetails->TrackDetails->StatusDetail->Location;
									
									$track_data['arrtime'] = date('M j, Y H:i:s', strtotime($response->CompletedTrackDetails->TrackDetails->ActualDeliveryTimestamp));
									$track_data['deptime'] = date('M j, Y H:i:s', strtotime($response->CompletedTrackDetails->TrackDetails->ShipTimestamp));

									$final_llocatoin = $final_destadd = $final_add ='';

									foreach($delivery_address as $key=>$address){
										if(!empty($address))
											$final_add .= sprintf('%s: %s, ', $key, $address);
									}

									foreach($desitnation_address as $dkey=>$dest){
										if(!empty($dest))
											$final_destadd .= sprintf('%s: %s, ', $dkey, $dest);
									}
									 
									foreach($currentlocation as $lkey=>$laddress){
										if(!empty($laddress))
											$final_llocatoin .= sprintf('%s: %s, ', $lkey, $laddress);
									}
									$track_data['destination'] = trim($final_destadd,', ');
									$track_data['devlivery'] = trim($final_add, ', ');
									$track_data['lastlocation'] = trim($final_llocatoin, ', ');

									$track_update_id = $this->checkTrackExists();

									if($track_update_id === false){
										$track_ins_id = $this->insertTrackStatus($track_data);
										if($track_ins_id !==false){
											$track_data['trackid'] = $track_ins_id;
											$this->emailPushNotification($track_userid,$fedex_track_no,$record_status);
										}
									}else{
											$track_package_name		=	$this->updateTrackStatus($track_data,$track_update_id);
											$track_data['trackid']	=	$track_update_id;
											$track_data['packagename']	=	$track_package_name;
											$this->emailPushNotification($track_userid,$fedex_track_no,$record_status,$track_update_id);
									}
									$track_data['trackhistory'] = array();
									$return_arr['tracklist'][] = $track_data;
								}else{
									$return_arr['error_code'] = 1;
									$return_arr['error_msg'] = $response->CompletedTrackDetails->TrackDetails->Notification->Message;
								}
							}
 						}
						else
						{
							$return_arr['error_code'] = 1;
							$return_arr['error_msg'] = 'FEDEX API error';
 						} 
						
					} catch (SoapFault $exception) {
						 $return_arr['error_code'] = 1;
 						 $return_arr['error_msg'] = 'FEDEX SOAP exception arised';
					}
				}else{
					$return_arr['error_code'] = 1;
 					$return_arr['error_msg'] = 'Missing FEDEX configuration';
				}

				$this->printErrorMessage($return_arr);
		}

		public function trackUspsShippment(){
			
			    //****************
				/* @Description: To get USPS tracking detials
				/* @parameter:   
				/* @return :  
				****************/
				global $G_uspscredentials;

				$usps_track_no = trim($_REQUEST['trackno']);
				$track_userid = trim($_REQUEST['userid']);

				if(!empty($G_uspscredentials['username']) && !empty($G_uspscredentials['password']) && !empty($_REQUEST['trackno'])){

					$uspc_user_name = $G_uspscredentials['username'];
					$uspc_track_id  = trim($_REQUEST['trackno']);

					$tracking = new USPSTrackConfirm($uspc_user_name);

					$tracking->setTestMode(true);

					$tracking->addPackage($uspc_track_id);

					$usps_xml_data = $tracking->getTracking();
					$usps_array_data = $tracking->getArrayResponse();

					if($tracking->isSuccess()) {

						if ($usps_xml_data != 'FAILURE' && $usps_xml_data != 'ERROR'){

							$return_arr['error_code']		=	0;
							$track_data['userid']			=	trim($_REQUEST['userid']);
							$track_data['trackno']			=	$usps_array_data['TrackResponse']['TrackInfo']['@attributes']['ID'];
							$track_data['type']				=	"usps";
							$track_data['package_weight']	=	"N-A";
							$track_data['package_dimensions']=	"N-A";

							if($usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['Event'] != ""){
								$record_status = $usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['Event'];
							}else{
								$record_status = "pending";
							}

							$track_data['status']			=	$record_status;

							$track_data['service']			=	$usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['FirmName'];
							$track_data['lastlocation']		=	sprintf('CountryCode:%s',$usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['EventCountry']);

							$evnt_act_date					=	date("M j, Y",strtotime($usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['EventDate']));

							//$evnt_act_date		=	date("M j, Y");
							$explode_date= explode(",",$evnt_act_date);
							$date_month	= $explode_date[0];
							$year		= $explode_date[1];
							

							$evnt_act_time					=	$usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['EventTime'];
									$evnt_act_time_hr	=	date("H", strtotime($evnt_act_time));
									$evnt_act_time_min	=	date("i", strtotime($evnt_act_time));
									$evnt_act_time_sec	=	date("s", strtotime($evnt_act_time));
							
							$arr_time = sprintf('%s, %04d %02d:%02d:%02d', $date_month,$year, $evnt_act_time_hr, $evnt_act_time_min, $evnt_act_time_sec);
							
							if(!empty($usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['EventDate'])){
								$track_data['arrtime']		=	$arr_time;
							}else{
								$track_data['arrtime']		=	"";
								
							}
							$track_data['deptime']			= "";

							$track_data['destination'] = sprintf('City:%s,StateOrProvinceCode:%s, CountryCode:%s', $usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['EventCity'],$usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['EventState'],$usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['EventCountry']);

							$track_data['delivery'] = sprintf('City:%s,StateOrProvinceCode:%s, CountryCode:%s', $usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['EventCity'],$usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['EventState'],$usps_array_data['TrackResponse']['TrackInfo']['TrackSummary']['EventCountry']);

							
							
							$track_data['trackhistory'] = array();
							foreach($usps_array_data['TrackResponse']['TrackInfo']['TrackDetail'] as $usps_value){
								$track_address				=	array();

								$track_address['location']	=	sprintf('City:%s,StateOrProvinceCode:%s, CountryCode:%s', $usps_value['EventCity'], $usps_value['EventState'],$usps_value['EventCountry']);

								$track_address['activity']	=	$usps_value['Event'];

								$act_date					=	date("M j, Y",strtotime($usps_value['EventDate']));

								//$evnt_act_date		=	date("M j, Y");
								$actexplode_date= explode(",",$act_date);
								$actdate_month	= $actexplode_date[0];
								$actyear		= $actexplode_date[1];

								$act_time					=	$usps_value['EventTime'];

									$act_time_hr	=	date("H", strtotime($act_time));
									$act_time_min	=	date("i", strtotime($act_time));
									$act_time_sec	=	date("s", strtotime($act_time));

								if(!empty($usps_value['EventDate'])){

									$track_address['tdate']		=	sprintf('%s, %04d %02d:%02d:%02d',$actdate_month,$actyear , $act_time_hr, $act_time_min, $act_time_sec);
								}else{
									$track_address['tdate']		= "";
								}


								$track_data['trackhistory'][] = $track_address;
							}

							$track_update_id = $this->checkTrackExists();

							if($track_update_id === false){
								$track_ins_id = $this->insertTrackStatus($track_data);
								if($track_ins_id !==false){
									$track_data['trackid'] = $track_ins_id;
									$this->emailPushNotification($track_userid,$usps_track_no,$record_status);
								}
							}else{
									$track_package_name		=	$this->updateTrackStatus($track_data,$track_update_id);
									$track_data['trackid']	=	$track_update_id;
									$track_data['packagename']	=	$track_package_name;
									$this->emailPushNotification($track_userid,$usps_track_no,$record_status,$track_update_id);
							}						
							$return_arr['tracklist'][] = $track_data;
						
						}else{
							$return_arr['error_code'] = 1;
 							$return_arr['error_msg'] = 'Unable to process your request';

						}
				}else{
					$return_arr['error_code'] = 1;
 					$return_arr['error_msg'] = $tracking->getErrorMessage();
				}

			}else{
					$return_arr['error_code'] = 1;
 					$return_arr['error_msg'] = 'Missing USPS configuration';
				}

				$this->printErrorMessage($return_arr);


				
		}

		public function trackTntShippment(){
				
				//****************
				/* @Description: To get UPS tracking detials
				/* @parameter:   
				/* @return :  
				****************/
				global $G_tntcredentials;

				$tnt_track_id = trim($_REQUEST['trackno']);
				$track_userid = trim($_REQUEST['userid']);

				if(!empty($G_tntcredentials['username']) && !empty($G_tntcredentials['password'])){
					
				
					$xmlfile = file_get_contents('xml/tnt.xml', true);
					$to_replace = array('{{tracknumber}}');

					$replace_with['tracknumber'] = trim($_REQUEST['trackno']);
					$xmldata = str_replace($to_replace, $replace_with, $xmlfile);

					if($xmldata !== false ){						
						$vals = xml2array($xmldata);
						//print_r($vals);
						
						if($vals['TrackResponse']['Consignment'][0]['ConsignmentNumber'] == 123456782){


							$return_arr['error_code']		=	0;
							$track_data['userid']			=	trim($_REQUEST['userid']);
							$track_data['trackno']			=	$vals['TrackResponse']['Consignment'][0]['ConsignmentNumber'];
							$track_data['type']				=	"tnt";
							$track_data['package_weight']	=	"N-A";
							$track_data['package_dimensions']=	"N-A";
							$track_data['package_quantity']	=	$vals['TrackResponse']['Consignment'][0]['PieceQuantity'];
							$tnt_track_status				=	"pending";
							$track_data['status']			=	$tnt_track_status;
							$track_data['service']			=	"";
							$track_data['lastlocation']		=	sprintf('Town:%s',$vals['TrackResponse']['Consignment'][0]['DeliveryTown']);
							
							if($vals['TrackResponse']['Consignment'][0]['DeliveryDate'] != ""){
								$evnt_act_date					=	date("M j, Y",strtotime($vals['TrackResponse']['Consignment'][0]['DeliveryDate']));

								//$evnt_act_date		=	date("M j, Y");
								$explode_date = explode(",",$evnt_act_date);
								$date_month = $explode_date[0];
								$year		= $explode_date[1];
							}else{
								$date_month = "";
								$year  = "";
							}



							if($vals['TrackResponse']['Consignment'][0]['DeliveryTime'] != ""){
								$evnt_act_time					=	$vals['TrackResponse']['Consignment'][0]['DeliveryTime'];
								$evnt_act_time_hr	=	date("H", strtotime($evnt_act_time));
								$evnt_act_time_min	=	date("i", strtotime($evnt_act_time));
								$evnt_act_time_sec	=	date("s", strtotime($evnt_act_time));
							}else{
								$evnt_act_time_hr	=	"";
								$evnt_act_time_min	=	"";
								$evnt_act_time_sec	=	"";

							}

							if($vals['TrackResponse']['Consignment'][0]['DeliveryDate'] != ""){

								$track_data['arrtime']			=	sprintf('%s, %04d %02d:%02d:%02d', $date_month,$year, $evnt_act_time_hr, $evnt_act_time_min, $evnt_act_time_sec);
							}else{
								$track_data['arrtime']			= "";
							}
							$track_data['deptime']			= "";

							$track_data['destination'] = sprintf('Town:%s,City:%s,StateOrProvinceCode:%s, CountryCode:%s',$vals['TrackResponse']['Consignment'][0]['DeliveryTown'],"","","");

							$track_data['delivery'] = sprintf('Town:%s,City:%s,StateOrProvinceCode:%s, CountryCode:%s',$vals['TrackResponse']['Consignment'][0]['DeliveryTown'],"","","");
							

							$track_data['trackhistory'] = array();

							$si=0;

							foreach($vals['TrackResponse']['Consignment'] as $key){
								
								if($si>0){
									$track_address				=	array();
									$track_address['location']	=	sprintf('Town:%s,City:%s,StateOrProvinceCode:%s, CountryCode:%s', $key['DeliveryTown'],"","","");

									$track_address['activity']	=	$key['CustomerReference'];

									if($key['CollectionDate'] != ""){
										$act_date					=	date("M j, Y",strtotime($key['CollectionDate']));

										//$evnt_act_date		=	date("M j, Y");
										$actexplode_date= explode(",",$act_date);
										$actdate_month	= $actexplode_date[0];
										$actyear		= $actexplode_date[1];
									}else{
										$actdate_month	= "";
										$actyear		= "";
									}

									if($key['DeliveryTime'] != ""){
										$act_time					=	$key['DeliveryTime'];

										$act_time_hr	=	date("H", strtotime($act_time));
										$act_time_min	=	date("i", strtotime($act_time));
										$act_time_sec	=	date("s", strtotime($act_time));
									}else{
										$act_time_hr	=	"";
										$act_time_min	=	"";
										$act_time_sec	=	"";
									
									}

									if($key['CollectionDate'] != ""){

										$track_address['tdate']		=	sprintf('%s, %04d %02d:%02d:%02d',$actdate_month,$actyear , $act_time_hr, $act_time_min, $act_time_sec);
									}else{
										$track_address['tdate']		= "";
									}

									$track_data['trackhistory'][] = $track_address;
								}
								
								$si++;
							}
							
							
							$track_update_id = $this->checkTrackExists();

							if($track_update_id === false){
								$track_ins_id = $this->insertTrackStatus($track_data);
								if($track_ins_id !==false){
									$track_data['trackid'] = $track_ins_id;
									$this->emailPushNotification($track_userid,$tnt_track_id,$tnt_track_status);
								}
							}else{
									$track_package_name		=	$this->updateTrackStatus($track_data,$track_update_id);
									$track_data['trackid']	=	$track_update_id;
									$track_data['packagename']	=	$track_package_name;
									$this->emailPushNotification($track_userid,$tnt_track_id,$tnt_track_status,$track_update_id);
									
							}

							$return_arr['tracklist'][] = $track_data;
							
						}else{
							$return_arr['error_code'] = 1;
							$return_arr['error_msg'] = 'No result found for your TNT query. Please try again.';
						}
					}else{
						$return_arr['error_code'] = 1;
						$return_arr['error_msg'] = 'Unable to access TNT API';
					}
					
				}else{
					$return_arr['error_code'] = 1;
 					$return_arr['error_msg'] = 'Missing TNT configuration';
				}
				
				$this->printErrorMessage($return_arr);
				
		}

		public function trackDhlShippment(){
				
				//****************
				/* @Description: To get UPS tracking detials
				/* @parameter:   
				/* @return :  
				****************/
				//echo "hi";

				global $G_dhlcredentials;
				$track_userid = trim($_REQUEST['userid']);
				if(!empty($G_dhlcredentials['username']) && !empty($G_dhlcredentials['password'])){

					$dhl_track_num	= trim($_REQUEST['trackno']);
					$dhl_url		= 'http://54.148.175.127/synq/dhl/samples/EA/Tracking.php?dhl_trackId='.$dhl_track_num;
					$dhl_xml_data	= file_get_contents($dhl_url, true);

					if($xmldata !== false ){

						$dhl_array_val = xml2array($dhl_xml_data);
						

							if($dhl_array_val['req:TrackingResponse']['AWBInfo']['Status']['Condition']['ConditionCode'] !=207){


							$return_arr['error_code']	=	0;
							$track_data['userid']		=	trim($_REQUEST['userid']);
							$track_data['trackno']		=	trim($_REQUEST['trackno']);
							$track_data['type']			=	"dhl";
							$track_data['package_weight']	=	"N/A";
							$track_data['package_dimensions']=	"N/A";

							$track_data['package_quantity']	=	$dhl_array_val['req:TrackingResponse']['AWBInfo']['TrackingPieces']['PieceInfo']['PieceDetails']['PieceNumber'];

							if($dhl_array_val['req:TrackingResponse']['AWBInfo']['Status']['ActionStatus'] != ""){
								$record_status = $dhl_array_val['req:TrackingResponse']['AWBInfo']['Status']['ActionStatus'];
							}else{
								$record_status = "pending";
							}

							$track_data['status']		=	$record_status;
							$track_data['service']		=	"";
							$track_data['lastlocation']	= $dhl_array_val['req:TrackingResponse']['AWBInfo']['TrackingPieces']['PieceInfo']['PieceEvent']['ServiceArea']['Description']; 	sprintf('ServiceAreaCode:%s',$dhl_array_val['req:TrackingResponse']['AWBInfo']['TrackingPieces']['PieceInfo']['PieceEvent']['ServiceArea']['ServiceAreaCode']);

							$evnt_act_date		=	date("M j, Y",strtotime($dhl_array_val['req:TrackingResponse']['AWBInfo']['TrackingPieces']['PieceInfo']['PieceEvent']['Date']));

							
							$explode_date = explode(",",$evnt_act_date);
							$date_month = $explode_date[0];
							$year		= $explode_date[1];

							$evnt_act_time		=	$dhl_array_val['req:TrackingResponse']['AWBInfo']['TrackingPieces']['PieceInfo']['PieceEvent']['Time'];
									$evnt_act_time_hr	=	date("H", strtotime($evnt_act_time));
									$evnt_act_time_min	=	date("i", strtotime($evnt_act_time));
									$evnt_act_time_sec	=	date("s", strtotime($evnt_act_time));
							

							if(!empty($dhl_array_val['req:TrackingResponse']['AWBInfo']['TrackingPieces']['PieceInfo']['PieceEvent']['Date'])){
								$track_data['arrtime']			=	sprintf('%s, %04d %02d:%02d:%02d', $date_month, $year, $evnt_act_time_hr, $evnt_act_time_min, $evnt_act_time_sec);
							}else{
								$track_data['arrtime']			= "";
							}


							$track_data['deptime']			= "";

							$track_data['destination'] = $dhl_array_val['req:TrackingResponse']['AWBInfo']['TrackingPieces']['PieceInfo']['PieceEvent']['ServiceArea']['Description'];

							$track_data['delivery'] = $dhl_array_val['req:TrackingResponse']['AWBInfo']['TrackingPieces']['PieceInfo']['PieceEvent']['ServiceArea']['Description'];

							$track_update_id = $this->checkTrackExists();

							if($track_update_id === false){
								$track_ins_id = $this->insertTrackStatus($track_data);
								if($track_ins_id !==false){
									$track_data['trackid'] = $track_ins_id;
									$this->emailPushNotification($track_userid,$dhl_track_num,$record_status);
								}
							}else{
									$track_package_name		=	$this->updateTrackStatus($track_data,$track_update_id);
									$track_data['trackid']	=	$track_update_id;
									$track_data['packagename']	=	$track_package_name;
									$this->emailPushNotification($track_userid,$dhl_track_num,$record_status,$track_update_id);
							}

							$return_arr['tracklist'][] = $track_data;


						}else{
							$return_arr['error_code'] = 1;
							$return_arr['error_msg'] = 'No result found for your DHL query. Please try again.';
						}
					}else{
						$return_arr['error_code'] = 1;
 						$return_arr['error_msg'] = 'Unable to access DHL API';
					}
				}else{
					$return_arr['error_code'] = 1;
 					$return_arr['error_msg'] = 'Missing DHL configuration';
				}
			$this->printErrorMessage($return_arr);	
		}

		public function trackCanadaPostShippment(){

			
			global $G_canada_postcredentials;
			$track_userid = trim($_REQUEST['userid']);
			if(!empty($G_canada_postcredentials['username']) && !empty($G_canada_postcredentials['password'])){

				$cp_track_no = trim($_REQUEST['trackno']);
				/**
				 * Sample code for the GetTrackingDetails Canada Post service.
				 * 
				 * The GetTrackingDetails service  returns all tracking events recorded for a specified 
				 * parcel. (The parcel is identified using a PIN or DNC).
				 *
				 * This sample is configured to access the Developer Program sandbox environment. 
				 * Use your development key username and password for the web service credentials.
				 * 
				 **/

				// Your username and password are imported from the following file
				// CPCWS_SOAP_Tracking_PHP_Samples\SOAP\tracking\user.ini
				$userProperties = parse_ini_file(realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . '/cpost/SOAP/tracking/user.ini');
				$wsdl = realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . '/cpost/SOAP/wsdl/track.wsdl';

				$hostName = 'ct.soa-gw.canadapost.ca';

				// SOAP URI
				$location = 'https://' . $hostName . '/vis/soap/track';

				// SSL Options
				$opts = array('ssl' =>
					array(
						'verify_peer'=> false,
						'cafile' => realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . '/../../../third-party/cert/cacert.pem',
						'CN_match' => $hostName
					)
				);

				$ctx = stream_context_create($opts);	
				$client = new SoapClient($wsdl,array('location' => $location, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS, 'stream_context' => $ctx));

				// Set WS Security UsernameToken
				$WSSENS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
				$usernameToken = new stdClass(); 
				$usernameToken->Username = new SoapVar($userProperties['username'], XSD_STRING, null, null, null, $WSSENS);
				$usernameToken->Password = new SoapVar($userProperties['password'], XSD_STRING, null, null, null, $WSSENS);
				$content = new stdClass(); 
				$content->UsernameToken = new SoapVar($usernameToken, SOAP_ENC_OBJECT, null, null, null, $WSSENS);
				$header = new SOAPHeader($WSSENS, 'Security', $content);
				$client->__setSoapHeaders($header); 

				try {
					$result = $client->__soapCall('GetTrackingDetail', array(
						'get-tracking-detail-request' => array(
							'locale'	=> 'FR',
							// PIN or DNC Choice
							'pin'		=> $cp_track_no
							// 'dnc'		=> '315052413796541'	
						)
					), NULL, NULL);

					$cp_array = json_decode(json_encode($result), true);
					
					
					
					if(isset($result->{'tracking-detail'})){


							$return_arr['error_code']	=	0;
							$track_data['userid']		=	trim($_REQUEST['userid']);
							$track_data['trackno']		=	trim($_REQUEST['trackno']);
							$track_data['type']			=	"canada_post";
							$track_data['package_weight']	=	"N/A";
							$track_data['package_dimensions']=	"N/A";

							$track_data['package_quantity']	=	"";
							
							if(!empty($cp_array['tracking-detail']['significant-events']['occurrence'][0]['event-description'])){
								$record_status = "pending";
							}else{
								$record_status = $cp_array['tracking-detail']['significant-events']['occurrence'][0]['event-description'];
							}
							

							$track_data['status']		=	$cp_array['tracking-detail']['significant-events']['occurrence'][0]['event-description'];

							$track_data['service']		=	$cp_array['tracking-detail']['service-name'];

							$track_data['lastlocation']	=	$cp_array['tracking-detail']['significant-events']['occurrence'][0]['event-site'];

							$cp_evnt_date = $cp_array['tracking-detail']['significant-events']['occurrence'][0]['event-date'];

							$evnt_act_date		=	date("M j, Y",strtotime($cp_evnt_date));

							
							$explode_date = explode(",",$evnt_act_date);
							$date_month = $explode_date[0];
							$year		= $explode_date[1];

							
							$cp_evnt_date = $cp_array['tracking-detail']['significant-events']['occurrence'][0]['event-time'];

							$evnt_act_time_hr	=	date("H", strtotime($cp_evnt_date));
							$evnt_act_time_min	=	date("i", strtotime($cp_evnt_date));
							$evnt_act_time_sec	=	date("s", strtotime($cp_evnt_date));
							

							if(!empty($cp_evnt_date)){
								$track_data['arrtime']			=	sprintf('%s, %04d %02d:%02d:%02d', $date_month, $year, $evnt_act_time_hr, $evnt_act_time_min, $evnt_act_time_sec);
							}else{
								$track_data['arrtime']			=   "";
							}
							
							$track_data['deptime']	=  "";

							$track_data['destination'] = $cp_array['tracking-detail']['significant-events']['occurrence'][0]['event-site'];

							$track_data['delivery'] = $cp_array['tracking-detail']['significant-events']['occurrence'][0]['event-site'];

							$track_data['trackhistory'] = array();

							$si=0;

							foreach($cp_array['tracking-detail']['significant-events']['occurrence'] as $key){
								
								if($si>0){
									$track_address				=	array();
									$track_address['location']	=	$key['event-site'];

									$track_address['activity']	=	$key['event-description'];

									if($key['event-date'] != ""){
										$act_date					=	date("M j, Y",strtotime($key['event-date']));

										$actexplode_date= explode(",",$act_date);
										$actdate_month	= $actexplode_date[0];
										$actyear		= $actexplode_date[1];
									}else{
										$actdate_month	= "";
										$actyear		= "";
									}

									if($key['event-time'] != ""){
										$act_time					=	$key['event-time'];

										$act_time_hr	=	date("H", strtotime($act_time));
										$act_time_min	=	date("i", strtotime($act_time));
										$act_time_sec	=	date("s", strtotime($act_time));
									}else{
										$act_time_hr	=	"";
										$act_time_min	=	"";
										$act_time_sec	=	"";
									
									}

									if($key['event-date'] != ""){

										$track_address['tdate']		=	sprintf('%s, %04d %02d:%02d:%02d',$actdate_month,$actyear , $act_time_hr, $act_time_min, $act_time_sec);
									}else{
										$track_address['tdate']		= "";
									}

									$track_data['trackhistory'][] = $track_address;
								}
								
								$si++;
							}

							$track_update_id = $this->checkTrackExists();

							if($track_update_id === false){
								$track_ins_id = $this->insertTrackStatus($track_data);
								if($track_ins_id !==false){
									$track_data['trackid'] = $track_ins_id;
									$this->emailPushNotification($track_userid,$cp_track_no,$record_status);
								}
							}else{
									$track_package_name		=	$this->updateTrackStatus($track_data,$track_update_id);
									$track_data['trackid']	=	$track_update_id;
									$track_data['packagename']	=	$track_package_name;
									$this->emailPushNotification($track_userid,$cp_track_no,$record_status,$track_update_id);
							}

							$return_arr['tracklist'][] = $track_data;

					} else {
						foreach ( $result->{'messages'}->{'message'} as $message ) {
							$return_arr['error_code'] = 1;
 							$return_arr['error_msg'] = $message->description;
						}
					}
				
				}catch (SoapFault $exception) {
					$return_arr['error_code'] = 1;
 					$return_arr['error_msg'] = trim($exception->getMessage());
				}
			}else{

				$return_arr['error_code'] = 1;
 				$return_arr['error_msg'] = 'Missing Canada Post configuration';
			}

			$this->printErrorMessage($return_arr);

		}
		
		public function updateSettings(){
			//****************
			/* @Description: Update push notification settings and send Emails
			/* @parameter:   
			/* @return :  
			*****************/	
			global  $G_dbconn, $G_dbdatetime;

			$user_id			= trim($_REQUEST['userid']);
			$set_push_notify	= trim($_REQUEST['push_notify']);
			$set_email_notify	= trim($_REQUEST['email_notify']);

 			$isvalid = $this->validateInput('updateSettings');
			if($isvalid === true){
				if(!empty($set_push_notify) && !empty($set_email_notify)){

					$prep_emailqry = $G_dbconn->prepare('UPDATE tbl_users SET enable_push_notification = :set_push_notify,enable_email_notification = :set_email_notify,modified_date=:modified_date  WHERE userid = :user_id');

					$updt_email = $prep_emailqry->execute(array(':set_push_notify'=>$set_push_notify,':set_email_notify'=>$set_email_notify,':modified_date'=>$G_dbdatetime, ':user_id'=>$user_id));

					if($updt_email){
						$return_arr['error_code'] = 0;
						$return_arr['error_msg'] = 'Email and push notification settings updated successfully';
					}else{
						$return_arr['error_code'] = 1;
						$return_arr['error_msg'] = 'Invalid Data';
					}
				
				}else{
					$return_arr['error_code'] = 1;
					$return_arr['error_msg'] = 'Invalid Data';
				}
				
			}else{
				$return_arr['error_code'] = 1;
				$return_arr['error_msg'] = $isvalid;
			}
			$this->printErrorMessage($return_arr);
		}

		public function updateMyprofile(){
			//****************
			/* @Description: Update user details
			/* @parameter:   
			/* @return :  
			*****************/	
			global  $G_dbconn, $G_dbdatetime;
 			$isvalid = $this->validateInput('updateMyprofile');
			
			if($isvalid === true){
				
				$user_id	= trim($_REQUEST['userid']);
				$set_name	= trim($_REQUEST['name']);
				$set_email	= trim($_REQUEST['email']);

				if(!empty($set_email) && !empty($set_name)){

					$prep_qry = $G_dbconn->prepare('SELECT * FROM tbl_users WHERE userid != :userid and email= :email');
					$prep_qry->execute(array(':userid'=>$user_id,':email'=>$set_email));
					
					$row_count = $prep_qry->rowCount();
				

					if($row_count == 0){

						$prep_emailqry = $G_dbconn->prepare('UPDATE tbl_users SET name = :setname,email = :setemail,modified_date=:modified_date  WHERE userid = :user_id');

						$updt_email = $prep_emailqry->execute(array(':setname'=>$set_name,':setemail'=>$set_email,':modified_date'=>$G_dbdatetime, ':user_id'=>$user_id));

						if($updt_email){
							$return_arr['error_code'] = 0;
							$return_arr['error_msg'] = 'Name and email updated successfully';
						}else{
							$return_arr['error_code'] = 2;
							$return_arr['error_msg'] = 'Invalid Data';
						}


					}elseif($row_count>0){
						
						$prep_emailqry = $G_dbconn->prepare('UPDATE tbl_users SET name = :setname,modified_date=:modified_date  WHERE userid = :user_id');

						$updt_email = $prep_emailqry->execute(array(':setname'=>$set_name,':modified_date'=>$G_dbdatetime, ':user_id'=>$user_id));

						if($updt_email){
							$return_arr['error_code'] = 1;
							$return_arr['error_msg'] = 'Name updated successfully. Email already exist';
						}else{
							$return_arr['error_code'] = 2;
							$return_arr['error_msg'] = 'Invalid Data';
						}

					}

				}else{
					$return_arr['error_code'] = 2;
					$return_arr['error_msg'] = 'Invalid parameters';
				}

			}else{
				$return_arr['error_code'] = 2;
				$return_arr['error_msg'] = $isvalid;
			}
			$this->printErrorMessage($return_arr);
		}

		public function getQuestions(){
			//****************
			/* @Description: Retrive the security questions from DB and send in response
			/* @parameter:   
			/* @return :  
			*****************/	
			global  $G_dbconn, $G_dbdatetime;
			$prep_qry = $G_dbconn->prepare('SELECT *  FROM tbl_security_questions');
			$prep_qry->execute();
			if($prep_qry->rowCount() > 0){
				$return_arr['error_code'] = 0;
				$question_arr = array();
				while($eachques = $prep_qry->fetch(PDO::FETCH_ASSOC)){
					$q_id = $eachques['ques_id'];
					$question['q_id'] = $q_id;
					$question['question'] = $eachques['question'];
					$question_arr[] = $question;
				}
				$return_arr['questions'] = $question_arr;
			}else{
				$return_arr['error_code'] = 1;
				$return_arr['error_msg'] = 'No records found';
			}
				$this->printErrorMessage($return_arr);

		}

		public function forgotPassword(){
			//****************
			/* @Description: Reset Password for user
			/* @parameter:   
			/* @return :  
			*****************/	
			global  $G_dbconn, $G_dbdatetime;
			$isvalid = $this->validateInput('forgotPassword');
 			if($isvalid === true){
				$user_email = trim($_REQUEST['email']);
				$hintans    = trim(stripslashes($_REQUEST['sqhint']));
				$prep_qry = $G_dbconn->prepare('SELECT * FROM tbl_users WHERE email = :eml');
				$prep_qry->execute(array(':eml'=>$user_email));
				if($prep_qry->rowCount() > 0){
					 $user_data = $prep_qry->fetch(PDO::FETCH_ASSOC);
					 if($user_data['security_hint_answer'] == $hintans){
						/*** To Generate new password ***/
						 $digits = 3;
						 $numkey =  rand(pow(10, $digits-1), pow(10, $digits)-1);
						 $input = array("@", "v", "!", "J", "d", "E", "Y", "q", "$", "H", "k");
						 $rand_keys = array_rand(array_flip($input), 3);
						 $rand_keys[3] = $numkey;
						 shuffle($rand_keys);
						 $newpassword = implode('', $rand_keys);
						 /*** End of password generation ***/
						 $set_message = sprintf("Hello,<br/>Your new password has been generated. Please use this <b>%s</b> password to login APP.<br/>Thanks and Regards", $newpassword);
						 $message['to'] = $user_email;
 						 $message['subject'] = 'New Login Password';
						 $message['message'] = $set_message;
						 $headers  = "MIME-Version: 1.0" . "\r\n";
						 $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
						 $headers .= 'From: Admin<admin@shenll.com>' . "\r\n" ;
						 $message['headers'] = $headers;
						 $prep_passqry = $G_dbconn->prepare('UPDATE tbl_users SET password = :pass  WHERE email = :emal');
						 $updt_pss = $prep_passqry->execute(array(':pass'=>sha1($newpassword), ':emal'=>$user_email));

						 if($updt_pss == true && $this->sendMail($message)){
							$return_arr['error_code'] = 0;
							$return_arr['error_msg'] = 'New password has been sent to your email address';
						 }else{
							$return_arr['error_code'] = 1;
							$return_arr['error_msg'] = 'Unable to send Email';
						 }
					}else{
						$return_arr['error_code'] = 1;
						$return_arr['error_msg'] = 'Wrong security answer.';
					}
				}else{
					$return_arr['error_code'] = 1;
					$return_arr['error_msg'] = 'Invalid Email Address';
				}
			}else{
				$return_arr['error_code'] = 1;
 				$return_arr['error_msg'] = $isvalid;
			}
			$this->printErrorMessage($return_arr);
		}

		public function changePass(){
			global  $G_dbconn, $G_dbdatetime;
			$isvalid = $this->validateInput('changePass');
 			if($isvalid === true){
				$new_Pass  = trim($_REQUEST['newPass']);
				$pass_length = strlen($new_Pass);
				$pass_Hint = trim($_REQUEST['passHint']);
				$user_Id   = trim($_REQUEST['userId']);
				
				if($pass_length>=6){
					$prep_qry = $G_dbconn->prepare('SELECT * FROM tbl_users WHERE userid = :userid');
					$prep_qry->execute(array(':userid'=>$user_Id));

					if($prep_qry->rowCount() > 0){

						$user_data = $prep_qry->fetch(PDO::FETCH_ASSOC);
						if($user_data['security_hint_answer'] == $pass_Hint){

							$prep_passqry = $G_dbconn->prepare('UPDATE tbl_users SET password = :pass,modified_date=:modified_date  WHERE userid = :userid');
							$updt_pss = $prep_passqry->execute(array(':pass'=>sha1($new_Pass),':modified_date'=>$G_dbdatetime, ':userid'=>$user_Id));
							if($updt_pss){
								$return_arr['error_code'] = 0;
								$return_arr['error_msg'] = 'New password has been changed successfully';
							}
						
						}else{
							$return_arr['error_code'] = 1;
							$return_arr['error_msg'] = 'Password hint in-valid';
						
						}
					
					}else{
						$return_arr['error_code'] = 1;
						$return_arr['error_msg'] = 'Password hint in-valid';
					}
			
				}else{
					$return_arr['error_code'] = 1;
					$return_arr['error_msg'] = 'Password length too short';
				}
			}else{
					$return_arr['error_code'] = 1;
					$return_arr['error_msg'] = 'In-valid Data';
				}
			$this->printErrorMessage($return_arr);
		}

		
		public function curlPost($curlurl, $curldata){
			//****************
			/* @Description: Common cURL post method
			/* @parameter:   
			/* @return :  
			*****************/
  			$ch = curl_init($curlurl);
			curl_setopt($ch, CURLOPT_HEADER, 0);
 			curl_setopt($ch, CURLOPT_POST,1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$curldata);
			$result = curl_exec($ch);
   			if($result === false)
			{
				return false;
			}else
				return $result;
		}

		private function sendMail($mail_arr){
			
			if(!empty($mail_arr['to']) && !empty($mail_arr['subject']) && !empty($mail_arr['message'])){
				if(!empty($mail_arr['headers']))
					$headers = $mail_arr['headers'];
				else
					$headers = '';
				if(mail($mail_arr['to'], $mail_arr['subject'], $mail_arr['message'], $headers))
					return true;
				else
					return false;
			}

		}

		public function printErrorMessage($error){
			//****************
			/* @Description: Print all messages in this method
			/* @parameter:   
			/* @return :  
			*****************/
				echo json_encode($error);
				exit;
		}
	}

	if(!empty($_REQUEST['type'])){
	    $request_type = $_REQUEST['type'];
		$tracking_obj = new ShipmentTrackingApi;
		switch($request_type){
			case 'register':
				$tracking_obj->createUser();
			break;
			case 'login':
				$tracking_obj->authenticateUser();
			break;
			case 'trackStatus':
				$tracking_obj->selectShipmentType();
			break;
			case 'getMyTrack':
				$tracking_obj->getAllTrackDetails();
			break;
			case 'updateSettings':
				$tracking_obj->updateSettings();
			break;
			case 'updateMyprofile':
				$tracking_obj->updateMyprofile();
			break;
			case 'getQuestions':
				$tracking_obj->getQuestions();
			break;
			case 'forgotPassword':
				$tracking_obj->forgotPassword();
			break;
			case 'changePass':
				$tracking_obj->changePass();
			break;
			case 'getUserSecurityQuestion':
				$tracking_obj->getUserSecurityQuestion();
			break;
			case 'updatePackageName':
				$tracking_obj->updatePackageName();
			break;

			default:
				echo json_encode(array('error_code'=>1,'error_msg'=>'Invalid request received'));
		}
	}else
		echo json_encode(array('error_code'=>1,'error_msg'=>'Invalid request received'));


