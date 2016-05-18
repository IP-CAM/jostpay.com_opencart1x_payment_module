<?php if ($testmode) { ?>
<div class="warning"><?php echo $text_testmode; ?></div>
<?php } 
if(isset($oncallback))
{
 echo $header; ?><?php echo $column_left; ?><?php echo $column_right; ?>
<div id="content">
	<?php echo $content_top; ?>
	
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb) { ?>
			<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php } ?>
	</div>

	<?php echo $toecho; ?>
	<?php echo $content_bottom; ?>
</div>
<?php echo $footer; 

}
else
{

if(!empty($transaction_history_link))
{
?>
 <div>
 Copy this url to access the Transaction History.<br/>
 <a href='<?php echo $transaction_history_link; ?>'><?php echo $transaction_history_link;?></a>
 </div>
 <?php
 }
 ?>
<form action="<?php echo $action; ?>" method="post">
	<input type='hidden' name='amount' value='<?php echo $jostpay_amount ; ?>' />
	<input type='hidden' name='merchant' value='<?php echo $jostpay_mert_id;?>' />
	<input type='hidden' name='ref' value='<?php echo $trans_id; ?>' />
	<input type='hidden' name='memo' value="<?php echo "Payment for Order #$order_id by $full_name"; ?>" />
	<input type='hidden' name='notification_url' value='<?php echo $notify_url;?>' />
	<input type='hidden' name='success_url' value='<?php echo $notify_url;?>' />
	<input type='hidden' name='cancel_url' value='<?php echo $notify_url;?>' />
  <?php 
  echo"<b> Note : </b> If you Choose Payment Gateway as JostPay, then all other currency format converted into Naira (NGN / N) at the time of Payment!";
  
  $TOTAL_VALUE=0;
  $comments="";
  foreach ($products as $product) 
  {
    $comments.=$product['name']."(".$product['model']."-".$product['quantity']."-".$product['weight']."-N ".$product['price'].")";
    $TOTAL_VALUE+=($product['price']*$product['quantity']);  
       
  }
  ?>

  <?php
 
  
 if(!empty($currency_code) && ($currency_code != 'NGN' || $currency_code != strtolower('NGN')))
    {
   function currencyConverterRate($from_currency,$to_currency)
	{
        //Yahoo Finance    
        $url = 'http://download.finance.yahoo.com/d/quotes.csv?s='.$from_currency.$to_currency.'=X&f=sl1d1t1ba&e=.csv';
        $handle = @fopen($url, 'r');
        if($handle)
        {
            $result = fgets($handle, 4096);
            fclose($handle);
        }
        $currencyData = explode(',',$result);
        return $currencyData[1];
	}
$converted_value = currencyConverterRate("$currency_code","ngn");
$TOTAL_VALUE = $TOTAL_VALUE*$converted_value;
$TOTAL_VALUE=round($TOTAL_VALUE,2);
    }
 
  ?>
  <div class="buttons">
    <div class="right">
      <input type="submit" value="<?php echo $button_confirm; ?>" class="button" />
    </div>
  </div>
</form>
<?php
}
?>