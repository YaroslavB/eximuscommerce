<?php
/**
 * Images tabs
 */

Yii::app()->getClientScript()->registerCss('infoStyles', "
    table.imagesList {
        float: left;
        width: 45%;
        margin-right: 15px;
        margin-bottom: 15px;
    }
");

echo CHtml::openTag('div', array('class'=>'row'));
echo CHtml::label(Yii::t('StoreModule.admin', 'Выберите изображения'), 'files');
    $this->widget('system.web.widgets.CMultiFileUpload', array(
        'name'=>'StoreProductImages',
        'model'=>$model,
        'attribute'=>'files',
        'accept'=>implode('|', Yii::app()->params['storeImages']['extensions']),
    ));
echo CHtml::closeTag('div');

if ($model->images)
{
    foreach ($model->images as $image)
    {
        $this->widget('zii.widgets.CDetailView', array(
            'data'=>$image,
            'id'=>'ProductImage'.$image->id,
            'htmlOptions'=>array(
                'class'=>'detail-view imagesList',
            ),
            'attributes'=>array(
                array(
                    'label'=>Yii::t('StoreModule.admin', 'Изображение'),
                    'type'=>'raw',
                    'value'=>CHtml::image($image->getUrl(true), $image->name, array(
                        'height'=>'150px',
                    )),
                ),
                'id',
                array(
                    'name'=>'is_main',
                    'type'=>'raw',
                    'value'=>CHtml::radioButton('mainImageId', $image->is_main, array(
                        'value'=>$image->id,
                    )),
                ),
                array(
                    'name'=>'author',
                    'type'=>'raw',
                    'value'=>$image->author->username,
                ),
                'date_uploaded',
                array(
                    'label'=>Yii::t('StoreModule.admin', 'Действия'),
                    'type'=>'raw',
                    'value'=>CHtml::ajaxLink(Yii::t('StoreModule.admin', 'Удалить'),$this->createUrl('deleteImage', array('id'=>$image->id)),
                        array(
                            'type'=>'POST',
                            'data'=>array(Yii::app()->request->csrfTokenName => Yii::app()->request->csrfToken),
                            'success'=>"js:$('#ProductImage$image->id').hide().remove()",
                        ),
                        array(
                            'id'=>'DeleteImageLink'.$image->id,
                            'confirm'=>'Delete Image?',
                        )),
                ),
            ),
        ));
    }
}