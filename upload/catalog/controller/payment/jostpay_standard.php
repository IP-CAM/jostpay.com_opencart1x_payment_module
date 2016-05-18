<?php
###############################################################################
# PROGRAM     : JostPay OpenCart Payment Module                                 #
# DATE	      : 01-10-2014                        				              #
# AUTHOR      : IBUKUN OLADIPO                                                #
# WEBSITE     : http://www.tormuto.com	                                      #
###############################################################################

class ControllerPaymentGTBStandard extends Controller 
{
	protected function index() 
	{
		$this->language->load('payment/jostpay_standard');		
		$this->data['text_testmode'] = $this->language->get('text_testmode');		    	
		$this->data['button_confirm'] = $this->language->get('button_confirm');
		$this->data['testmode'] = $this->config->get('jostpay_standard_test');	
		$this->load->model('checkout/order');
		
		$this->data['action'] = '//jostpay.com/sci';		
		$this->data['return'] = $this->url->link('checkout/success');
		$this->data['notify_url'] = $this->url->link('payment/jostpay_standard/callback', '', 'SSL');
		$this->data['cancel_return'] = $this->url->link('checkout/checkout', '', 'SSL');	
		$this->data['order_id'] = $order_id =  $this->session->data['order_id'];
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$data['ap_amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		
		if (!empty($order_info))
		{		

		$this->data['jostpay_mert_id'] =  $this->config->get('jostpay_mert_id');		
		$jostpay_HashKey =  $this->config->get('jostpay_HashKey');
		
		$this->data['timeStamp'] = time();
		$this->data['trans_id'] = $trans_id =  date("ymds");
		
		if ($this->customer->isLogged())
		{
			//$this->data['jostpay_cust_id'] = $this->customer->getId();
			$this->data['transaction_history_link']=$this->url->link('information/jostpay_standard');
		}
		//else $this->data['jostpay_cust_id'] = date("yms");		
		
		
		$this->data['jostpay_amount'] = $data['ap_amount'] ;
		
		$this->data['jostpay_tranx_hash'] = hash ('sha512', $trans_id . '+'. $this->data['jostpay_amount'] . '+'.$this->data['notify_url'] .'+'. $jostpay_HashKey );
		
		$this->data['full_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8')  . ' ' . html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');	
		$this->model_checkout_order->confirm($order_id,$this->config->get('jostpay_standard_pending_status_id'));
                
		
			$this->data['business'] = $this->config->get('jostpay_standard_email');
			$this->data['item_name'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');				
			
			$this->data['products'] = array();
			
			foreach ($this->cart->getProducts() as $product)
			{
				$option_data = array();
	
				foreach ($product['option'] as $option) 
				{
					if ($option['type'] != 'file')$value = $option['option_value'];	
					else 
					{
						$filename = $this->encryption->decrypt($option['option_value']);						
						$value = utf8_substr($filename, 0, utf8_strrpos($filename, '.'));
					}
										
					$option_data[] = array(
						'name'  => $option['name'],
						'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
					);
				}
				
				$this->data['products'][] = array(
					'name'     => $product['name'],
					'model'    => $product['model'],
					'price'    => $this->currency->format($product['price'], $order_info['currency_code'], false, false),
					'quantity' => $product['quantity'],
					'option'   => $option_data,
					'weight'   => $product['weight']
				);
			}
			
			$this->data['discount_amount_cart'] = 0;
			
			$total = $this->currency->format($order_info['total'] - $this->cart->getSubTotal(), $order_info['currency_code'], false, false);

			if ($total > 0)
			{
				$this->data['products'][] = array(
					'name'     => $this->language->get('text_total'),
					'model'    => '',
					'price'    => $total,
					'quantity' => 1,
					'option'   => array(),
					'weight'   => 0
                      
				);	
			} else $this->data['discount_amount_cart'] -= $total;
			
			$this->data['first_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');	
			$this->data['last_name'] = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');	
			$this->data['address1'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');	
			$this->data['address2'] = html_entity_decode($order_info['payment_address_2'], ENT_QUOTES, 'UTF-8');	
			$this->data['city'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');	
			$this->data['zip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');	
			$this->data['country'] = $order_info['payment_iso_code_2'];
			$this->data['email'] = $order_info['email'];
			$this->data['invoice'] = $this->session->data['order_id'] . ' - ' . html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8') . ' ' . html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
			$this->data['lc'] = $this->session->data['language'];

			if (!$this->config->get('jostpay_standard_transaction'))$this->data['paymentaction'] = 'authorization';
			else $this->data['paymentaction'] = 'sale';
			
			$this->data['custom'] = $this->session->data['order_id'];
			
	//CUSTOM DATABASE LOGGIN			
		$sql="CREATE TABLE IF NOT EXISTS ".DB_PREFIX."jostpay_standard(
				id int not null auto_increment,
				primary key(id),
				order_id INT NOT NULL,unique(order_id),
				date_time datetime,
				transaction_id VARCHAR(48),
				approved_amount VARCHAR(12),
				customer_email VARCHAR(68),
				response_description VARCHAR(225),
				response_code VARCHAR(5),
				transaction_amount varchar(12),
				customer_id INT
				)";
		$this->db->query($sql);
		$customer_id=$this->customer->isLogged()?$this->customer->getId():"";
		
		$this->db->query("INSERT INTO ".DB_PREFIX."jostpay_standard
		(order_id,transaction_id,date_time,transaction_amount,
		customer_email,customer_id) 
		VALUES
		('$order_id','$trans_id',NOW(),'{$data['ap_amount']}',
		'".$this->db->escape($order_info['email'])."','$customer_id')");
			
		
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/jostpay_standard.tpl')) {
				$this->template = $this->config->get('config_template') . '/template/payment/jostpay_standard.tpl';
			} else {
				$this->template = 'default/template/payment/jostpay_standard.tpl';
			}
			$this->render();
		}else echo "empty order info";
	}
	
	
	function notifyAdmin($title="")
	{
		if(!$this->config->get('jostpay_standard_debug'))return;
		$post_data=json_encode($this->request->post);
		$msg="$title<br/>Post Data: $post_data";
		$this->log->write('GTB_STANDARD :: Debug Info' . $msg);
	}
	
	public function callback() 
	{
		$trans_ref = $this->request->post['ref'];
		
		$post_data=
			array(
				'action'=>'get_transaction',
				'jostpay_id'=>$configs['jostpay_merchant'],
				'ref'=>$trans_ref,
				//'amount'=>'1000',  //the amount that you are expecting (this is optional but important)
			);
						
		$json=$this->general_model->_curl_json('https://jostpay.com/api_v1',$post_data,true);
		
		if(!empty($json['error']))$json_data['info']=$json['error'];
		else
		{
			$json_data['approved_amount']=$json['amount'];
			$json_data['response_description']=$json['info'];
			
			
			if($json['status_msg']=='FAILED')$new_status=-1;
			elseif($json['status_msg']=='COMPLETED')
			{
				if(floatval($json['amount'])<$expected_deposit){
					$json_data['response_description']="Incorrect deposit amount ($expected_deposit NGN was expected, but {$json['amount']} NGN found). ";
					$new_status=-1;
				}
				else $new_status=1;	
			}
		}
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		$order_id=$this->request->post['jostpay_cust_id'];
		$this->load->model('checkout/order');
		
		
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$ap_amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		
		$order_status_id = $this->config->get('jostpay_standard_failed_status_id');	
		$success=false;
		
		if(empty($order_info))
		{
			$info="Order info not found";
			$this->notifyAdmin($info);
		}
		elseif($this->request->post['jostpay_tranx_status_code']!='00')
		{
			$info=$this->request->post['jostpay_tranx_status_msg'];
			$this->notifyAdmin($info);
		}
		elseif(floatval($this->request->post['jostpay_tranx_amt'])<floatval($ap_amount))
		{
			$info="Amount paid {$this->request->post['jostpay_tranx_amt']} NGN is different from the expected payment amount {$ap_amount}NGN.";
			$this->notifyAdmin($info);
		}
		else
		{
			
			$order_status_id = $this->config->get('jostpay_standard_completed_status_id');
			$info=$this->request->post['jostpay_tranx_status_msg'];
			$success=true;
			$this->notifyAdmin("$info , Response: $response");
					
			$mertid=$this->config->get('jostpay_mert_id');
			$hashkey=$this->config->get('jostpay_HashKey');
			$amount=$ap_amount * 100 ;
			
			$hash=hash("sha512","$mertid+$jostpay_tranx_id+$hashkey");
			$url="http://gtweb2.gtbank.com/JostPayService/gettransactionstatus.json?mertid=$mertid&amount=$amount&tranxid=$jostpay_tranx_id&hash=$hash";
			$ch = curl_init();
			//	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);			
				curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $url);
				
				$response = curl_exec($ch);
				$returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				
			if($returnCode == 200)$json=@json_decode($response,true);
			else
			{				
				$json=null;
				$info="Error ($returnCode) accessing jostpay confirmation page";
			}
			
			
			if(empty($json))
			{
				$info="Error verifying";
				$this->notifyAdmin("Error verifying $url, Response: $response");
			}
			else
			{
				if($json['ResponseCode']=='00')
				{
					$order_status_id = $this->config->get('jostpay_standard_completed_status_id');
					$info="Payment Confirmation Successfull";
					$success=true;
					$this->notifyAdmin("$info , Response: $response");
				}
				else//transaction not completed for one reason or the other.
				{
					$order_status_id = $this->config->get('jostpay_standard_failed_status_id');	
					$info="Confirmation failed: ".$json['ResponseDescription'];
					$this->notifyAdmin("$info $url, Response: $response");					
				}
					
			}
		}
		
		
		if(!empty($order_info))
		{
			
			if(!$order_info['order_status_id'])$this->model_checkout_order->confirm($order_id, $order_status_id);
			else $this->model_checkout_order->update($order_id, $order_status_id);
				
			$status=$success?'completed':'failed';
			
			$this->db->query("UPDATE ".DB_PREFIX."jostpay_standard SET
				approved_amount='".$this->db->escape($this->request->post['jostpay_tranx_amt'])."',
				response_code='".$this->db->escape($this->request->post['jostpay_tranx_status_code'])."',
				response_description='".$this->db->escape($this->request->post['jostpay_tranx_status_msg'])."'
				WHERE order_id='$order_id'");
		}
		
       $this->document->setTitle("JostPay Order Payment: $info");

		$this->data['breadcrumbs'] = array(); 
		$this->data['breadcrumbs'][] = array(
			'text'			=> $this->language->get('text_home'),
			'href'			=> $this->url->link('common/home'),           
			'separator'		=> false
		);
		$this->data['breadcrumbs'][] = array(
			'text'			=> "JostPay Payment Callback",
			'href'      	=> "",
			'separator' 	=> $this->language->get('text_separator')
		);   
      
		 $this->children = array
		  (
			 'common/column_left', 
			 'common/column_right',
			 'common/content_top',
			 'common/content_bottom',
			 'common/footer', 
			 'common/header'
		  );
		  
		$toecho= "
					<style type='text/css'>
					.errorMessage,.successMsg
					{
						color:#ffffff;
						font-size:18px;
						font-family:helvetica;
						border-radius:9px;
						display:inline-block;
						max-width:350px;
						border-radius: 8px;
						padding: 4px;
						margin:auto;
					}
					
					.errorMessage{background-color:#ff3300;}
					
					.successMsg{background-color:#00aa99;}
					
					body,html{min-width:100%;}
				</style>
				";
		
		if ($this->customer->isLogged())
		{
			$transaction_history_link=$this->url->link('information/jostpay_standard');
			$dlink="<a href='$transaction_history_link'>CLICK TO VIEW TRANSACTION DETAILS</a>";
		}
		else
		{
			$home_url=$this->url->link("common/home",'', 'SSL');
			$dlink="<a href='$home_url'>CLICK TO RETURN HOME</a>";
		}
		
		
		if($success)
		{
		
			$toecho.="<div class='successMsg'>
					$info<br/>
					Your order has been successfully Processed <br/>
					ORDER ID: $order_id<br/>
					$dlink</div>";
		}
		else
		{
			$toecho.="<div class='errorMessage'>
					Your transaction was not successful<br/>
					REASON: $info<br/>
					ORDER ID: $order_id<br/>
					$dlink</div>";
		}
		
		$this->data['oncallback']=true;
		$this->data['toecho']=$toecho;
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/jostpay_standard.tpl')) {
				$this->template = $this->config->get('config_template') . '/template/payment/jostpay_standard.tpl';
			} else {
				$this->template = 'default/template/payment/jostpay_standard.tpl';
			}
		$this->response->setOutput($this->render());
	}
}
?>