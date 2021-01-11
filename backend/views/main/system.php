<?php

use common\helpers\Url;
use \common\helpers\Html;

$this->title = '首页';
$this->params['breadcrumbs'][] = ['label' => $this->title];
?>
<script src="<?= Yii::$app->request->baseUrl ?>/resources/js/echarts.min.js"></script>
<div class="row">
    <div class="col-sm-12">
        <!-- 具体内容 -->
        <div class="box">
            <div class="row">
                <div class="col-lg-3">
                    <div class="box" style="margin: 35px 45px;">
                        <div class="row">
                            <h4 style="padding: 10px 25px; background:#ecf0f5">数据来源</h4>
                        </div>
                        <div class="row">
                            <input name="site" type="radio" value="all" checked /><span style="padding-left: 8px;">全部站点</span>
                        </div>
                        <div class="row">
                            <input name="site" type="radio" value="cn" /><span style="padding-left: 8px;">大陆</span>
                        </div>
                        <div class="row">
                            <input name="site" type="radio" value="hk" /><span style="padding-left: 8px;">香港</span>
                        </div>
                        <div class="row">
                            <input name="site" type="radio" value="tw" /><span style="padding-left: 8px;">台湾</span>
                        </div>
                        <div class="row">
                            <input name="site" type="radio" value="us" /><span style="padding-left: 8px;">美国</span>
                        </div>
                    </div>
                    <div class="box" style="margin: 35px 45px;">
                        <div class="row">
                            <?php if($type==2) { ?>
                            <?= Html::a('查看日销售额 >>',['', 'type'=>1], ['class' => 'btn btn-info btn-sm']) ?>
                            <?php } else { ?>
                            <?= Html::a('查看月销售额 >>',['', 'type'=>2], ['class' => 'btn btn-info btn-sm']) ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div id="main" style="width: 100%;height:750px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var list = <?= json_encode($list) ?>;
    var dimension = list.length-1;
    var siteName = "all";

    // 基于准备好的dom，初始化echarts实例
    var myChart = echarts.init(document.getElementById("main"));

    // 指定图表的配置项和数据
    option = {
        legend: {
            show:false
        },
        tooltip: {
            trigger: 'axis',
            showContent: true
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true,
            top: '55%'
        },
        dataset: {
            source: list,
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
        },
        yAxis: {
            gridIndex: 0
        },
        series: [
            {
                name: '全部站点',
                type: 'line',
                smooth: 0.2,
                encode: {
                    x: 'datetime',      // 表示维度 3、1、5 映射到 x 轴。
                    y: 'sale_amount_all',              // 表示维度 2 映射到 y 轴。
                    tooltip: ['sale_amount_all'] // 表示维度 3、2、4 会在 tooltip 中显示。
                },
            },
            {
                name: '香港',
                type: 'line',
                smooth: 0.2,
                encode: {
                    x: 'datetime',      // 表示维度 3、1、5 映射到 x 轴。
                    y: 'sale_amount_hk',              // 表示维度 2 映射到 y 轴。
                    tooltip: ['sale_amount_hk'] // 表示维度 3、2、4 会在 tooltip 中显示。
                }
            },
            {
                name: '大陆',
                type: 'line',
                smooth: 0.2,
                encode: {
                    x: 'datetime',      // 表示维度 3、1、5 映射到 x 轴。
                    y: 'sale_amount_cn',              // 表示维度 2 映射到 y 轴。
                    tooltip: ['sale_amount_cn'] // 表示维度 3、2、4 会在 tooltip 中显示。
                }
            },
            {
                name: '台湾',
                type: 'line',
                smooth: 0.2,
                encode: {
                    x: 'datetime',      // 表示维度 3、1、5 映射到 x 轴。
                    y: 'sale_amount_tw',              // 表示维度 2 映射到 y 轴。
                    tooltip: ['sale_amount_tw'] // 表示维度 3、2、4 会在 tooltip 中显示。
                }
            },
            {
                name: '美国',
                type: 'line',
                smooth: 0.2,
                encode: {
                    x: 'datetime',      // 表示维度 3、1、5 映射到 x 轴。
                    y: 'sale_amount_us',              // 表示维度 2 映射到 y 轴。
                    tooltip: ['sale_amount_us'] // 表示维度 3、2、4 会在 tooltip 中显示。
                }
            },
            {
                visualMap: false,
                type: 'pie',
                id: 'pie',
                radius: '40%',
                center: ['50%', '30%'],
                label: {
                    formatter: '{b}: {@0} ({d}%)'
                },
                data: [
                    {
                        name: list[dimension]['name_hk'],
                        value: list[dimension]['sale_amount_hk'],
                    },
                    {
                        name: list[dimension]['name_cn'],
                        value: list[dimension]['sale_amount_cn'],
                    },
                    {
                        name: list[dimension]['name_tw'],
                        value: list[dimension]['sale_amount_tw'],
                    },
                    {
                        name: list[dimension]['name_us'],
                        value: list[dimension]['sale_amount_us'],
                    },
                ]
            }
        ]
    };

    function updatePie() {
        var datas = list[dimension]
        var data = [];

        if(siteName=="all") {
            data = [
                {
                    name: datas['name_hk'],
                    value: datas['sale_amount_hk'],
                },
                {
                    name: datas['name_cn'],
                    value: datas['sale_amount_cn'],
                },
                {
                    name: datas['name_tw'],
                    value: datas['sale_amount_tw'],
                },
                {
                    name: datas['name_us'],
                    value: datas['sale_amount_us'],
                },
            ];
        }
        else {
            let key = 'type_sale_amount_' + siteName;
            $.each(datas[key], function (name, value) {
                data.push({
                    name: name,
                    value: value,
                });
            });

            if(data.length===0) {
                data.push({
                    name: datas['name_'+siteName],
                    value: 0,
                });
            }
        }

        myChart.setOption({
            legend: {
                selected : {
                    "全部站点": "all"===siteName,
                    "大陆": "cn"===siteName || "all"===siteName,
                    "香港": "hk"===siteName || "all"===siteName,
                    "台湾": "tw"===siteName || "all"===siteName,
                    "美国": "us"===siteName || "all"===siteName,
                }
            },
            series: [
                {
                    name: datas['name_'+siteName],
                    type: 'line',
                    encode: {
                        x: 'datetime',      // 表示维度 3、1、5 映射到 x 轴。
                        y: 'sale_amount_'+siteName,              // 表示维度 2 映射到 y 轴。
                        tooltip: ['sale_amount_'+siteName] // 表示维度 3、2、4 会在 tooltip 中显示。
                    },
                },
                {
                    id: 'pie',
                    label: {
                        formatter: '{b}: {@[' + dimension + ']} ({d}%)'
                    },
                    data: data
                }
            ]
        });
    }

    myChart.on('updateAxisPointer', function (event) {
        var xAxisInfo = event.axesInfo[0];
        if (xAxisInfo) {
            dimension = xAxisInfo.value;
            updatePie();
        }
    });

    // 使用刚指定的配置项和数据显示图表。
    myChart.setOption(option);
</script>

<script type="text/javascript">
    $("input[name=site]").click(function () {
        siteName = $(this).val();
        updatePie();
    });
</script>