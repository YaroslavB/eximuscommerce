<?php

/**
 * Store product types
 * This is the model class for table "StoreProductType".
 *
 * The followings are the available columns in table 'StoreProductType':
 * @property integer $id
 * @property string $name
 */
class StoreProductType extends BaseModel
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return StoreProductType the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'StoreProductType';
	}

	public function defaultScope()
	{
		return array(
			'order'=>'name ASC',
		);
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('name', 'required'),
			array('name', 'length', 'max'=>255),

			array('id, name', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'attributeRelation'=>array(self::HAS_MANY, 'StoreTypeAttribute', 'type_id'),
			'attributes'=>array(self::HAS_MANY, 'StoreAttribute', array('attribute_id'=>'id'), 'through'=>'attributeRelation'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => 'Name',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Clear and set type attributes
	 * @param $attributes array of attributes id. array(1,3,5)
	 * @return mixed
	 */
	public function useAttributes($attributes)
	{
		// Clear all relations
		StoreTypeAttribute::model()->deleteAllByAttributes(array('type_id'=>$this->id));

		if (empty($attributes))
			return false;

		foreach($attributes as $attribute_id)
		{
			$record = new StoreTypeAttribute;
			$record->type_id = $this->id;
			$record->attribute_id = $attribute_id;
			$record->save(false);
		}
	}
}