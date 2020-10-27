/**
 * author:zhangxiaowu
 * date:2016年9月11日
 * version:1.0
 * email:uf_zhangxiaowu@163.com
 */

//var alreadySetSkuVals = {};//已经设置的SKU值数据
var langLabel = {
	'zh-CN':{
		'batchFill':'批量填充',
		'enable':'启用',
		'disable':'禁用',
	},
	'zh-TW':{
		'batchFill':'批量填充',
		'enable':'启用',
		'disable':'禁用',
	},
	'en-US':{
		'batch_fill':'批量填充',
		'enable':'启用',
		'disable':'禁用',
	}
};
lang = typeof lang != 'undefined'? lang: "zh-CN";

$(function(){

    createTable();

	//sku属性发生改变时,进行表格创建
	$(document).on("change",'.sku_value',function(){
        createTable();
	});
    function createTable(){
        getAlreadySetSkuVals();//获取已经设置的SKU值
        // console.log(alreadySetSkuVals);
        var defaultSkuInputs = $("#skuTableBox input[name*='skuInput[]']");
        var b = true;
        var skuTypeArr =  [];//存放SKU类型的数组
        var totalRow = 1;//总行数
        //获取元素类型
        $(".sku_type").each(function(){
            var that = $(this);
            //SKU类型节点
            var skuTypeNode = $(this).children("li");
            var skuTypeObj = {};//sku类型对象
            //SKU属性类型标题
            skuTypeObj.skuTypeTitle = $(skuTypeNode).attr("sku-type-name");
            //SKU属性类型主键
            var propid = $(skuTypeNode).attr("propid");
            skuTypeObj.skuTypeKey = propid;
            //是否是必选SKU 0：不是；1：是；
            var is_required = $(skuTypeNode).attr("is_required");
            skuValueArr = [];//存放SKU值得数组
            //SKU相对应的节点
            var skuValNode = $(this).next();
            //获取SKU值
            var skuValCheckBoxs = $(skuValNode).find("input[type='checkbox'][class*='sku_value']");
            var checkedNodeLen = 0 ;//选中的SKU节点的个数
            $(skuValCheckBoxs).each(function(){
                if($(this).is(":checked")){
                    var skuValObj = {};//SKU值对象
                    skuValObj.skuValueTitle = $(this).attr("title");//SKU值名称
                    skuValObj.skuValueId = $(this).attr("propvalid");//SKU值主键
                    skuValObj.skuPropId = that.children().attr("propid");//SKU类型主键
                    skuValueArr.push(skuValObj);
                    checkedNodeLen ++ ;
                }
            });
            if(is_required && "1" == is_required){//必选sku
                if(checkedNodeLen <= 0){//有必选的SKU仍然没有选中
                    b = false;
                    return false;//直接返回
                }
            }
            if(skuValueArr && skuValueArr.length > 0){
                totalRow = totalRow * skuValueArr.length;
                skuTypeObj.skuValues = skuValueArr;//sku值数组
                skuTypeObj.skuValueLen = skuValueArr.length;//sku值长度
                skuTypeArr.push(skuTypeObj);//保存进数组中
            }
        });
        var SKUTableDom = "";//sku表格数据
        //开始创建行
        if(b){//必选的SKU属性已经都选中了

//			//调整顺序(少的在前面,多的在后面)
//			skuTypeArr.sort(function(skuType1,skuType2){
//				return (skuType1.skuValueLen - skuType2.skuValueLen)
//			});
//
            SKUTableDom += "<table class='skuTable'><tr>";
            //创建表头

            for(var t = 0 ; t < skuTypeArr.length ; t ++){
                SKUTableDom += '<th>'+skuTypeArr[t].skuTypeTitle+'</th>';
            }
            
            defaultSkuInputs.each(function(){
            	var skuTitle = $(this).attr("attr-title");
            	var skuName  = $(this).attr("attr-name");
                if($(this).attr('attr-batch')==1){
                	skuTitle += "<a class='btn btn-primary btn-xs batch-"+skuName+"'>"+langLabel[lang].batchFill+"</a>"
                }
            	if($(this).attr("attr-require") == 1){
            		SKUTableDom += '<th class="required"><em>*</em>'+skuTitle+'</th>';
            	}else{
            		SKUTableDom += '<th>'+skuTitle+'</th>';
            	}
            });            

            SKUTableDom += "</tr>";
            //循环处理表体
            for(var i = 0 ; i < totalRow ; i ++){//总共需要创建多少行
                var currRowDoms = "";
                var rowCount = 1;//记录行数
                var propvalidArr = [];//记录SKU值主键
                var propIdArr = [];//属性类型主键
                var propvalnameArr = [];//记录SKU值标题
                var propNameArr = [];//属性类型标题
                for(var j = 0 ; j < skuTypeArr.length ; j ++){//sku列
                    var skuValues = skuTypeArr[j].skuValues;//SKU值数组
                    var skuValueLen = skuValues.length;//sku值长度
                    rowCount = (rowCount * skuValueLen);//目前的生成的总行数
                    var anInterBankNum = (totalRow / rowCount);//跨行数
                    var point = ((i / anInterBankNum) % skuValueLen);
                    propNameArr.push(skuTypeArr[j].skuTypeTitle);
                    if(0  == (i % anInterBankNum)){//需要创建td
                        currRowDoms += '<td rowspan='+anInterBankNum+'>';
                        currRowDoms += skuValues[point].skuValueTitle;
                        currRowDoms += '</td>';
                        propvalidArr.push(skuValues[point].skuValueId);
                        propIdArr.push(skuValues[point].skuPropId);
                        propvalnameArr.push(skuValues[point].skuValueTitle);
                    }else{
                        //当前单元格为跨行
                        propvalidArr.push(skuValues[parseInt(point)].skuValueId);
                        propIdArr.push(skuValues[parseInt(point)].skuPropId);
                        propvalnameArr.push(skuValues[parseInt(point)].skuValueTitle);
                    }
                }
                
                /*var specids = [];
                for(i = 0;i<propIdArr.length;i++){
                	//alert(propIdArr[i]);
                }*/
                //console.log(specids.toString());
                
                var propids = propIdArr.toString();
                var propvalids = propvalidArr.toString();                
                var _propvalids = sortSkuIds(propvalids);
                //var _propids = sortSkuIds(propids);
                if(currRowDoms != ''){
                	SKUTableDom += '<tr propvalids=\''+propvalids+'\' propids=\''+propids+'\' propvalnames=\''+propvalnameArr.join(";")+'\'  propnames=\''+propNameArr.join(";")+'\' class="sku_table_tr">'+currRowDoms;
                    defaultSkuInputs.each(function(t){
                    	var skuVal= "";
                    	var skuName = $(this).attr('attr-name');
                    	var skuValDefined = false;
                    	if(alreadySetSkuVals[_propvalids] && typeof alreadySetSkuVals[_propvalids][skuName] != 'undefined'){
                    		skuVal = alreadySetSkuVals[_propvalids][skuName];
                    		skuValDefined = true;
                    	}
                    	if(skuName == "status"){
                    		skuVal = skuValDefined == false || skuVal ==1?1:0;
                    		SKUTableDom += "<td>";
                    		SKUTableDom += '<input type="hidden" name="'+inputName+'[b]['+_propvalids+'][ids]" value="'+propids+'"/>';
                    		SKUTableDom += '<input type="hidden" name="'+inputName+'[b]['+_propvalids+'][vids]" value="'+propvalids+'"/>';
                    		SKUTableDom += '<input type="hidden" class="setsku-' +skuName+'" name="'+inputName+'[c]['+_propvalids+'][' +skuName+']" value="'+skuVal+'"/>';
                    		SKUTableDom += '<span class="btn btn-default btn-sm sku-status">'+langLabel[lang].disable+'</span></td>';
                    	}else{
                        	SKUTableDom += '<td><input type="text" class="form-control setsku-' +skuName+'" name="'+inputName+'[c]['+_propvalids+'][' +skuName+']" value="'+skuVal+'"/></td>';
                    	}
                    });
                    SKUTableDom += '</tr>';
                	
                	
                }
                
            }
            SKUTableDom += "</table>";
        }
        $("#skuTable").html(SKUTableDom);
        
        //初始化skuInput表单编辑状态
        $("#skuTable tr[class*='sku_table_tr']").each(function(){
        	if($(this).find("input[class*='setsku-status']").val()==1){
        		$(this).find(".sku-status").removeClass("btn-success").addClass("btn-default").html(langLabel[lang].disable);
        		$(this).find("input[type*='text']").attr("readonly",false);
        	}else{
        		$(this).find(".sku-status").removeClass("btn-default").addClass("btn-success").html(langLabel[lang].enable);
        		$(this).find("input[type*='text']").attr("readonly",true);
        	}
        });
        $("#skuTable tr[class*='sku_table_tr']").find(".sku-status").click(function(){
            var rowBox = $(this).parent().parent();
	        if($(this).parent().find("input[class*='setsku-status']").val()==0){
	    		$(this).removeClass("btn-success").addClass("btn-default").html(langLabel[lang].disable);
	    		rowBox.find("input[class*='setsku-status']").val(1);
	    		rowBox.find("input[type*='text']").attr("readonly",false);
	    	}else{
	    		$(this).removeClass("btn-default").addClass("btn-success").html(langLabel[lang].enable);
	    		rowBox.find("input[class*='setsku-status']").val(0);
	    		rowBox.find("input[type*='text']").attr("readonly",true);
	    	}
        });
	}


});
/**
 * 获取已经设置的SKU值 
 */
function getAlreadySetSkuVals(){
	//获取设置的SKU属性值
	$("tr[class*='sku_table_tr']").each(function(){	
		var rowBox = $(this);
		var skuName = $("#skuTableBox input[name*='skuInput[]']").eq()
		var _propvalids = sortSkuIds(rowBox.attr("propvalids"));//SKU值主键集合		
		var _skuVals = {};
		$("#skuTableBox input[name*='skuInput[]']").each(function(){
			var skuName = $(this).attr("attr-name");
			_skuVals[skuName] = rowBox.find(".setsku-"+skuName).val();
		});		
		alreadySetSkuVals[_propvalids] = _skuVals;
	});
}
/**
 * 数据校验
 * @returns
 */
function checkSkuInputData(){

	var skuInputs = $("#skuTableBox input[name*='skuInput[]']");	
	$("#skuTableBox tr[class*='sku_table_tr']").each(function(index){
			var rowBox = $(this);
			if(rowBox.find(".setsku-status").val() == 1){
				$("#skuTableBox input[name*='skuInput[]']").each(function(){
					var skuName   = $(this).attr("attr-name");
					var skuTitle  = $(this).attr("attr-title");
					var skuRequire = $(this).attr("attr-require");
					if(skuRequire && val==''){
						alert(skuTitle+"不能为空");
						return false;
					}

				});				
				
			}

	});
}
/**
 * 排序
 * @param str
 * @returns
 */
function sortSkuIds(str){
   var array = str.split(",");   
   array.sort(function(v1,v2){return v1-v2;});
   return array.toString();
}
/**
 * sku验证
 * @returns
 */
function checkSkuInputData(){
	var skuInputs = $("#skuTableBox input[name*='skuInput[]']");
	var uniqueArr = {};
	var returnFlag = true;
	skuInputs.each(function(){
		if($(this).attr("attr-unique") == 1){						
			uniqueArr[$(this).attr("attr-name")] = [];
		}		
	});

	//对戒的那点事
	var $input61 = $('input[name="Style[style_spec][a][61][]"]');
	var $input62 = $('input[name="Style[style_spec][a][62][]"]');

	function cacheRing($input) {
		let tip = true;

		if ($input.length < 1) {
			return true;
		}

		$input.each(function(i, v) {
			if($(this).prop("checked")) {
				tip = false;
			}
		});
		if(tip) {
			let $title = '';

			if($input.attr("name").indexOf("61")!==-1) {
				$title = $(".sku_type").find('[propid="61"]').attr("sku-type-name");
			}

			if($input.attr("name").indexOf("62")!==-1) {
				$title = $(".sku_type").find('[propid="62"]').attr("sku-type-name");
			}

			appConfirm($title+"必须勾选", $title+"必须勾选");

			return false;
		}
		return true;
	}

	if(!cacheRing($input61) || !cacheRing($input62)) {
		return false;
	}
	
	$("#skuTableBox tr[class*='sku_table_tr']").each(function(index){
	    var uniqueVals = {};
	    var rowTip = "价格列表第"+(index+1)+"行"; 
		var rowBox = $(this);
		if(rowBox.find(".setsku-status").val() == 1){				
			skuInputs.each(function(i){				
				var skuName   = $(this).attr("attr-name");
				var skuTitle  = $(this).attr("attr-title");
				var skuValue  = $.trim(rowBox.find(".setsku-"+skuName).val());
				var skuRequire = $(this).attr("attr-require");
				var skuUnique = $(this).attr("attr-unique");
				var skuDType = $(this).attr("attr-dtype");
				if(skuRequire == 1){
					if($.inArray(skuDType,['integer','double']) >-1 && skuValue==0){
	    				 appConfirm(skuTitle+"不能为0",rowTip);
						 returnFlag = false;
						 return returnFlag;
					 }else if(skuValue==""){
						 appConfirm(skuTitle+"不能为空",rowTip);
						 returnFlag = false;
						 return returnFlag;
					 }

				}
				if(skuUnique == 1){	
					if($.inArray(skuValue,uniqueArr[skuName])>-1){
						appConfirm(skuTitle+"\""+skuValue+"\"重复",rowTip);
						returnFlag = false;
						return false;
					}
					uniqueArr[skuName][index] = skuValue;
	        	}
				if(skuValue != '' && $.inArray(skuDType,['integer','double']) >-1){
					 if(!$.isNumeric(skuValue)){
						 appConfirm(skuTitle+"["+skuValue+"]必须为数字",rowTip);
						 returnFlag = false;
						 return returnFlag;
					 }else if(skuDType == 'double' && skuValue<0){
						 appConfirm(skuTitle+"必须大于0",rowTip);
						 returnFlag = false;
						 return returnFlag;
					 }
					 
				}				

			});			
			
		}

	});
	return returnFlag;
}