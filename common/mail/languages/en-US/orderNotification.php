<?php 
use common\models\order\Order;
use common\enums\OrderStatusEnum;
use common\helpers\ImageHelper;
use common\enums\ExpressEnum;
use common\helpers\AmountHelper;

$order_id = $code;
$order = Order::find()->where(['id'=>$order_id])->one();

\Yii::$app->language = $order->language;
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Order</title>
		<style type="text/css">
body{font-family:"microsoft yahei";}.qmbox *{margin:0;padding:0;box-sizing:border-box;}.btn{color:#000 !important;}.qmbox ul,.qmbox ol,.qmbox li,.qmbox em,.qmbox i{font-style:normal;list-style:none;}.qmbox a:hover,.qmbox   a:visited,.qmbox a:link,.qmbox a:active{text-decoration:none;}.qmbox .Mail{max-width:640px;width:100%;margin:0 auto;background:#F6F6F6;}.qmbox .Head{width:100%;height:100px;padding:20px 0;text-align:center;}.qmbox .Head .logo{display:block;font-family:'Times New Roman',Times,serif;font-size:28px;font-weight:bold;}.qmbox .Head .sign{font-size:9px;color:#666;}.qmbox .Main .info{background:#fff;padding:15px;}.qmbox .Main .info dl dt{padding:10px 0;color:#333;font-weight:bold;font-size:12px;}.qmbox .Main .info dl dd{color:#666;font-size:10px;line-height:20px;}.qmbox .Main .info dl dd p{padding:6px 0;}.qmbox .Main .info dl:last-child dd{line-height:25px;}.qmbox .Main .info dl .pay{margin-left:60px;border-top:#ddd 1px solid;}.qmbox .Main .info dl dd a{color:#A0827B;text-decoration:underline;}.qmbox .Main .info dl dd i{color:#EA4A4A;float:right;}.qmbox .Main .info dl dd .over{color:#999;}.qmbox .Main .info dl dd em{color:#1780F5;margin-left:10px;}.qmbox .Main .info dl dd .orderno{color:#947465;}.qmbox .Main .list ul{background:#fff;margin:15px;padding:10px 15px;border-radius:5px;}.qmbox .Main .list ul li{padding:5px 0;display:flex;justify-content:flex-start;}.qmbox .Main .list ul li dl{overflow:hidden;}.qmbox .Main .list ul li dl dt{width:60px;height:60px;margin-right:20px;}.qmbox .Main .list ul li dl dt img{width:100%;}.qmbox .Main .list ul li dl dt em{padding:0 8px;color:#947465;background:#F5F0EC;border:#D7CAC4 1px solid;border-radius:3px;font-size:11px;}.qmbox .Main .list ul li dl dd{line-height:20px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}.qmbox .Main .list ul li dl .good{font-size:14px;color:#333;line-height:16px;}.qmbox .Main .list ul li dl .attr{font-size:12px;color:#999;line-height:14px;margin:16px 0 10px;}.qmbox .Main .list ul li dl .price{font-size:14px;color:#F3A391;line-height:14px;}.qmbox .Main .list ol{background:#fff;margin:15px;padding:10px 15px;border-radius:5px;}.qmbox .Main .list ol li{line-height:25px;}.qmbox .Main .list ol li dl{text-align:left;}.qmbox .Main .list ol li p{text-align:center;}.qmbox .Main .list ol li .sum{font-size:12px;}.qmbox .Main .list ol li .sum{font-size:12px;}.qmbox .Main .list ol li .count{padding:5px 0;border-top:#ddd 1px solid;}.qmbox .Main .list ol li dt .total{color:#F29B87;}.qmbox .Main .list ol li dt .total .pay{color:#F29B87;font-size:18px;}.qmbox .Main .list ol li .num{font-size:10px;}.qmbox .Main .list dt em,.qmbox .Main .list dd em{float:right;}.qmbox .Main .list ol li dd .discount{color:#947465;}.qmbox .Main .list ol li a{text-decoration:none;}.qmbox .Main .list ol li .btn{width:200px;height:40px;padding:7px 0;margin:5px auto;text-align:center;color:#fff;background:#F29B87;}.qmbox .Main .mind{background:#fff;padding:15px;}.qmbox .Main .mind dl{line-height:20px;font-size:9px;color:#333;}.qmbox .Main .mind dl dt{font-weight:bold;padding:10px 0;}.qmbox .Main .mind dl dd{padding-bottom:10px;}.qmbox .Main .mind dl dd a{color:#A0827B;}.qmbox .Foot{padding:15px;}.qmbox .Foot .image img{width:100%;display:block;}.qmbox .Foot .intro{padding:30px;font-size:9px;color:#666;}.qmbox .Foot .intro dl{text-align:center;border-bottom:#ccc 1px solid;}.qmbox .Foot .intro dl dt{padding:15px 0;}.qmbox .Foot .intro dl dd{padding:15px 0;}.qmbox .Foot .intro dl dd img{width:20px;height:20px;margin:0 20px;}.qmbox .Foot .intro .type{padding:15px 0;text-align:center;}.qmbox .Foot .intro .type li{display:inline-block;list-style:none;padding:0 20px;}.qmbox .Foot .intro .type li:not(:last-child){border-right:#999 1px solid;}.qmbox .Foot .intro .copy{line-height:20px;text-align:center;color:#999;}.qmbox .Foot .intro .copy a{color:#A0827B;text-decoration:underline;}.qmbox .Foot .intro .copy em{font-size:7px;}
		</style>
	</head>
	<body>
		<div class="qmbox">
			<div class="Mail" id="app">
				<div class="Head">
					<span class="logo">BDD Co.</span>
					<em class="sign">Superior quality diamond online shop | Jewelry expert</em>
				</div>
				<div class="Main">
					<div class="info">
						<dl>
							<dt>Dear customers：</dt>
							<?php if(
							        $order->order_status == OrderStatusEnum::ORDER_UNPAID ||
                                    $order->order_status == OrderStatusEnum::ORDER_CANCEL && $order->refund_status
                            ) {?>
							<dd>Thank you for choosing BDD Co. We attach great importance to your order. Please read all the email about the order carefully. If the data is wrong, please contact us immediately by email <a href="mailto:service@bddco.com" rel="noopener" target="_blank">service@bddco.com</a>。</dd>
							<?php } elseif($order->order_status == OrderStatusEnum::ORDER_PAID){?>
							<dd>Your order has been paid successfully! Thank you for choosing BDD Co. We attach great importance to your order, we have arranged for you as soon as possible, and we will send it to you at the first time when the product test is correct. If you have any questions, please contact us immediately by email to <a href="mailto:service@bddco.com" rel="noopener" target="_blank">service@bddco.com</a>。</dd>
							<?php } elseif($order->order_status == OrderStatusEnum::ORDER_SEND){?>
							<dd>Your order has been delivered successfully! Thank you for choosing BDD Co. If you have any questions, please contact us immediately by email <a href="mailto:service@bddco.com" rel="noopener" target="_blank">service@bddco.com</a>。</dd>
							<?php }?>							
						</dl>
						<dl>
							<dt>Order details</dt>
							<dd>
                                <span>Payment information：</span><?php if($order->refund_status) { ?><span><i><?= \common\enums\PayStatusEnum::getValue($order->refund_status,'refund') ?></i></span>
                                <?php } else { ?><span>Online payment<i><?= OrderStatusEnum::getValue($order->order_status) ?></i></span><?php } ?>
							</dd>
							<dd><span>Order NO：</span><span class="orderno"><?= $order->order_sn ?></span></dd>							
							<?php if($order->order_status == OrderStatusEnum::ORDER_UNPAID) {?>
							<dd><span>Order Time：</span><span><?= \Yii::$app->formatter->asDatetime($order->created_at); ?></span></dd>
							<?php }elseif($order->order_status == OrderStatusEnum::ORDER_PAID) {?>
							<dd><span>Payment time：</span><span><?= \Yii::$app->formatter->asDatetime($order->payment_time); ?></span></dd>
                            <?php }elseif($order->order_status == OrderStatusEnum::ORDER_CANCEL) {?>
                                <dd><span>Order Status：</span><span>Closed</span></dd>
							<?php }elseif($order->order_status == OrderStatusEnum::ORDER_SEND) {?>
							<dd><span>Logistics Company：</span><span><?= \Yii::$app->services->express->getExressName($order->express_id,$order->language);?></span></dd>
							<dd><span>Logistics NO：</span><span><?= $order->express_no; ?></span></dd>
							<dd><span>Delivery Time：</span><span><?= \Yii::$app->formatter->asDatetime($order->delivery_time); ?></span></dd>
							<?php }?>
						</dl>
					</div>
					<div class="list">
					  <?php
					  $currency = $order->account->currency;
					  $exchange_rate = $order->account->exchange_rate;

					  $goods_list = $order->goods;
					  if(is_array($goods_list) && !empty($goods_list)) {
					     foreach ($goods_list as $goods){
					         $goods_attr = '';
					         if($goods->goods_spec){
					             $goods->goods_spec = \Yii::$app->services->goods->formatGoodsSpec($goods->goods_spec);
					             foreach ($goods->goods_spec as $vo){
					                 $goods_attr .= $vo['attr_name'].":".$vo['attr_value']."&nbsp;";
					             }
					         }
					      ?>
					      <ul>
						  <li>
								<dl>
									<dt><img src="<?= ImageHelper::thumb($goods->goods_image)?>"></dt>
								</dl>
								<dl>
									<dd class="good"><?= $goods->lang->goods_name?></dd>
									<dd class="attr"><?= $goods_attr?></dd>
									<dd class="price"><?= AmountHelper::outputAmount($goods->goods_price,2,$currency)?></dd>
								</dl>
							</li>
						</ul>
					      <?php
					     }
					  }          
					  ?>
						<ol>
							<li>
								<dl>
                                    <dt class="sum"><span>Qty of commodities：</span><em><?= count($order->goods)?></em></dt>
									<dt class="sum"><span>Goods Amount：</span><em><?= AmountHelper::outputAmount($order->account->goods_amount,2,$currency)?></em></dt>
									<dd class="num"><span>Freight：</span><em>+<?= AmountHelper::outputAmount($order->account->shipping_fee,2,$currency)?></em></dd>
									<dd class="num"><span>Taxation：</span><em>+<?= AmountHelper::outputAmount($order->account->tax_fee,2,$currency)?></em></dd>
									<dt class="count"><span>Order Amount：</span><em class="total"><?= AmountHelper::outputAmount($order->account->order_amount,2,$currency)?></em></dt>
                                    <dd class="num"><span>Discount Amount：</span><em class="discount">-<?= AmountHelper::outputAmount($order->account->discount_amount,2,$currency)?></em></dd>
                                    <dd class="num"><span>Coupon：</span><em class="discount">-<?= AmountHelper::outputAmount($order->account->coupon_amount,2,$currency)?></em></dd>
                                    <?php
                                    if($order->cards) {
                                        foreach ($order->cards as $card) {
                                            if($card->type!=2) {
                                                continue;
                                            }
                                            ?>
                                            <dd class="num"><span>Gift Card (<?= $card->card->sn ?>)：</span><em>-<?= AmountHelper::outputAmount(abs($card->use_amount),2,$currency)?></em></dd>
                                        <?php }} ?>
									<?php if($order->order_status == OrderStatusEnum::ORDER_PAID || $order->refund_status) {?>
									<dt class="count"><span>Pay Amount：</span><em class="total"><?= AmountHelper::outputAmount($order->account->paid_amount,2, $order->account->paid_currency)?></em></dt>
                                    <?php } elseif($order->order_status == OrderStatusEnum::ORDER_UNPAID) {?>
                                        <dt class="count"><span>Total Amount：</span><em class="total"><?php
                                                if($currency==\common\enums\CurrencyEnum::TWD) {
                                                    ?><?= AmountHelper::outputAmount(intval($order->account->pay_amount),2,$currency)?><?php
                                                } else {
                                                    ?><?= AmountHelper::outputAmount($order->account->pay_amount,2,$currency)?><?php } ?></em></dt>
                                    <?php }?>
                                    <?php if($order->refund_status) { ?>
                                        <dt class="count"><span>Refunded：</span><em class="total"><?php
                                                if($currency==\common\enums\CurrencyEnum::TWD) {
                                                    ?><?= AmountHelper::outputAmount(intval($order->account->pay_amount),2,$currency)?><?php
                                                } else {
                                                    ?><?= AmountHelper::outputAmount($order->account->pay_amount,2,$currency)?><?php } ?></em></dt>
                                    <?php } ?>
								</dl>
								<?php if($order->order_status == OrderStatusEnum::ORDER_UNPAID) {?>
								<a href="<?= \Yii::$app->params['frontBaseUrl']?>/payment-options?orderId=<?= $order->id?>&price=<?= sprintf("%.2f",$order->account->order_amount)?>&coinType=<?= $currency?>" style="text-decoration:none" target="_blank"><div class="btn">Pay</div></a>
							    <?php } else {?>
							    <a href="<?= \Yii::$app->params['frontBaseUrl']?>/account/order-details?orderId=<?= $order->id?>" style="text-decoration:none" target="_blank"><div class="btn">View</div></a>
							    <?php }?>
							</li>
						</ol>
					</div>
					<div class="mind">
						<dl>
							<dt>Notes</dt>
							<dd>
								As each diamond is unique and our daily order volume is quite large, we need to further confirm and check for customers who have purchased diamonds to ensure that the diamond is indeed in stock.
At this stage, your order will be kept temporarily for up to 48 business hours. Once the inventory is confirmed, we will email you the date of shipment. If you pay by bank wire transfer, we will provide further wire transfer instructions once the inventory is confirmed. To ensure that delivery is not delayed, please export the payment as soon as possible after receiving the wire transfer instruction. Please note that it usually takes 24 hours for the payment to arrive at our bank from the bank you exported. The goods will be sent after the receipt of payment is confirmed.
							</dd>
						</dl>
					</div>
					</div>
				<div class="Foot">
					<div class="intro">
						<ul class="type">
							<li>Wedding Ring</li>
							<li>Engagement Ring</li>
							<li>Jewelry</li>
						</ul>
						<div class="copy">
							<p>If you have any feedback or suggestions on bddco's products, or if you have any problems using it</p>
							<p>Feel free to contact us：<a href="mailto:service@bddco.com" rel="noopener" target="_blank">service@bddco.com</a></p>
							<em>Copyright ©<?= date("Y")?> BDD Co., Ltd.</em>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
