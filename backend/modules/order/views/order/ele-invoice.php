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
                <div class="invoice-data-topcolor">發票</div>
                <div class="invoice-data-b clf">
                    <div class="invoice-data-l fl">
                        <div class="invoice-data-type">發貨日期</div>
                        <div class="invoice-data-val"><?php echo $result['invoice_date'];?></div>
                    </div>
                    <div class="invoice-data-r fl">
                        <div class="invoice-data-type">頁碼</div>
                        <div class="invoice-data-val">1 of 1</div>
                    </div>
                </div>
                <div class="invoice-data-b clf">
                    <div class="invoice-data-l fl">
                        <div class="invoice-data-type">发票号码:</div>
                        <div class="invoice-data-val"><?php echo $result['order_sn'];?></div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div>网址:<?php echo $result['siteInfo']['webSite']??'';?></div>
            <div>邮箱:<?php echo $result['siteInfo']['email']??'';?></div>
            <div>电话:<?php echo $result['siteInfo']['tel']??'';?></div>
        </div>

        <div class="site-type clf">
            <div class="list fl clf">
                <div class="list-tit fl">銷售商:</div>
                <div class="list-details fl">
                    <div class="child-name"><?php echo $result['realname'];?></div>
                    <div class="child-addr"><?php echo $result['address_details'];?></div>
                </div>
            </div>

            <div class="list fl clf">
                <div class="list-tit fl">客戶地址:</div>
                <div class="list-details fl">
                    <div class="child-name"><?php echo $result['realname'];?></div>
                    <div class="child-addr"><?php echo $result['address_details'];?></div>
                </div>
            </div>
        </div>

        <div class="package-information">
            <div class="package-tit">訂單信息</div>
            <div class="package-info clf">
                <div class="package-child fl">
                    <div class="package-child-v">國際空運貨單</div>
                    <div class="package-child-val"><?php echo $result['express_no'];?></div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">運輸公司</div>
                    <div class="package-child-val"><?php echo $result['express_company_name'];?></div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">支付方式</div>
                    <div class="package-child-val"><?php echo $result['delivery_time'];?></div>
                </div>
            </div>
            <div class="package-info clf">
                <div class="package-child fl">
                    <div class="package-child-v">出口國家</div>
                    <div class="package-child-val">中國</div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">交易幣種</div>
                    <div class="package-child-val"><?php echo $result['currency'];?></div>
                </div>
                <div class="package-child fl">
                    <div class="package-child-v">目的地國家</div>
                    <div class="package-child-val"><?php echo $result['country'];?></div>
                </div>
            </div>
        </div>

        <table cellspacing="0" cellpadding="0" width="100%" border="1" rules="cols">
            <tr>
                <th width="32%">商品描述</th>
                <th width="16%">國家</th>
                <th width="20%">款號</th>
                <th width="10%">數量</th>
                <th width="15%">單價</th>
                <th width="15%">總金額</th>
            </tr>
            <?php foreach ($result['order_goods'] as $val){ ?>
            <tr>
                <td><?php echo $val['goods_name'];?></td>
                <td>中國</td>
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
                <div class="text">出口商簽字</div>
            </div>
            <div class="signature-date fl">
                <div class="signature-t"><?php echo $result['invoice_date'];?></div>
                <div class="text">日期</div>
            </div>
        </div>

        <div>
            <div style="width: 280px;" class="fl">
                <div class="total clf" style="text-align: left;word-break:break-all;">如果您有任何問題, 請發送郵件至我們的客服郵箱: service@bddco.com ; 我們將竭誠為您服務! 感謝選擇BDD Co.
                </div>
            </div>
            <div>
                <div style="width: 350px;" class="fr">
                    <div class="fr clf">
                        <div class="fr total-val"><?php echo $result['currency'] .' '.$result['order_amount']; ?> </div>
                        <div class="fr total-bg">小計</div>
                    </div>
                </div>

                <div class="total clf">
                    <div class="fr clf">
                        <div class="fr total-val"> - <?php echo $result['currency'] .' '.'0'; ?></div>
                        <div class="fr total-bg">優惠劵抵扣</div>
                    </div>
                </div>

                <div class="total clf">
                    <div class="fr clf">
                        <div class="fr total-val"> - <?php echo $result['currency'] .' '.$result['gift_card_amount']; ?></div>
                        <div class="fr total-bg">購物卡抵扣</div>
                    </div>
                </div>

                <div class="total clf">
                    <div class="fr clf">
                        <div class="fr total-val"><?php echo $result['currency'] .' '.$result['order_paid_amount']; ?></div>
                        <div class="fr total-bg">訂單總計</div>
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
