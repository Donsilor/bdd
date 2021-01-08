<?php

use common\helpers\Url;

$this->title = '首页';
$this->params['breadcrumbs'][] = ['label' => $this->title];
?>
<script src="<?= Yii::$app->request->baseUrl ?>/resources/js/echarts.min.js"></script>
<div class="row">
    <div class="col-sm-12">
        <!-- 具体内容 -->
        <div class="box">
            <div id="main" style="width: 1000px;height:750px;"></div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var list = <?= json_encode($list) ?>;
console.log(list);
    // 基于准备好的dom，初始化echarts实例
    var myChart = echarts.init(document.getElementById("main"));

    // 指定图表的配置项和数据
    option = {
        legend: {},
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
                type: 'line',
                smooth: 0.2,
                seriesLayoutBy: 'column',
                encode: {
                    x: 'datetime',      // 表示维度 3、1、5 映射到 x 轴。
                    y: 'sale_amount_all',              // 表示维度 2 映射到 y 轴。
                    tooltip: ['name_all', 'sale_amount_all'] // 表示维度 3、2、4 会在 tooltip 中显示。
                }
            },
            {
                type: 'line',
                smooth: 0.2,
                seriesLayoutBy: 'column',
                encode: {
                    x: 'datetime',      // 表示维度 3、1、5 映射到 x 轴。
                    y: 'sale_amount_hk',              // 表示维度 2 映射到 y 轴。
                    tooltip: ['name_hk', 'sale_amount_hk'] // 表示维度 3、2、4 会在 tooltip 中显示。
                }
            },
            {
                type: 'line',
                smooth: 0.2,
                seriesLayoutBy: 'column',
                encode: {
                    x: 'datetime',      // 表示维度 3、1、5 映射到 x 轴。
                    y: 'sale_amount_cn',              // 表示维度 2 映射到 y 轴。
                    tooltip: ['name_cn', 'sale_amount_cn'] // 表示维度 3、2、4 会在 tooltip 中显示。
                }
            },
            {
                type: 'line',
                smooth: 0.2,
                seriesLayoutBy: 'column',
                encode: {
                    x: 'datetime',      // 表示维度 3、1、5 映射到 x 轴。
                    y: 'sale_amount_tw',              // 表示维度 2 映射到 y 轴。
                    tooltip: ['name_tw', 'sale_amount_tw'] // 表示维度 3、2、4 会在 tooltip 中显示。
                }
            },
            {
                type: 'line',
                smooth: 0.2,
                seriesLayoutBy: 'column',
                encode: {
                    x: 'datetime',      // 表示维度 3、1、5 映射到 x 轴。
                    y: 'sale_amount_us',              // 表示维度 2 映射到 y 轴。
                    tooltip: ['name_us', 'sale_amount_us'] // 表示维度 3、2、4 会在 tooltip 中显示。
                }
            },
            {
                type: 'pie',
                id: 'pie',
                radius: '40%',
                center: ['50%', '30%'],
                // seriesLayoutBy: 'row',
                // dimensions: ['1', '2', '3'],
                label: {
                    formatter: '{b}: {@0} ({d}%)'
                },
                data: [
                    {
                        name: list[0]['name_hk'],
                        value: list[0]['sale_amount_hk'],
                        // tooltip: ['datetime', 'sale_amount_us']
                    },
                    {
                        name: list[0]['name_cn'],
                        value: list[0]['sale_amount_cn'],
                        // tooltip: ['datetime', 'sale_amount_us']
                    },
                    {
                        name: list[0]['name_tw'],
                        value: list[0]['sale_amount_tw'],
                        // tooltip: ['datetime', 'sale_amount_us']
                    },
                    {
                        name: list[0]['name_us'],
                        value: list[0]['sale_amount_us'],
                        // tooltip: ['datetime', 'sale_amount_us']
                    },
                ]
            },
            // {
            //     type: 'pie',
            //     dimensions: ['date', 'open2'],
            //     id: 'pie2',
            //     radius: '30%',
            //     center: ['75%', '25%'],
            //     emphasis: {focus: 'data'},
            //     label: {
            //         formatter: '{b}: {@周一} ({d}%)'
            //     },
            //     encode: {
            //         itemName: 'product',
            //         value: 'date',
            //         tooltip: 'date'
            //     }
            // }
        ]
    };

    // myChart.on('updateAxisPointer', function (event) {
    //     var xAxisInfo = event.axesInfo[0];
    //     if (xAxisInfo) {
    //         var dimension = xAxisInfo.value + 1;
    //
    //         console.log(dimension);
    //
    //         myChart.setOption({
    //             series: [{
    //                 id: 'pie',
    //                 label: {
    //                     formatter: '{b}: {@[' + dimension + ']} ({d}%)'
    //                 },
    //                 encode: {
    //                     value: dimension,
    //                     tooltip: dimension
    //                 }
    //                 // data: [{value:20,name:"aa"}, {value:30,name:"aa"}],
    //             }]
    //         });
    //     }
    // });

    // 使用刚指定的配置项和数据显示图表。
    myChart.setOption(option);
</script>