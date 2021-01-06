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
            <div id="main" style="width: 600px;height:1600px;"></div>
        </div>
    </div>
</div>
<script type="text/javascript">
    // 基于准备好的dom，初始化echarts实例
    var myChart = echarts.init(document.getElementById("main"));

    // 指定图表的配置项和数据
    option = {
        title: {
            text: '折线图堆叠'
        },
        legend: {},
        tooltip: {
            trigger: 'axis',
            showContent: false
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true,
        top: '55%'
        },
        toolbox: {
            feature: {
                saveAsImage: {}
            }
        },
        dataset: {
            source: [
                ['product', '周一', '周二', '周三', '周四', '周五', '周六', '周日'],
                ['邮件营销', 120, 132, 101, 134, 90, 230, 210],
                ['联盟广告', 220, 182, 191, 234, 290, 330, 310],
                ['视频广告', 150, 232, 201, 154, 190, 330, 410],
                ['直接访问', 320, 332, 301, 334, 390, 330, 1320],
                ['搜索引擎', 820, 932, 901, 934, 1290, 1330, 1320],
            ]
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
        },
        yAxis: {
            type: 'value',
            gridIndex: 0
        },
        series: [
            {type: 'line', smooth: true, seriesLayoutBy: 'row', emphasis: {focus: 'series'}},
            {type: 'line', smooth: true, seriesLayoutBy: 'row', emphasis: {focus: 'series'}},
            {type: 'line', smooth: true, seriesLayoutBy: 'row', emphasis: {focus: 'series'}},
            {type: 'line', smooth: true, seriesLayoutBy: 'row', emphasis: {focus: 'series'}},
            {type: 'line', smooth: true, seriesLayoutBy: 'row', emphasis: {focus: 'series'}},
            {
                type: 'pie',
                id: 'pie',
                radius: '30%',
                center: ['50%', '25%'],
                emphasis: {focus: 'data'},
                label: {
                    formatter: '{b}: {@2012} ({d}%)'
                },
                encode: {
                    itemName: 'product',
                    value: '周一',
                    tooltip: '周一'
                }
            }
        ]
    };

    myChart.on('updateAxisPointer', function (event) {
        var xAxisInfo = event.axesInfo[0];
        if (xAxisInfo) {
            var dimension = xAxisInfo.value + 1;
            myChart.setOption({
                series: {
                    id: 'pie',
                    label: {
                        formatter: '{b}: {@[' + dimension + ']} ({d}%)'
                    },
                    encode: {
                        value: dimension,
                        tooltip: dimension
                    }
                }
            });
        }
    });

    // 使用刚指定的配置项和数据显示图表。
    myChart.setOption(option);
</script>