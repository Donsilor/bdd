<?php

use common\helpers\Html;
use common\helpers\Url;
use common\helpers\DebrisHelper;
use common\helpers\StringHelper;

?>

<!--ajax模拟框加载-->
<div class="modal fade" id="ajaxModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <?= Html::img('@web/resources/img/loading.gif', ['class' => 'loading']) ?>
                <span>加载中... </span>
            </div>
        </div>
    </div>
</div>
<!--ajax大模拟框加载-->
<div class="modal fade" id="ajaxModalLg" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <?= Html::img('@web/resources/img/loading.gif', ['class' => 'loading']) ?>
                <span>加载中... </span>
            </div>
        </div>
    </div>
</div>
<!--ajax最大模拟框加载-->
<div class="modal fade" id="ajaxModalMax" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="width: 80%">
        <div class="modal-content">
            <div class="modal-body">
                <?= Html::img('@web/resources/img/loading.gif', ['class' => 'loading']) ?>
                <span>加载中... </span>
            </div>
        </div>
    </div>
</div>
<!--初始化模拟框-->
<div id="rfModalBody" class="hide">
    <div class="modal-body">
        <?= Html::img('@web/resources/img/loading.gif', ['class' => 'loading']) ?>
        <span>加载中... </span>
    </div>
</div>

<?php

list($fullUrl, $pageConnector) = DebrisHelper::getPageSkipUrl();

$page = (int)Yii::$app->request->get('page', 1);
$perPage = (int)Yii::$app->request->get('per-page', 10);

$perPageSelect = Html::dropDownList('rf-per-page', $perPage, [
    10 => '10条/页',
    20 => '20条/页',
    30 => '30条/页',
    40 => '40条/页',
    50 => '50条/页',
], [
    'class' => 'form-control rf-per-page',
    'style' => 'width:100px'
]);

$perPageSelect = StringHelper::replace("\n", '', $perPageSelect);

$script = <<<JS

    $(".pagination").append('<li style="float: left;margin-left: 10px;">$perPageSelect</li>');
    $(".pagination").append('<li>&nbsp;&nbsp;前往&nbsp;<input id="invalue" type="text" class="pane rf-page-skip-input"/>&nbsp;页</li>');

    // 跳转页码
    $('.rf-page-skip-input').blur(function() {
        var page = $('#invalue').val();
        if (!page) {
            return;
        }
        
        if (parseInt(page) > 0) {
              location.href = "{$fullUrl}" + "{$pageConnector}page="+ parseInt(page) + '&per-page=' + $('.rf-per-page').val();
        } else {
            $('#invalue').val('');
            rfAffirm('请输入正确的页码');
        }
    });
    
    // 选择分页数量
    $('.rf-per-page').change(function() {
        var page = $('#invalue').val();
        if (!page) {
            page = '{$page}';
        }
  
        location.href = "{$fullUrl}" + "{$pageConnector}page="+ parseInt(page) + '&per-page=' + $(this).val();
    });
JS;

$this->registerJs($script);
?>

<script>
    // 小模拟框清除
    $('#ajaxModal').on('hide.bs.modal', function (e) {
        if (e.target == this) {
            $(this).removeData("bs.modal");
            $('#ajaxModal').find('.modal-content').html($('#rfModalBody').html());
        }
    });
    // 大模拟框清除
    $('#ajaxModalLg').on('hide.bs.modal', function (e) {
        if (e.target == this) {
            $(this).removeData("bs.modal");
            $('#ajaxModalLg').find('.modal-content').html($('#rfModalBody').html());
        }
    });
    // 最大模拟框清除
    $('#ajaxModalMax').on('hide.bs.modal', function (e) {
        if (e.target == this) {
            $(this).removeData("bs.modal");
            $('#ajaxModalMax').find('.modal-content').html($('#rfModalBody').html());
        }
    });

    // 小模拟框加载完成
    $('#ajaxModal').on('shown.bs.modal', function (e) {
        autoFontColor()
    });
    // 大模拟框加载完成
    $('#ajaxModalLg').on('shown.bs.modal', function (e) {
        autoFontColor()
    });
    // 最模拟框加载完成
    $('#ajaxModalMax').on('shown.bs.modal', function (e) {
        autoFontColor()
    });

    function batchAudit(obj) {
        let $e = $(obj);
        let url = $e.attr('href');
        let text = $e.text();
        let grid = $e.data('grid');
        let id = $e.closest("tr").data("key");

        let ids = [];
        if(id) {
            ids.push(id);
        }
        else if($("#"+grid).length>0) {
            ids = $("#"+grid).yiiGridView("getSelectedRows");
        }

        if(ids.length===0) {
            rfInfo('未选中数据！','');
            return false;
        }

        appConfirm("确定要"+text+"吗?", '', function (code) {
            if(code !== "defeat") {
                return;
            }

            $.ajax({
                type: "post",
                url: url,
                dataType: "json",
                data: {
                    ids: ids
                },
                success: function (data) {
                    if (parseInt(data.code) !== 200) {
                        rfAffirm(data.message);
                    } else {
                        window.location.reload();
                    }
                }
            });
        });
    }

    // 启用状态 status 1:启用;0禁用;
    function rfStatus(obj) {
        let id = $(obj).attr('data-id');
        let url = $(obj).attr('data-url');
        let status = 0;
        self = $(obj);
        if (self.hasClass("btn-success")) {
            status = 1;
        }

        if (!id) {
            id = $(obj).parent().parent().attr('id');
        }

        if (!id) {
            id = $(obj).parent().parent().attr('data-key');
        }
        if(!url){
             url = "<?= Url::to(['ajax-update'])?>";
        } 
        $.ajax({
            type: "get",
            url: url,
            dataType: "json",
            data: {
                id: id,
                status: status
            },
            success: function (data) {
                if (parseInt(data.code) === 200) {
                    if (self.hasClass("btn-success")) {
                        self.removeClass("btn-success").addClass("btn-default");
                        self.text('禁用');
                    } else {
                        self.removeClass("btn-default").addClass("btn-success");
                        self.text('启用');
                    }
                    window.location.reload();
                } else {
                    rfAffirm(data.message);
                }
            }
        });
    }
    //批量启用1/禁用0/软删除-1
    $(".jsBatchStatus").click(function(){

    	let grid = $(this).attr('data-grid');
    	let url = $(this).attr('data-url');
    	let status = $(this).attr('data-value');
    	let text = $(this).text();

        let id = $(this).closest("tr").data("key");

        let ids = [];
        if(id) {
            ids.push(id);
        }
        else if($("#"+grid).length>0) {
            ids = $("#"+grid).yiiGridView("getSelectedRows");
        }

    	if(!url){
   		 	url = "<?= Url::to(['ajax-batch-update'])?>";
        }
        if(ids=="" || !ids){
        	rfInfo('未选中数据！','');
            return false;
        }
        
    	appConfirm("确定要"+text+"吗?", '', function (code) {
    		switch (code) {
                case "defeat":
                	$.ajax({
                        type: "post",
                        url: url,
                        dataType: "json",
                        data: {
                            ids: ids,
                            status:status
                        },
                        success: function (data) {
                            if (parseInt(data.code) !== 200) {
                                rfAffirm(data.message);
                            }else {
                            	//rfAffirm(data.message);
                            	window.location.reload(); 
                            }
                        }
                    });
                    break;
            	default:
        	}
    		
        })

    });    
    // 排序
    function rfSort(obj) {
        let id = $(obj).attr('data-id');
        let url = $(obj).attr('data-url');

        if (!id) {
            id = $(obj).parent().parent().attr('id');
        }

        if (!id) {
            id = $(obj).parent().parent().attr('data-key');
        }
        if(!url){
            url = "<?= Url::to(['ajax-update'])?>";
        } 
        var sort = $(obj).val();
        if (isNaN(sort)) {
            rfAffirm('排序只能为数字');
            return false;
        } else {
            $.ajax({
                type: "get",
                url: url,
                dataType: "json",
                data: {
                    id: id,
                    sort: sort
                },
                success: function (data) {
                    if (parseInt(data.code) !== 200) {
                        rfAffirm(data.message);
                    }
                }
            });
        }
    }

    function rfAjaxUpdate(obj) {
        let id = $(obj).attr('data-id');
        let url = $(obj).attr('data-url');
        let type = $(obj).attr('data-type');

        if (!id) {
            id = $(obj).parent().parent().attr('id');
        }

        if (!id) {
            id = $(obj).parent().parent().attr('data-key');
        }
        if(!url){
            url = "<?= Url::to(['ajax-update'])?>";
        }

        var val = $(obj).val();
        var name = $(obj).attr('name');
        var data = {'id':id};
        data[name] = val;

        if(type == 'number' && isNaN(val)){
            rfAffirm('只能为数字');
            return false;
        }
        $.ajax({
            type: "get",
            url: url,
            dataType: "json",
            data: data,
            success: function (data) {
                if (parseInt(data.code) !== 200) {
                    rfAffirm(data.message);
                }
            }
        });
    }

    //显示360 主图
    function view_3ds() {
        var ds3 = $("#ds3").val();
        if(ds3 == ''){
            rfMsg('请填写360°主图');
            return;
        }
        var title = '360°主图';
        var width = '498px';
        var height = '498px';
        var offset = "0";
        var btn = [];
        // var href = ds3;
        var href = "https://spins0.arqspin.com/iframe.html?spin=" + ds3 + "&is=0.16"
        openIframe(title, width, height, href, offset,btn);
        e.preventDefault();
        return false;
    }
</script>