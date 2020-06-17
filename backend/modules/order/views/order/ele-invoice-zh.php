<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title></title>
    <link href="/backend/resources/css/invoice.css" rel="stylesheet">
</head>
<body>

<div class="scroll" id="wdf">
    <!--startprint1-->

    <!--打印内容开始-->

    <div class="template">
        <div class="clf">
            <div class="invoice-data-img fl"></div>

            <div class="invoice-data fr">
                <div class="invoice-data-topcolor">发票</div>
                <div class="invoice-data-b clf">
                    <div class="invoice-data-l fl">
                        <div class="invoice-data-type">发货日期</div>
                        <div class="invoice-data-val"><?php echo $result['invoice_date'];?></div>
                    </div>
                    <div class="invoice-data-r fl">
                        <div class="invoice-data-type">页码</div>
                        <div class="invoice-data-val">1 of 1</div>
                    </div>
                </div>
                <div class="invoice-data-b clf" style="border-top: 1px solid #333;padding: 10px;">
                    发票号码:<?php echo $result['order_sn'];?>
                </div>
            </div>

            <div class="fl" style="margin: 20px 0px;width: 300px;">
                <div>网址:<?php echo $result['siteInfo']['webSite']??'';?></div>
                <div>邮箱:<?php echo $result['siteInfo']['email']??'';?></div>
                <div>电话:<?php echo $result['siteInfo']['tel']??'';?></div>
            </div>
        </div>

        <div class="site-type clf">
            <div class="list fl clf">
                <div class="list-tit fl">销售商:</div>
                <div class="list-details fl">
                    <div class="child-name"><?php echo $result['realname'];?></div>
                    <div class="child-addr"><?php echo $result['address_details'];?></div>
                </div>
            </div>

            <div class="list fl clf">
                <div class="list-tit fl">客户地址:</div>
                <div class="list-details fl">
                    <div class="child-name"><?php echo $result['realname'];?></div>
                    <div class="child-addr"><?php echo $result['address_details'];?></div>
                </div>
            </div>
        </div>

        <div class="package-information">
            <div class="package-tit">订单信息</div>
            <div class="package-info clf">
                <div class="package-child fl">
                    <div class="package-child-v">提单号码</div>
                    <div class="package-child-val"><?php echo $result['express_no'];?></div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">运输公司</div>
                    <div class="package-child-val"><?php echo $result['express_company_name'];?></div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">支付方式</div>
                    <div class="package-child-val"><?php echo Yii::t('pay', \common\enums\PayEnum::getValue($result['payment_type'], "payTypeName"), [], $result['language']);//$result['delivery_time'];?></div>
                </div>
            </div>
            <div class="package-info clf">
                <div class="package-child fl">
                    <div class="package-child-v">出口地</div>
                    <div class="package-child-val">香港</div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">交易币种</div>
                    <div class="package-child-val"><?php echo $result['currency'];?></div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">目的地</div>
                    <div class="package-child-val"><?php echo "香港";//$result['country'];?></div>
                </div>
            </div>
        </div>

        <table cellspacing="0" cellpadding="0" width="100%" border="1" rules="cols">
            <tr>
                <th width="">商品描述</th>
                <th width="20%">款号</th>
                <th width="8%">数量</th>
                <th width="15%">单价</th>
                <th width="15%">总金额</th>
            </tr>
            <?php foreach ($result['order_goods'] as $val){ ?>
            <tr>
                <td><?php echo $val['goods_name'];?></td>
                <td><?php echo $val['goods_sn'];?></td>
                <td><?php echo $val['goods_num'];?></td>
                <td><?php echo $val['goods_pay_price']. " ".$val['currency']; ?></td>
                <td><?php echo $val['goods_pay_price']*$val['goods_num'] . " ".$val['currency']; ?></td>
            </tr>
            <?php } ?>

        </table>

        <div class="signature clf">
            <div class="signature-name fl">
                <div class="signature-t"><div class="signature-t-img"></div></div>
                <div class="text">销售商托运人签字</div>
            </div>
            <div class="signature-date fl">
                <div class="signature-t"><?php echo $result['invoice_date'];?></div>
                <div class="text">日期</div>
            </div>
        </div>

        <div style="height: 200px;">
            <div style="width: 280px;height: 200px;position: relative" class="fl">
                <div class="total clf" style="text-align: left;word-break:break-all;margin: 0;position: absolute;bottom:0px;">
                    <br/><br/><br/>&nbsp;&nbsp;&nbsp;&nbsp;如果您有任何問題,請發送郵件至我們的客服郵箱:service@bddco.com;我們將竭誠為您服務!感謝選擇BDD Co.</div>
            </div>
            <div style="width: 350px;height: 200px;" class="fr">
                <div class="total clf">
                    <div class="fr clf">
                        <div class="fr total-val"><?php echo $result['currency'] .' '.$result['order_amount']; ?> </div>
                        <div class="fr total-bg">小计</div>
                    </div>
                </div>

                <div class="total clf">
                    <div class="fr clf">
                        <div class="fr total-val"> - <?php echo $result['currency'] .' '.'0'; ?></div>
                        <div class="fr total-bg">优惠劵抵扣</div>
                    </div>
                </div>

                <div class="total clf">
                    <div class="fr clf">
                        <div class="fr total-val"> - <?php echo $result['currency'] .' '.$result['gift_card_amount']; ?></div>
                        <div class="fr total-bg">购物卡抵扣</div>
                    </div>
                </div>

                <div class="total clf">
                    <div class="fr clf">
                        <div class="fr total-val"><?php echo $result['currency'] .' '.$result['order_paid_amount']; ?></div>
                        <div class="fr total-bg">订单总计</div>
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
