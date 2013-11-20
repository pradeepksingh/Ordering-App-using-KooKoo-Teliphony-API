<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


	class Teliphonyapi extends CI_Controller {

		function __construct() {
			parent::__construct();
		}
		public function track_your_order(){
			$mobile = $this->input->get('cid',TRUE);
			
			$step = "";
			$event = $this->input->get("event");
			$data = $this->input->get('data');
			$status = $this->input->get('status');
			$attempt = 0;
			$rests = array();
			$this->load->library('kookooresponse');
			switch($event) {
			
				case "NewCall":
					$this->session->set_userdata('attempts',0);
					$this->session->set_userdata('mobile',0);
					$this->session->set_userdata('ordercode',0);
					$this->session->set_userdata('cid',$this->input->get('cid'));
					$this->session->set_userdata('called_number',$this->input->get('called_number'));
					$this->session->set_userdata('sid',$this->input->get('sid'));
					$this->session->set_userdata('next_goto','Menu1');
					break;
			
				case "Disconnect":
					exit;
					break;
			
				case "Hangup":
					exit;
					break;
			
				case "GotDTMF":
					$step = $this->session->userdata("next_goto");
					if($step === 'Mobile_CheckInput') {
						if($data === '') {
							$this->kookooresponse->addPlayText("You have not entered any input");
							$this->load->library('kookoodtmf');
							$this->kookooresponse->addPlayText("Please enter your 10 digit registered mobile number");
							$this->kookoodtmf->setMaxDigits('10'); //max inputs to be allowed
							$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
							$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
							$this->session->set_userdata('next_goto','Mobile_CheckInput');
						}elseif(strlen($data) == 10){
							$details = $this->customer_details($data);
							if(count($details) > 0){
								$this->session->set_userdata('mobile',$data);
								$this->session->set_userdata('next_goto','Main_Menu');
							}else{
								$this->kookooresponse->addPlayText("Mobile number you have enter is not registered with us");
								$this->kookooresponse->addHangup();
							}
						}else{
							$this->kookooresponse->addPlayText("You have entered incorrect input");
							$this->load->library('kookoodtmf');
							$this->kookooresponse->addPlayText("Please enter your 10 digit registered mobile number");
							$this->kookoodtmf->setMaxDigits('10'); //max inputs to be allowed
							$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
							$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
							$this->session->set_userdata('next_goto','Mobile_CheckInput');
						}
					}
					if($step === 'Menu1_CheckInput') {
						if($data === '') {
							$this->kookooresponse->addPlayText("You have not entered any input");
							$this->session->set_userdata('next_goto','Main_Menu');
						}else{
							if($data == 1) {
								$mobile = $this->session->userdata("mobile");
								$this->load->model('orders/osearch_model','osearch');
								$orders = $this->osearch->getTodayOrdersByMemberId($mobile);
								if(count($orders) > 0){
									$orderid = $orders[0]['orderid'];
									$torder = $this->check_status($orderid);
									if($torder['dial'] == 0){
										$this->kookooresponse->addPlayText($torder['message']);
										$this->kookooresponse->addHangup();
									}else{
										$this->session->set_userdata('cityid',$torder['cityid']);
										$this->session->set_userdata('restid',$torder['restid']);
										$this->session->set_userdata('ordercode',$orders[0]['ordercode']);
										$this->kookooresponse->addPlayText($torder['message']);
										$this->session->set_userdata('next_goto','Track_Dial');
									}
								}else{
									$this->kookooresponse->addPlayText("Sorry we did not receive any order from you");
									$this->kookooresponse->addHangup();
								}
							}elseif($data == 2){
								$mobile = $this->session->userdata("mobile");
								$details = $this->customer_details($mobile);
								$this->session->set_userdata('areaid',$details[0]['cityarea']);
								$this->session->set_userdata('zone_a',$details[0]['zone_id']);
								$rests = $this->rest_list($details[0]['cityarea']);
								if(count($rests) > 0){
									$msg = 'we have found '.count($rests).' restaurants delivering to your area';
									$this->kookooresponse->addPlayText($msg);
									foreach($rests as $key=>$row){
										$restnsme = 'Press '.$key.' to select '.$row['name'].' '.$row['area'];
										$this->kookooresponse->addPlayText($restnsme);
									}
									$this->load->library('kookoodtmf');
									$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
									$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
									$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
									$this->session->set_userdata('next_goto','Menu3_CheckInput');
								}else{
									$this->kookooresponse->addPlayText("Sorry we do not have restaurants delivering to your area");
									$this->kookooresponse->addHangup();
								}
							}elseif($data == 3){
								$mobile = $this->session->userdata("mobile");
								$orders = $this->favourite_orders($mobile);
								if(count($orders) > 0){
									$msg = 'your last '.count($orders).' favourite orders are';
									$this->kookooresponse->addPlayText($msg);
									foreach($rests as $key=>$row){
										$restnsme = 'Press '.$key.' to select order '.$row['ordercode'].' from '.$row['restname'].' of amount '.$row['total'];
										$this->kookooresponse->addPlayText($restnsme);
									}
									$this->load->library('kookoodtmf');
									$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
									$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
									$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
									$this->session->set_userdata('next_goto','Fav_CheckInput');
								}else{
									$this->kookooresponse->addPlayText("Sorry we did not found any favourite order for you");
									$this->kookooresponse->addHangup();
								}
// 							}elseif($data == 4){
								
							}else{
								$this->kookooresponse->addPlayText("You have entered incorrect input");
								$this->session->set_userdata('next_goto','Main_Menu');
							}
						}
					}
					if($step === 'Fav_CheckInput') {
						if($data === '') {
							$this->kookooresponse->addPlayText("You have not entered any input, Please try again");
							$this->session->set_userdata('next_goto','Fav_List');
						}else{
							$mobile = $this->session->userdata("mobile");
							$orders = $this->favourite_orders($mobile);
							$orderid = $orders[$data]['orderid'];
							if($orderid != null && $orderid != 0 && $orderid != "") {
								$this->update_cart($orderid);
								$this->session->set_userdata('next_goto','Order_Total');
							}else{
								$this->kookooresponse->addPlayText("You have entered incorrect input, Please try again");
								$this->session->set_userdata('next_goto','Fav_List');
							}
						}
					}
					if($step === 'Menu3_CheckInput') {
						if($data === '') {
							$this->kookooresponse->addPlayText("You have not entered any input, Please try again");
							$this->session->set_userdata('next_goto','Rest_List');
						}else{
							$rests = $this->rest_list($this->session->userdata('areaid'));
							$restid = $rests[$data]['restid'];
							$zone_b = $rests[$data]['zone_id'];
							if($restid != null && $restid != 0 && $restid != "") {
								$this->session->set_userdata('restid',$restid);
								$this->session->set_userdata('zone_b',$zone_b);
								$this->session->set_userdata('next_goto','Main_Cat');
							}else{
								$this->kookooresponse->addPlayText("You have entered incorrect input, Please try again");
								$this->session->set_userdata('next_goto','Rest_List');
							}
						}
					}
					if($step === 'Menu4_CheckInput') {
						if($data === '') {
							$this->kookooresponse->addPlayText("You have not entered any input, Please try again");
							$this->session->set_userdata('next_goto','Main_Cat');
						}else{
							$restid = $this->session->userdata('restid');
							$maincats = $this->get_rest_main_cat($restid);
							$mcatid = $maincats[$data]['mcatid'];
							if($mcatid != null && $mcatid != 0 && $mcatid != "") {
								$this->session->set_userdata('mcatid',$mcatid);
								$items = $this->get_item_list($restid,$mcatid);
								$this->session->set_userdata('next_goto','Item_Menu');
							}else{
								$this->kookooresponse->addPlayText("You have entered incorrect input, Please try again");
								$this->session->set_userdata('next_goto','Main_Cat');
							}
						}
					}
					if($step === 'Menu8_CheckInput') {
						if($data === '') {
							$this->kookooresponse->addPlayText("You have not entered any input, Please try again");
							$this->session->set_userdata('next_goto','Item_Menu');
						}else{
							$mcatid = $this->session->userdata('mcatid');
							$restid = $this->session->userdata('restid');
							$items = $this->get_item_list($restid,$mcatid);
							$itemid = $items[$data]['id'];
							if($itemid != null && $itemid != 0 && $itemid != "") {
								$this->session->set_userdata('itemid',$itemid);
								$this->session->set_userdata('next_goto','Item_quanity');
							}else{
								$this->kookooresponse->addPlayText("You have entered incorrect input, Please try again");
								$this->session->set_userdata('next_goto','Item_Menu');
							}
						}
					}
					if($step === 'Menu5_CheckInput') {
						if($data === '') {
							$this->kookooresponse->addPlayText("You have not entered any input, Please try again");
							$this->session->set_userdata('next_goto','Item_quanity');
						}else{
							if(strlen($data) <= 2) {
								$restid = $this->session->userdata('restid');
								$itemid = $this->session->userdata('itemid');
								$this->add_item_to_cart($itemid, $data, $restid);
								$this->kookooresponse->addPlayText("Item added to cart");
								$this->session->set_userdata('next_goto','Item_New');
							}else{
								$this->kookooresponse->addPlayText("You have entered incorrect input, Please try again");
								$this->session->set_userdata('next_goto','Item_quanity');
							}
						}
					}
					if($step === 'Menu6_CheckInput') {
						if($data === '') {
							$this->kookooresponse->addPlayText("You have not entered any input, Please try again");
							$this->session->set_userdata('next_goto','Item_New');
						}else{
							if($data == 1) {
								$this->session->set_userdata('next_goto','Item_Menu');
							}elseif($data == 2){
								$this->session->set_userdata('next_goto','Main_Cat');
							}elseif($data == 3){
								$this->session->set_userdata('next_goto','Order_Total');
							}else{
								$this->kookooresponse->addPlayText("You have entered incorrect input, Please try again");
								$this->session->set_userdata('next_goto','Item_New');
							}
						}
					}
					if($step === 'Menu7_CheckInput') {
						if($data === '') {
							$this->kookooresponse->addPlayText("You have not entered any input, Please try again");
							$this->load->library('kookoodtmf');
							$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
							$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
							$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
							$this->session->set_userdata('next_goto','Menu7_CheckInput');
						}else{
							if($data == 2) {
								$this->session->set_userdata('next_goto','Item_Menu');
							}elseif($data == 1){
								$this->session->set_userdata('next_goto','Cart_Chekout');
							}else{
								$this->kookooresponse->addPlayText("You have entered incorrect input, Please try again");
								$this->load->library('kookoodtmf');
								$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
								$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
								$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
								$this->session->set_userdata('next_goto','Menu7_CheckInput');
							}
						}
					}
					break;
			
				case "Dial":
					$step = $this->session->userdata("next_goto");
					if($step === 'Dial1_Status') {
						$attempts = $this->session->userdata("attempts") + 1;
						$number = $this->session->userdata('max_numbers');
						if($status === 'not_answered') {
							if($attempts < count($number)){
								$this->kookooresponse->addPlayText('number not connected trying to reconnect');
								$this->session->set_userdata('next_goto','Track_Dial');
							}else{
								$ordercode = $this->session->userdata("ordercode");
								$this->send_status_email($ordercode);
								$this->kookooresponse->addPlayText('Sorry we are unable to connect restaurant, expect a call from our customer care');
								$this->kookooresponse->addHangup();
							}
							$this->session->set_userdata('attempts',$attempts);
						}else {
							$this->kookooresponse->addHangup();
						}
					}
					break;
			}
			
			$step = $this->session->userdata("next_goto");
			if($step === 'Menu1'){
				$this->kookooresponse->addPlayText('Welcome to tastykhana');
				$details = $this->customer_details($mobile);
				if(count($details) > 0){
					$this->session->set_userdata('mobile',$mobile);
					$this->session->set_userdata('next_goto','Main_Menu');
				}else{
					$this->load->library('kookoodtmf');
					$this->kookooresponse->addPlayText("Please enter your 10 digit registered mobile number");
					$this->kookoodtmf->setMaxDigits('10'); //max inputs to be allowed
					$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
					$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
					$this->session->set_userdata('next_goto','Mobile_CheckInput');
				}
			}
			if($step === 'Main_Menu') {
				$this->load->library('kookoodtmf');
				$this->kookoodtmf->addPlayText('Press 1 to track your order status',3);
				$this->kookoodtmf->addPlayText('Press 2 to place your order ',3);
				$this->kookoodtmf->addPlayText('Press 3 to reorder',3);
// 				$this->kookoodtmf->addPlayText('Press 4 to file refund',3);
				$this->kookoodtmf->setMaxDigits('1'); //max inputs to be allowed
				$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
				$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
				$this->session->set_userdata('next_goto','Menu1_CheckInput');
			}
			if($step === 'Track_Dial'){
				$datamap = array();
				$datamap['restid'] = $this->session->userdata('restid');
				$datamap['cityid'] = $this->session->userdata('cityid');
				$rest_contacts = $this->rest_contacts($datamap);
				$number = $rest_contacts['rest_contact'];
				$this->session->set_userdata('max_numbers',count($number));
				$stdcode = $rest_contacts['stdcode'];
				$attempts = $this->session->userdata("attempts");
				if(strlen($number[$attempts]) == 8){
					$rest_contact = '0'.substr($stdcode,1,strlen($stdcode)).$number[$attempts];
				}else{
					$rest_contact = '0'.$number[$attempts];
				}
				$this->kookooresponse->addPlayText('Please wait while we connect you to restaurant',3);
				$this->kookooresponse->addDial($rest_contact);
				$this->session->set_userdata('next_goto','Dial1_Status');
			}
			if($step === 'Fav_List'){
				$mobile = $this->session->userdata("mobile");
				$orders = $this->favourite_orders($mobile);
				foreach($rests as $key=>$row){
					$restnsme = 'Press '.$key.' to select order '.$row['ordercode'].' from '.$row['restname'].' of amount '.$row['total'];
					$this->kookooresponse->addPlayText($restnsme);
				}
				$this->load->library('kookoodtmf');
				$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
				$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
				$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
				$this->session->set_userdata('next_goto','Fav_CheckInput');
			}
			if($step === 'Rest_List'){
				$areaid = $this->session->userdata('areaid');
				$rests = $this->rest_list($areaid);
				foreach($rests as $key=>$row){
					$restnsme = 'Press '.$key.' to select '.$row['name'].' '.$row['area'];
					$this->kookooresponse->addPlayText($restnsme);
				}
				$this->load->library('kookoodtmf');
				$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
				$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
				$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
				$this->session->set_userdata('next_goto','Menu3_CheckInput');
			}
			if($step === 'Main_Cat') {
				$restid = $this->session->userdata('restid');
				$maincats = $this->get_rest_main_cat($restid);
				foreach($maincats as $key=>$row){
					$catname = 'Press '.$key.' to select items from '.$row['mcatname'];
					$this->kookooresponse->addPlayText($catname);
				}
				$this->load->library('kookoodtmf');
				$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
				$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
				$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
				$this->session->set_userdata('next_goto','Menu4_CheckInput');
			}
			if($step === 'Item_Menu') {
				$mcatid = $this->session->userdata('mcatid');
				$restid = $this->session->userdata('restid');
				$items = $this->get_item_list($restid,$mcatid);
				foreach($items as $key=>$row){
					$itemname = 'Press '.$key.' to select '.$row['name'];
					$itemprice = 'price of this item is rupees '.$row['price'];
					$this->kookooresponse->addPlayText($itemname);
					$this->kookooresponse->addPlayText($itemprice);
				}
				$this->load->library('kookoodtmf');
				$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
				$this->kookoodtmf->setTimeOut('200'); //maxtimeout if caller not give any inputs
				$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
				$this->session->set_userdata('next_goto','Menu8_CheckInput');
			}
			if($step === 'Item_quanity') {
				$this->kookooresponse->addPlayText("Please enter item quantity");
				$this->load->library('kookoodtmf');
				$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
				$this->kookoodtmf->setTimeOut('200'); //maxtimeout if caller not give any inputs
				$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
				$this->session->set_userdata('next_goto','Menu5_CheckInput');
			}
			if($step === 'Item_New') {
				$this->kookooresponse->addPlayText("Press 1 to add more items");
				$this->kookooresponse->addPlayText("Press 2 to change main category");
				$this->kookooresponse->addPlayText("Press 3 to check your order cart");
				$this->load->library('kookoodtmf');
				$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
				$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
				$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
				$this->session->set_userdata('next_goto','Menu6_CheckInput');
			}
			if($step === 'Order_Total') {
				$restid = $this->session->userdata('restid');
				$areaid = $this->session->userdata('areaid');
				$zone_a = $this->session->userdata('zone_a');
				$zone_b = $this->session->userdata('zone_b');
				$cart_total = $this->get_cart_total($restid,$areaid,$zone_a,$zone_b);
				if($cart_total['itemcount'] > 0){
					if($cart_total['itemcount'] > 1){
						$this->kookooresponse->addPlayText("You have ordered ".$cart_total['itemcount']." items");
						$this->kookooresponse->addPlayText("Items are");
					}else{
						$this->kookooresponse->addPlayText("You have ordered ".$cart_total['itemcount']." item");
						$this->kookooresponse->addPlayText("Item is");
					}
					foreach ($cart_total['items'] as $key=>$row){
						$this->kookooresponse->addPlayText($row['quantity']." ".$row['name']);
					}
					$this->kookooresponse->addPlayText("Your cart sub total is rupees ".$cart_total['amount']);
					if($cart_total['deliverycharge'] > 0)
						$this->kookooresponse->addPlayText("delivery charge is rupees ".$cart_total['deliverycharge']);
					if($cart_total['packaging'] > 0)
						$this->kookooresponse->addPlayText("packaging charge is rupees ".$cart_total['packaging']);
					if($cart_total['tax'] > 0)
						$this->kookooresponse->addPlayText("tax is rupees ".$cart_total['tax']);
					if($cart_total['total'] > 0)
						$this->kookooresponse->addPlayText("Your grand total is rupees ".$cart_total['total']);
					$this->kookooresponse->addPlayText("Press 1 to place this order");
					$this->kookooresponse->addPlayText("Press 2 to add more items");
					$this->load->library('kookoodtmf');
					$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
					$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
					$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
					$this->session->set_userdata('next_goto','Menu7_CheckInput');
				}else{
					$this->kookooresponse->addPlayText("There is no items in your cart");
					$this->kookooresponse->addPlayText("Press 1 to add more items");
					$this->kookooresponse->addPlayText("Press 2 to change main category");
					$this->kookooresponse->addPlayText("Press 3 to check your order cart");
					$this->load->library('kookoodtmf');
					$this->kookoodtmf->setMaxDigits('2'); //max inputs to be allowed
					$this->kookoodtmf->setTimeOut('1000'); //maxtimeout if caller not give any inputs
					$this->kookooresponse->addCollectDtmf($this->kookoodtmf);
					$this->session->set_userdata('next_goto','Menu6_CheckInput');
				}
			}
			if($step === 'Cart_Chekout') {
				$this->kookooresponse->addPlayText("Please wait your order is being checkout");
				$restid = $this->session->userdata('restid');
				$areaid = $this->session->userdata('areaid');
				$zone_a = $this->session->userdata('zone_a');
				$zone_b = $this->session->userdata('zone_b');
				$rest_details = $this->rest_detail($restid);
				$mem_details = $this->customer_details($this->session->userdata('mobile'));
				$cart_total = $this->get_cart_total($restid,$areaid,$zone_a,$zone_b);
				$map['restid'] = $rest_details['restid'];
				$map['restname'] = $rest_details['name'].'-'.$rest_details['area'];
				$map['model_type'] = $rest_details['model_type'];
				$map['date'] = date('Y-m-d');
				$map['time'] =	date('H:i');
				$map['tax'] = $cart_total['tax'];
				$map['deliverycharge'] = $cart_total['deliverycharge'];
				$map['amount'] = $cart_total['amount']+$cart_total['tax']+$cart_total['packaging'];
				$map['rdiscount'] = 0;
				$map['deal_comm'] = 0;
				$map['total'] = $cart_total['amount']+$cart_total['tax']+$cart_total['packaging']+$cart_total['deliverycharge'];
				$map['payment_mode'] = 0;
				$map['status'] = 0;
				$map['mode'] = 0;
				$map['discount'] = 0;
				$map['wallet_purchase'] = 0;
				$map['coupon_discount'] = 0;
				$map['extras'] = 'Test order please do not process';
				$map['areaid'] = $mem_details[0]['cityarea'];
				$map['replace_item'] = 1;
				$map['ordercode'] = strtoupper(base_convert(strtotime(date('Y-m-d H:i:s')), 10, 36)).strtoupper(base_convert($map['restid'], 10, 36)) ;
				$map['cityid'] = $rest_details['cityid'];
				$map['order_type'] = 1;
				$map['order_ip'] = ip2long($_SERVER['REMOTE_ADDR']);
				$map['userid'] = $mem_details[0]['id'];
				$map['usertype'] = 1;
				$cartmap = array();
				$cartmap['restid'] = $rest_details['restid'];
				$cartmap['amount'] = $cart_total['amount']+$cart_total['packaging'];
				$cartmap['areaid'] = $mem_details[0]['cityarea'];
				$cartmap['billing_model'] = $rest_details['model_type'];
				$cartmap['rzone'] = $this->session->userdata('zone_b');
				$this->load->library('logistics');
				$coordinates = $this->logistics->getLogisticCoordinates($cartmap);
				unset($cartmap);
				$deltime = $coordinates['deliverytime']+$coordinates['prep_time']+15;
				$del_time = $this->_get_completion_time($deltime,$map['time']);
				$map['time_in_min'] = $deltime;
				$current_time = date('H:i');
				if( $map['date'] == date('Y-m-d')) {
					if( strtotime($del_time) > strtotime($rest_details['mctime']) && strtotime($del_time) <= strtotime($this->_get_completion_time($deltime,$rest_details['eotime']))) {
						$time_diff = round((strtotime(date('Y-m-d '.$rest_details['mctime'])) - strtotime($current_time))/60);
						if($time_diff <= 5) {
							$del_time = $this->_get_completion_time($deltime,$rest_details['eotime']);
						}else {
							$del_time = $this->_get_completion_time($deltime,$current_time);
						}
					}else if( strtotime($del_time) <= strtotime($this->_get_completion_time($deltime,$rest_details['motime']))) {
						$del_time = $this->_get_completion_time($deltime,$rest_details['motime']);
					}else if( strtotime($del_time) >= strtotime($rest_details['ectime'])) {
						$time_diff = round((strtotime($del_time) - strtotime(date('Y-m-d '.$rest_details['ectime'])))/60);
						if($time_diff <= $deltime) {
							$del_time = $this->_get_completion_time(0,$del_time);
						}else {
							$del_time = $this->_get_completion_time($deltime,$rest_details['motime']);
						}
					}else {
						$time_check = $this->_get_completion_time($deltime,$current_time);
						$del_time = (strtotime($map['time']) == strtotime($del_time)) ? $this->_get_completion_time($deltime,$del_time) : $del_time;
						$del_time = (strtotime($time_check) > strtotime($del_time)) ? $time_check : $this->_get_completion_time(0,$del_time);
					}
				}else {
					if( strtotime($del_time) > strtotime($rest_details['mctime']) && strtotime($del_time) <= strtotime($this->_get_completion_time($deltime,$rest_details['eotime']))) {
						$del_time = $this->_get_completion_time($deltime,$rest_details['eotime']);
					}else if( strtotime($del_time) <= strtotime($this->_get_completion_time($deltime,$rest_details['motime']))) {
						$del_time = $this->_get_completion_time($deltime,$rest_details['motime']);
					}else if( strtotime($del_time) >= strtotime($rest_details['ectime'])) {
						$del_time = $this->_get_completion_time($deltime,$rest_details['motime']);
					}else {
						$del_time = $this->_get_completion_time(0,$del_time);
					}
				}
				$map['del_time'] = $del_time;
	
				if((int)$map['order_type'] === 1) { 
					if($this->_mark_advance($del_time,$deltime+15,$map['date'])) {
						$map['advance_order'] = 1;
					}
				}else {
					if($this->_mark_advance($map['pickup_time'],30,$map['date'])) {
						$map['advance_order'] = 1;
					}
				}
				$this->load->model('orders/orders_model','order');
				$this->load->model('orders/cart_model','cart');
				$orderid = $this->order->putInOrderQ( $map );
				$user = array();
				$user['orderid'] = $orderid;
				$user['userid'] = $mem_details[0]['id'];
				$user['usertype'] = 1;
				$user['name']= $mem_details[0]['firstname'];
				$user['mobile']= $mem_details[0]['mobile'];;
				$user['email']= $mem_details[0]['email'];
				$user['phone']= $mem_details[0]['phone'];
				$user['address']= $mem_details[0]['address1'];
				$this->order->addOrderCustomerDetails($user);
				unset($user);
				$comment = 'Order received via IVR<br/>';
				$comment = $comment.'Actual order items<br/>';
				$comment = $comment.'<table><thead><th>Item Name</th><th>Qty</th></thead>';
				foreach($cart_total['items'] as $key=>$row) {
					$userItems = array();
					$userItems['orderid'] = $orderid;
					$userItems['itemid'] = $row['itemid'];
					$userItems['restid'] = $rest_details['restid'];
					$userItems['quantity'] = $row['quantity'];
					$userItems['price'] = $row['price'];
					$userItems['total'] = $row['total'];
					$this->order->addCustomerOrderItems($userItems);
					$comment = $comment.'<tr><td>'.$row["itemname"].'</td><td>'.$row["quantity"].'</td></tr>';
					unset($userItems);
				}
				if($map['extras'] != "")
					$comment = $comment.'<tr><td>Extras</td><td>'.$extras.'</td></tr>';
					
				$comment = $comment.'</table>';
				$this->order->addOrderLogs($orderid , $comment);
				$workload['url'] = 'http://tastykhana.in/';
				$workload['orderid'] = $orderid;
				$client = new GearmanClient();
				$client->addServer();
				$client->doBackground('visitor_order',serialize($workload));
				$html = 'global-template#Global alert';
				$this->pusher->trigger($this->config->item('channel'),'crm_broadcast_event',array('message' => $html));
				$this->cart->clearOrderCart( $rest_details['restid'] );
				$this->kookooresponse->addPlayText("Your order has been placed successfully.");
				$this->kookooresponse->addPlayText("Thank You.");
				$this->kookooresponse->addHangup();
			}
			$this->kookooresponse->send();
		}
	
		public function check_status($ordercode){
			$this->load->model('orders/osearch_model','search');
			$order = $this->search->getOrderInOrderQById($ordercode);
			if($order[0]['status'] == 4){
				$data['message'] = 'Dear customer your order has been cancelled.';
				$data['status'] = 0;
				$data['dial'] = 0;
				return $data;
				exit;
			}
			if(count($order) > 0){
				if($order[0]['mobile'] != "" && $order[0]['mobile'] != 0){
					$user_mobile = $order[0]['mobile'];
				}else{
					$user_mobile = 0;
				}
				$data['dial'] = 0;
				if($order[0]['order_type'] == 2){
					if($order[0]['date'] == date('Y-m-d')){
						$pickuptime = new DateTime($order[0]['pickup_time']);
						$data['message'] = 'You have placed a takeaway order. Please pickup your order at '.$pickuptime->format('h:i A').'.';
					}elseif($order[0]['date'] > date('Y-m-d')){
						$pickuptime = new DateTime($order[0]['pickup_time']);
						$data['message'] = 'You have placed a takeaway order.Please pickup your order on '.date('jS F Y',strtotime($order[0]['date'])).' at '.$pickuptime->format('h:i A').'.';
					}else{
						$data['message'] = 'You have picked up your order. Incase you have not picked up, get in touch with us on chat.';
					}
				}else{
					if($order[0]['date'] == date('Y-m-d')){
						$deltime = new DateTime($order[0]['del_time']);
						if($order[0]['status'] == 3){
							if($order[0]['model_type'] == 1){
								$data['message'] = 'Your order has been delivered. Incase your order is not delivered, get in touch with us on chat.';
							}else{
								$min = (strtotime($order[0]['del_time'])-strtotime(date('H:i:s')))/60;
								if($min <= 15){
									if($user_mobile != 0){
										$data['message'] = 'Since its almost time for delivery, we are connecting you to the concerned restaurant to track status.';
										$data['dial'] = 1;
										$data['cityid'] = $order[0]['cityid'];
										$data['restid'] = $order[0]['restid'];
									}else{
										$data['message'] = 'We are unable to get your order status due to some reasons. One of our customer representative will contact you soon.';
									}
								}else{
									$data['message'] = 'Expected time of delivery is '.$deltime->format('h:i A').', It is currently being prepared by the restaurant for delivery.';
								}
							}
						}elseif($order[0]['status'] == 7){
							$data['message'] = 'Your order will be cancelled as payment failed for your order. You can place a fresh order.';
						}else{
							if($order[0]['model_type'] == 1){
								if($order[0]['status'] == 0){
									$data['message'] = 'You order is not yet processed, you will get a confirmation mail once your order is processed to the restaurant.';
								}elseif($order[0]['status'] == 2){
									$data['message'] = 'Expected time of delivery is '.$deltime->format('h:i A').', It is currently on the way for delivery.';
								}else{
									$data['message'] = 'Expected time of delivery is '.$deltime->format('h:i A').', It is currently being prepared by the restaurant for delivery.';
								}
							}else{
								$data['message'] = 'You order is not yet processed, you will get a confirmation mail once your order is processed to the restaurant.';
							}
						}
					}else{
						if($order[0]['order_type'] == 2){
							if($order[0]['date'] > date('Y-m-d')){
								$pickuptime = new DateTime($order[0]['pickup_time']);
								$data['message'] = 'You have placed a takeaway order.Please pickup your order on '.date('j F, Y',strtotime($order[0]['date'])).' at '.$pickuptime->format('h:i A').'.';
							}else{
								$data['message'] = 'You have picked up your order. Incase you have not picked up, get in touch with us on chat.';
							}
						}else{
							if($order[0]['date'] > date('Y-m-d')){
								$time = new DateTime($order[0]['del_time']);
								$data['message'] = 'Your order will be delivered on '.date('j F, Y',strtotime($order[0]['date'])).' at '.$time->format('h:i A').'.';
							}else{
								$data['message'] = 'Your order has been delivered. Incase your order is not delivered, get in touch with us on chat.';
							}
						}
					}
				}
				return $data;
			}
		}
		
		
		public function customer_details($mobile){
			$this->load->model('teliphony_model','teliphony');
			return $details = $this->teliphony->getMemberDetailByMobile($mobile);
		}
		
		public function favourite_orders($mobile){
			$this->load->model('orders/osearch_model','osearch');
			return $orders = $this->osearch->getMemberLastFiveFavouriteOrders($mobile);
		}
		
		public function rest_list($cityarea){
			$this->load->model('teliphony_model','teliphony');
			return $restaurants = $this->teliphony->getRestaurantList($cityarea);
		}
		
		public function rest_detail($restid){
			$this->load->model('teliphony_model','teliphony');
			return $this->teliphony->getSelectedRestaurantById($restid);
		}
		
		public function get_rest_main_cat($restid){
			$this->load->model('teliphony_model','teliphony');
			return $maincat = $this->teliphony->getRestaurantMainCategory($restid);
		}
		
		public function get_order_items($orderid){
			$this->load->model('orders/orders_model','order');
			return $items = $this->order->getCustomerOrderItems($orderid);
		}
		
		public function update_cart($orderid){
			$this->load->model('orders/cart_model','cart');
			$this->load->model('orders/orders_model','order');
			$order = $this->order->getCustomerOrderItems($orderid);
			$subitems = $this->order->getCustomerOrderSubitems( $orderid );
			$this->session->unset_userdata('admin_cart_session');
			$max = count($order);
			for($i=0;$i<$max;$i++){
				$itemMap['itemid'] = $order[$i]['itemid'];
				$itemMap['quantity'] = $order[$i]['quantity'];
				$itemMap['restid'] = $order[$i]['restid'];
				$this->cart->addItemToCart( $itemMap );
				unset($itemMap);
			}
			foreach( $subitems as $key=>$row) {
				$itemMap['itemid'] = $row['itemid'];
				$itemMap['sub_item_id'] = $row['sub_item_id'];
				$itemMap['sub_item_key'] = $row['sub_item_key'];
				$itemMap['sub_item_name'] = $row['sub_item_name'];
				$itemMap['sub_item_key'] = $row['sub_item_key'];
				$itemMap['sub_cat_name'] = $row['sub_cat_name'];
				$itemMap['itemset'] = $row['itemset'];
				$itemMap['restid'] = $order[0]['restid'];
				$this->cart->addSubItemsToCart( $itemMap );
				unset($itemMap);
			}
		}
		
		public function get_item_list($restid,$mcatid){
			$time = date('H');
			if( $time >= 19) {
				$slot = 5;
			}
			if( $time <= 15) {
				$slot = 5;
			}
			if( $time >= 15 && $time < 19) {
				$slot = 6;
			}
			$this->load->model('teliphony_model','teliphony');
			return $maincat = $this->teliphony->getPopularItemsByCategory($restid,$mcatid,$slot);
		}
		public function add_item_to_cart($itemid,$quantity,$restid){
			$itemMap['itemid'] = $itemid;
			$itemMap['quantity'] = $quantity;
			$itemMap['restid'] = $restid;
			$this->load->model('orders/cart_model','cart');
			$this->cart->addItemToCart( $itemMap );
		}
		public function get_cart_total($restid,$areaid,$zone_a,$zone_b){
			$this->load->model('orders/cart_model','cart');
			$this->load->model('restaurants/rsearch_model','rsearch');
			$this->load->model('billing/config_model','bconfig');
			$orderCart = $this->cart->getOrderCart( $restid );
			$subcart = $this->cart->getOrderSubItemsBySubcat($restid);
			$subitems = array();
			foreach($orderCart as $key=>$row) {
				$itemid = $row['itemid'];
				$i =0;
				$cnt = 0;
				$subprice = 0;
				foreach($subcart as $key1=>$row1) {
					if( $itemid == $row1['itemid'] && $row1['sub_cat_name'] != '') {
						$subitems[$i]['subcat'] = $row1['sub_cat_name'];
						$subitems[$i]['subitems'] = $row1['subitems'];
						$subitems[$i]['itemset'] = $row1['itemset'];
						$subitems[$i]['subprice'] = $row1['subprice'];
						$subprice = $subprice + $row1['subprice'];
						$i++;
						$cnt++;
					}
				}
				$orderCart[$key]['subcnt'] = $cnt;
				$orderCart[$key]['total'] = $row['total']+$subprice;
				$orderCart[$key]['subitems'] = $subitems;
				unset($subitems);
			}
			
			$amount = 0;
			$glabal_pkg = $this->bconfig->getGlobalPackaging($restid);
			$packaging = $glabal_pkg;
			if( count($orderCart) > 0){
				foreach($orderCart as $key=>$row)	{
					$amount=$amount+$row['total'];
					$packaging = $packaging + $row['packaging'];
				}
			}
			$restproperties = $this->rsearch->getRestaurantTax($restid);
			$tax=($restproperties[0]['tax']/100)*$amount;
			$map = array();
			$map['restid'] = $restid;
			$map['amount'] = $amount+$packaging;
			$map['areaid'] = $areaid;
			$map['zone_a'] = $zone_a;
			$map['zone_b'] = $zone_b;
			$this->load->library('logistics');
			$coordinates = $this->logistics->getCartCoordinates($map);
			$cartmap = array();
			$cartmap['amount'] = $amount;
			$cartmap['deliverycharge'] = round($coordinates['deliverycharge']);
			$cartmap['itemcount'] = count($orderCart);
			$cartmap['items'] = $orderCart;
			$cartmap['packaging'] = round($packaging);
			$cartmap['tax'] = round($tax);
			$cartmap['total'] = round(($amount+$packaging+$tax+$coordinates['deliverycharge']),2);
			return $cartmap;
		}
		
		public function rest_contacts($map){
			$this->load->model('restaurants/rsearch_model','rsearch');
			$this->load->model('general/city_model','city');
			$helpline = $this->city->getCityStdCode($map['cityid']);
			$stdcode = $helpline[0]['stdcode'];
			$restcontact = $this->rsearch->getRestaurantContactInfo($map['restid']);
			$rest_contact = array();
			if(strlen($restcontact[0]['phone1']) == 8 || strlen($restcontact[0]['phone1']) == 10){
				array_push($rest_contact,$restcontact[0]['phone1']);
			}
			if(strlen($restcontact[0]['phone2']) == 8 || strlen($restcontact[0]['phone2']) == 10){
				array_push($rest_contact,$restcontact[0]['phone2']);
			}
			if(strlen($restcontact[0]['phone3']) == 8 || strlen($restcontact[0]['phone3']) == 10){
				array_push($rest_contact,$restcontact[0]['phone3']);
			}
			if(strlen($restcontact[0]['mobile1']) == 8 || strlen($restcontact[0]['mobile1']) == 10){
				array_push($rest_contact,$restcontact[0]['mobile1']);
			}
			if(strlen($restcontact[0]['mobile2']) == 8 || strlen($restcontact[0]['mobile2']) == 10){
				array_push($rest_contact,$restcontact[0]['mobile2']);
			}
			if(strlen($restcontact[0]['mgr_contact1']) == 8 || strlen($restcontact[0]['mgr_contact1']) ==10){
				array_push($rest_contact,$restcontact[0]['mgr_contact1']);
			}
			if(strlen($restcontact[0]['mgr_contact2']) == 8 || strlen($restcontact[0]['mgr_contact2']) == 10){
				array_push($rest_contact,$restcontact[0]['mgr_contact2']);
			}
			if(strlen($restcontact[0]['owner_contact']) == 8 || strlen($restcontact[0]['owner_contact']) == 10){
				array_push($rest_contact,$restcontact[0]['owner_contact']);
			}
			$data['dial'] = $stdcode;
			$data['rest_contact'] = $rest_contact;
			return $data;
		}
		function _get_completion_time($del_time,$curr_time){
			$time = explode(":",$curr_time);
			return  date('H:i',mktime((int)$time[0], (int)$time[1] + $del_time));
		}
		function _mark_advance($del_time,$deltime,$orderdate) {
			$current_time = date('H:i');
			$date = date('Y-m-d');
			if($orderdate === $date) {
				$difference = (strtotime($del_time)-strtotime($current_time))/60;
				if($difference > $deltime) {
					return true;
				}
			}else {
				return true;
			}
			return false;
		}
		public function send_status_email($ordercode){
			$message = '<html><head></head><body>Hi!<br/><br/>';
			$message .= 'Customer wants to know his/her order status.<br/>';
			$message .= 'Customer OrderCode : '.$ordercode.'.<br/>';
			$message .= 'Date & Time : '.date('d-m-Y H:i').'.<br/><br/>';
			$message .= 'Regards,<br/>';
			$message .= 'Team TastyKhana<br/></body></html>';
			$this->load->library('tkemail');
			$this->tkemail->load_order_config();
			$this->tkemail->to = 'wecare@tastykhana.com';
			$this->tkemail->subject = 'Please confirm customer order status';
			$this->tkemail->attachment = 0;
			$this->tkemail->mctag = 'Order Status';
			$this->tkemail->send_email( $message );
		}
	}
	
?>
