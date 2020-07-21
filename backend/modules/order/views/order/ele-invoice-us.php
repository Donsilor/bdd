<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title></title>

    <link href="/resources/css/invoice.css" rel="stylesheet">
</head>
<body>

<div class="scroll" id="wdf">
    <!--startprint1-->

    <!--打印内容开始-->

    <div class="template">
        <div class="clf">
            <div class="invoice-data-img fl"></div>
            <div class="invoice-data fr">
                <div class="invoice-data-topcolor">Invoice</div>
                <div class="invoice-data-b clf">
                    <div class="invoice-data-l fl">
                        <div class="invoice-data-type">Invoice Date</div>
                        <div class="invoice-data-val"><?php echo $result['invoice_date'];?></div>
                    </div>
                    <div class="invoice-data-r fl">
                        <div class="invoice-data-type">page</div>
                        <div class="invoice-data-val">1 of 1</div>
                    </div>
                </div>
                <div class="invoice-data-b clf" style="border-top: 1px solid #333;padding: 10px;">
                    Invoice No.:<?php echo $result['order_sn'];?>
                </div>
            </div>
            <div class="fl" style="margin: 20px 0px;width: 300px;">
                <div>Website:<?php echo $result['siteInfo']['webSite']??'';?></div>
                <div>Email:<?php echo $result['siteInfo']['email']??'';?></div>
                <div>Tel:<?php echo $result['siteInfo']['tel']??'';?></div>
            </div>
        </div>


        <div class="site-type clf">
            <div class="list fl clf">
                <div class="list-tit fl">Sales:</div>
                <div class="list-details fl">
                    <div class="child-name">BDD Co.</div>
                    <div class="child-addr"><?php echo $result['sender_address']?:'Unit 2304, 23/F,<br/>
                        Universal Trade Centre,<br/>
                        3 Arbuthnot Road,<br/>
                        Central, Hong Kong'; ?></div>
                </div>
            </div>

            <div class="list fl clf">
                <div class="list-tit fl">Customer:</div>
                <div class="list-details fl">
                    <div class="child-name"><?php echo $result['realname'];?></div>
                    <div class="child-name"><?php echo $result['mobile'];?></div>
                    <div class="child-addr"><?php echo $result['address_details'];?></div>
                </div>
            </div>
        </div>

        <div class="package-information">
            <div class="package-tit">Order Information</div>
            <div class="package-info clf">
                <div class="package-child fl">
                    <div class="package-child-v">Waybill No.</div>
                    <div class="package-child-val"><?php echo $result['express_no'];?></div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">Carrier</div>
                    <div class="package-child-val"><?php echo $result['express_company_name'];?></div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">Payment method</div>
                    <div class="package-child-val"><?php echo Yii::t('pay', \common\enums\PayEnum::getValue($result['payment_type'], "payTypeName"), [], $result['language']);//$result['delivery_time'];?></div>
                </div>
            </div>
            <div class="package-info clf">
                <div class="package-child fl">
                    <div class="package-child-v">Place of Export</div>
                    <div class="package-child-val"><?php echo $result['sender_area']?:'Hong Kong'; ?></div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">Currency Of Sale</div>
                    <div class="package-child-val"><?php echo $result['currency'];?></div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">Destination</div>
                    <div class="package-child-val"><?php echo $result['country'];?></div>
                </div>
            </div>
        </div>

        <table cellspacing="0" cellpadding="0" width="100%" border="1" rules="cols">
            <tr>
                <th width="32%">Item Description</th>
                <th width="20%">Stock No.</th>
                <th width="10%">Qty</th>
                <th width="15%">Unit Price</th>
                <th width="15%">Total Amt.</th>
            </tr>
            <?php foreach ($result['order_goods'] as $val){ ?>
            <tr>
                <td><?php echo $val['goods_name'];?></td>
                <td><?php echo $val['goods_sn'];?></td>
                <td><?php echo $val['goods_num'];?></td>
                <td><?php echo $val['goods_price']. " ".$val['currency']; ?></td>
                <td><?php echo $val['goods_price']*$val['goods_num'] . " ".$val['currency']; ?></td>
            </tr>
            <?php } ?>

        </table>

        <div class="signature clf">
            <div class="signature-name fl">
                <div class="signature-t"><div class="signature-t-img"></div></div>
                <div class="text">Signature of shipper/Exporter</div>
            </div>
            <div class="signature-date fl">
                <div class="signature-t"><?php echo $result['invoice_date'];?></div>
                <div class="text">Date</div>
            </div>
        </div>

        <div style="height: 200px;">
            <div style="width: 280px;height: 200px;position: relative" class="fl">
                <div class="total clf" style="text-align: left;word-break:break-all;margin: 0;position: absolute;bottom:0px;">
                    <br/><br/><br/>If you have any questions, please contact our Customer Service Associate by<br/>
                    sending e-mail to service@bddco.com<br/>
                    Thank you for choosing BDD Co.
                </div>
            </div>
            <div style="width: 350px;height: 200px;" class="fr">
                <div class="total clf">
                    <div class="fr clf">
                        <div class="fr total-val"><?php echo $result['currency'] .' '.$result['order_amount']; ?> </div>
                        <div class="fr total-bg">Subtotal</div>
                    </div>
                </div>

                <div class="total clf">
                    <div class="fr clf">
                        <div class="fr total-val"> - <?php echo $result['currency'] .' '.$result['coupon_amount']; ?></div>
                        <div class="fr total-bg">Coupon discount</div>
                    </div>
                </div>

                <div class="total clf">
                    <div class="fr clf">
                        <div class="fr total-val"> - <?php echo $result['currency'] .' '.$result['gift_card_amount']; ?></div>
                        <div class="fr total-bg">Shopping card</div>
                    </div>
                </div>

                <div class="total clf">
                    <div class="fr clf">
                        <div class="fr total-val"><?php echo $result['currency'] .' '.$result['order_pay_amount']; ?></div>
                        <div class="fr total-bg">Total</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--打印内容结束-->

    <!--endprint1-->
</div>



</body>
</html>
