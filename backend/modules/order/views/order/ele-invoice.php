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
                        <div class="invoice-data-type">開票日期</div>
                        <div class="invoice-data-val"><?php echo $result['invoice_date'];?></div>
                    </div>
                    <div class="invoice-data-r fl">
                        <div class="invoice-data-type">頁碼</div>
                        <div class="invoice-data-val">1 of 1</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="site-type clf">
            <div class="list fl clf">
                <div class="list-tit fl">托運人:</div>
                <div class="list-details fl">
                    <div class="child-name"><?php echo $result['sender_name']?$result['sender_name']:'BDD Co.Ltd';?></div>
                    <div class="child-addr"><?php echo $result['sender_address']?$result['sender_address']: '中環亞畢諾道3號環球貿易中心23樓04室';?></div>
                </div>
            </div>
            <div class="list fl clf">
                <div class="list-tit fl">發票號碼:</div>
                <div class="list-details fl">
                    <div class="child-name"><?php echo $result['order_sn'];?></div>
                </div>
            </div>
        </div>
        <div class="site-type clf">
            <div class="list fl clf">
                <div class="list-tit fl">進口商:</div>
                <div class="list-details fl">
                    <div class="child-name"><?php echo $result['realname'];?></div>
                    <div class="child-addr"><?php echo $result['address_details'];?></div>
                </div>
            </div>

            <div class="list fl clf">
                <div class="list-tit fl">收貨人:</div>
                <div class="list-details fl">
                    <div class="child-name"><?php echo $result['realname'];?></div>
                    <div class="child-addr"><?php echo $result['address_details'];?></div>
                </div>
            </div>
        </div>

        <div class="package-information">
            <div class="package-tit">貨單資訊</div>
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
                    <div class="package-child-v">出口日期</div>
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
                <th width="10%">數量</th>
                <th width="15%">單價</th>
                <th width="27%">總金額</th>
            </tr>
            <?php foreach ($result['order_goods'] as $val){ ?>
            <tr>
                <td><?php echo $val['goods_name'];?></td>
                <td>中國</td>
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

        <div class="total clf">
            <div class="fr clf">
                <div class="fr total-val"><?php echo $result['currency'] .' '.$result['order_amount']; ?> </div>
                <div class="fr total-bg">總金額</div>
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
                <div class="fr total-bg">實付金額</div>
            </div>
        </div>
    </div>
    <!--打印内容结束-->

    <!--endprint1-->
</div>



</body>
</html>
