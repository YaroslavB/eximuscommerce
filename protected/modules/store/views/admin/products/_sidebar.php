<h3><?php echo Yii::t('StoreModule.admin', 'Поиск по категории') ?></h3>
<div class="form wide">
	<input type="text" style="width: 90%" onkeyup='$("#StoreCategoryTreeFilter").jstree("search", $(this).val());' />
</div>

<?php

// Create jstree to filter products
$this->widget('ext.jstree.SJsTree', array(
	'id'=>'StoreCategoryTreeFilter',
	'data'=>StoreCategoryNode::fromArray(StoreCategory::model()->findAllByPk(1)),
	'options'=>array(
		'core'=>array('initially_open'=>'StoreCategoryTreeFilterNode_1'),
		'plugins'=>array('themes','html_data','ui','crrm', 'search','cookies'),
		'cookies'=>array(
			'save_selected'=>false,
		),
		'ui'=>array(
			'initially_select'=>array('StoreCategoryTreeFilterNode_1')
		),
	),
));

Yii::app()->getClientScript()->registerScript('insertAllCategory', '
$("#StoreCategoryTreeFilter").bind("loaded.jstree", function (event, data) {
	$(this).jstree("create",-1,false,{attr:{id:"StoreCategoryTreeFilterNode_0"}, data:{title:"'.Yii::t('StoreModule.admin', 'Все категории').'"}},false,true);
	$(this).jstree("select_node","#StoreCategoryTreeFilterNode_0");
});

');

Yii::app()->getClientScript()->registerCss("StoreCategoryTreeStyles","#StoreCategoryTree { width:90% }");

?>