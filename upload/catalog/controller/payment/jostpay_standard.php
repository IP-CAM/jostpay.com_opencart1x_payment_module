<?php
###############################################################################
# PROGRAM     : JostPay OpenCart Payment Module                                 #
# DATE	      : 01-10-2014                        				              #
# AUTHOR      : IBUKUN OLADIPO                                                #
# WEBSITE     : http://www.tormuto.com	                                      #
###############################################################################

class ControllerPaymentJOSTPAYStandard extends Controller 
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
		
		$this->data['timeStamp'] = time();
		$this->data['trans_id'] = $trans_id =  time();
		
		if ($this->customer->isLogged())
		{
			//$this->data['jostpay_cust_id'] = $this->customer->getId();
			$this->data['transaction_history_link']=$this->url->link('information/jostpay_standard');
		}
		//else $this->data['jostpay_cust_id'] = date("yms");		
		
		
		$this->data['jostpay_amount'] = $data['ap_amount'] ;
		
		
		$this->data['full_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8')  . ' ' . html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');	
		$this->model_checkout_order->confirm($order_id,$this->config->get('jostpay_standard_pending_status_id'));
                
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
				transaction_id INT NOT NULL,
				approved_amount DOUBLE NOT NULL,
				customer_email VARCHAR(128) NOT NULL,
				response_description VARCHAR(225) NOT NULL,
				response_code TINYINT(1) NOT NULL,
				transaction_amount DOUBLE NOT NULL,
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
		$this->log->write('JOSTPAY_STANDARD :: Debug Info' . $msg);
	}
	
	public function callback() 
	{
		$trans_ref = $this->request->get['ref'];
		
		$order_info=array();
		$order_id="";
	
		if(!empty($trans_ref))$query=$this->db->query("SELECT * FROM ".DB_PREFIX."jostpay_standard WHERE transaction_id='".$this->db->escape($trans_ref)."' LIMIT 1");
		
		if(empty($trans_ref))$toecho="<h3>Transaction reference not supplied!</h3>";
		if(empty($query->row))$toecho="<h3>Transaction record #$trans_ref not found!</h3>";
		elseif(!empty($query->row['response_code']))$toecho="<h3>Transaction Ref $trans_ref has been already processed!</h3>";		
		else
		{
			$order_id=$query->row['order_id'];
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($order_id);
			$order_status_id = $this->config->get('jostpay_standard_failed_status_id');	
			$ap_amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
			
			if(empty($order_info))
			{
				$info="Order info not found";
				$this->notifyAdmin($info);
			}
			else
			{
				$mertid=$this->config->get('jostpay_mert_id');
				$amount=$query->row['transaction_amount'];
				$jostpay_tranx_id=$query->row['transaction_id'];
				//$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
				 
				$temp_amount=floatval($amount);
				
				$url="https://jostpay.com/api_v1?action=get_transaction&jostpay_id=$mertid&ref=$jostpay_tranx_id&amount=$temp_amount";
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
				
				if($returnCode == 200)
				{
					$json=@json_decode($response,true);
				}
				else
				{
					$success=false;
					$json=null;
					$info="Error ($returnCode) accessing jostpay confirmation page";
					//$this->notifyAdmin($info);
					//$order_status_id = $this->config->get('jostpay_pending_order_status_id');
				}
				
				
				if(!empty($json))
				{
					if($json['status_msg']=='COMPLETED')
					{
						$order_status_id = $this->config->get('jostpay_standard_completed_status_id');
						$info="Payment Confirmation Successfull";
						$success=true;
					}
					else//transaction not completed for one reason or the other.
					{
						if($json['status_msg']=='FAILED')$order_status_id = $this->config->get('jostpay_standard_failed_status_id');	
						else $order_status_id = $this->config->get('jostpay_standard_pending_status_id');	
						$info="Payment Not Confirmed: ".$json['info'];
					}
					
					//$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);

					if(!$order_info['order_status_id'])$this->model_checkout_order->confirm($order_id, $order_status_id);
					else $this->model_checkout_order->update($order_id, $order_status_id);		
					

					$this->db->query("UPDATE ".DB_PREFIX."jostpay_standard SET
						approved_amount='".$this->db->escape($json['amount'])."',
						response_code='{$json['status']}',
						response_description='".$this->db->escape($json['info'])."'
						WHERE order_id='$order_id' LIMIT 1");
				}
				
				$this->notifyAdmin("$info , Response: $response");
			}			
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