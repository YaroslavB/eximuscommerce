<?php

/**
 * This is the model class for table "StoreProduct".
 *
 * The followings are the available columns in table 'StoreProduct':
 * @property integer $id
 * @property integer $manufacturer_id
 * @property boolean $use_configurations
 * @property array $configurations array of product pks
 * @property array $configurable_attributes array of StoreAttribute pks used to configure product
 * @property integer $type_id
 * @property string $name
 * @property string $url
 * @property float $price
 * @property float $max_price for configurable products.
 * @property boolean $is_active
 * @property string $short_description
 * @property string $full_description
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $layout
 * @property string $view
 * @property string $sku
 * @property string $quantity
 * @property string $auto_decrease_quantity
 * @property string $availability
 * @property string $created
 * @property string $updated
 * @method StoreProduct active() Find Only active products
 * @method StoreProduct withEavAttributes
 */
class StoreProduct extends BaseModel
{

	/**
	 * @var null Id if product to exclude from search
	 */
	public $exclude = null;

	/**
	 * @var array of related products
	 */
	private $_related;

	/**
	 * @var string used in search() method to filter products by manufacturer name.
	 */
	public $manufacturer_search;

	/**
	 * @var array of attributes used to configure product
	 */
	private $_configurable_attributes;
	private $_configurable_attribute_changed = false;

	/**
	 * @var array
	 */
	private $_configurations;

	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className
	 * @return StoreProduct the static model class
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
		return 'StoreProduct';
	}

	public function scopes()
	{
		$alias = $this->getTableAlias(true);
		return array(
			'active'=>array(
				'condition'=>$alias.'.is_active=1',
			),
		);
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('price', 'commaToDot'),
			array('price, type_id', 'numerical'),
			array('is_active', 'boolean'),
			array('use_configurations', 'boolean', 'on'=>'insert'),
			array('quantity, availability, manufacturer_id', 'numerical', 'integerOnly'=>true),
			array('name, price', 'required'),
			array('url', 'LocalUrlValidator'),
			array('name, url, meta_title, meta_keywords, meta_description, layout, view, sku', 'length', 'max'=>255),
			array('short_description, full_description, auto_decrease_quantity', 'type'),
			// Search
			array('id, name, url, price, short_description, full_description, created, updated, manufacturer_search', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * Find product by url.
	 * Scope.
	 * @param string Product url
	 * @return StoreProduct
	 */
	public function withUrl($url)
	{
		$this->getDbCriteria()->mergeWith(array(
			'condition'=>'url=:url',
			'params'=>array(':url'=>$url)
		));
		return $this;
	}

	/**
	 * Filter products by category
	 * Scope
	 * @param StoreCategory|string|array $categories to search products
	 * @return StoreProduct
	 */
	public function applyCategories($categories, $select = 't.*')
	{
		if($categories instanceof StoreCategory)
			$categories = array($categories->id);
		else
		{
			if(!is_array($categories))
				$categories = array($categories);
		}

		$alias = $this->getTableAlias(true);

		$criteria = new CDbCriteria;

		if($select)
			$criteria->select = $select;
		$criteria->join = 'LEFT OUTER JOIN `StoreProductCategoryRef` `categorization` ON (`categorization`.`product`=`t`.`id`)';
		$criteria->addInCondition('categorization.category', $categories);
		$this->getDbCriteria()->mergeWith($criteria);

		return $this;
	}

	/**
	 * Filter products by EAV attributes.
	 * Example: $model->applyAttributes(array('color'=>'green'))->findAll();
	 * Scope
	 * @param array $attributes list of allowed attribute models
	 * @return StoreProduct
	 */
	public function applyAttributes(array $attributes)
	{
		if(empty($attributes))
			return $this;
		return $this->withEavAttributes($attributes);
	}

	/**
	 * Filter product by manufacturers
	 * Scope
	 * @param string|array $manufacturers
	 * @return StoreProduct
	 */
	public function applyManufacturers($manufacturers)
	{
		if(!is_array($manufacturers))
			$manufacturers = array($manufacturers);

		if(empty($manufacturers))
			return $this;

		$criteria = new CDbCriteria;
		$criteria->addInCondition('manufacturer_id', $manufacturers);
		$this->getDbCriteria()->mergeWith($criteria);
		return $this;
	}

	/**
	 * Replaces comma to dot
	 * @param $attr
	 */
	public function commaToDot($attr)
	{
		$this->$attr = str_replace(',','.', $this->$attr);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'images'          => array(self::HAS_MANY, 'StoreProductImage', 'product_id'),
			'mainImage'       => array(self::HAS_ONE, 'StoreProductImage', 'product_id', 'condition'=>'is_main=1'),
			'imagesNoMain'    => array(self::HAS_MANY, 'StoreProductImage', 'product_id', 'condition'=>'is_main=0'),
			'manufacturer'    => array(self::BELONGS_TO, 'StoreManufacturer', 'manufacturer_id'),
			'productsCount'   => array(self::STAT, 'StoreProduct', 'manufacturer_id', 'select'=>'count(t.id)'),
			'type'            => array(self::BELONGS_TO, 'StoreProductType', 'type_id'),
			'related'         => array(self::HAS_MANY, 'StoreRelatedProduct', 'product_id'),
			'relatedProducts' => array(self::HAS_MANY, 'StoreProduct', array('related_id'=>'id'), 'through'=>'related'),
			'categorization'  => array(self::HAS_MANY, 'StoreProductCategoryRef', 'product'),
			'categories'      => array(self::HAS_MANY, 'StoreCategory',array('category'=>'id'), 'through'=>'categorization'),
			'mainCategory'    => array(self::HAS_ONE, 'StoreCategory', array('category'=>'id'), 'through'=>'categorization', 'condition'=>'categorization.is_main = 1'),
			// Product variation
			'variants'        => array(self::HAS_MANY, 'StoreProductVariant', array('product_id'), 'with'=>array('attribute', 'option'), 'order'=>'option.position'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id'                     => 'ID',
			'manufacturer_id'        => Yii::t('StoreModule.core', 'Производитель'),
			'manufacturer_search'    => Yii::t('StoreModule.core', 'Производитель'),
			'type_id'                => Yii::t('StoreModule.core', 'Тип'),
			'use_configurations'     => Yii::t('StoreModule.core', 'Использовать конфигурации'),
			'name'                   => Yii::t('StoreModule.core', 'Название'),
			'url'                    => Yii::t('StoreModule.core', 'URL'),
			'price'                  => Yii::t('StoreModule.core', 'Цена'),
			'is_active'              => Yii::t('StoreModule.core', 'Активен'),
			'short_description'      => Yii::t('StoreModule.core', 'Краткое описание'),
			'full_description'       => Yii::t('StoreModule.core', 'Полное описание'),
			'meta_title'             => Yii::t('StoreModule.core', 'Meta Title'),
			'meta_keywords'          => Yii::t('StoreModule.core', 'Meta Keywords'),
			'meta_description'       => Yii::t('StoreModule.core', 'Meta Description'),
			'layout'                 => Yii::t('StoreModule.core', 'Макет'),
			'view'                   => Yii::t('StoreModule.core', 'Шаблон'),
			'sku'                    => Yii::t('StoreModule.core', 'Артикул'),
			'quantity'               => Yii::t('StoreModule.core', 'Количество'),
			'availability'           => Yii::t('StoreModule.core', 'Доступность'),
			'auto_decrease_quantity' => Yii::t('StoreModule.core', 'Автоматически уменьшать количество'),
			'created'                => Yii::t('StoreModule.core', 'Дата создания'),
			'updated'                => Yii::t('StoreModule.core', 'Дата обновления'),
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search($params = array(), $additionalCriteria = null)
	{
		$criteria=new CDbCriteria;

		$criteria->with = array(
			'categorization'=>array('together'=>true),
			'manufacturer',
		);

		if($additionalCriteria !== null)
			$criteria->mergeWith($additionalCriteria);

		$criteria->compare('t.id',$this->id);
		$criteria->compare('t.name',$this->name,true);
		$criteria->compare('t.url',$this->url,true);
		$criteria->compare('t.price',$this->price);
		$criteria->compare('t.is_active',$this->is_active);
		$criteria->compare('t.short_description',$this->short_description,true);
		$criteria->compare('t.full_description',$this->full_description,true);
		$criteria->compare('t.sku',$this->sku,true);
		$criteria->compare('t.created',$this->created,true);
		$criteria->compare('t.updated',$this->updated,true);
		$criteria->compare('type_id', $this->type_id);
		$criteria->compare('manufacturer.name', $this->manufacturer_search,true);

		if (isset($params['category']) && $params['category'])
			$criteria->compare('categorization.category', $params['category']);

		// Id of product to exclude from search
		if($this->exclude)
			$criteria->compare('t.id !', array(':id'=>$this->exclude));

		// Create sorting by translation title
		$sort=new CSort;
		$sort->defaultOrder = 't.created DESC';
		$sort->attributes=array(
			'*',
			'manufacturer_search' => array(
				'asc'   => 'manufacturer.name',
				'desc'  => 'manufacturer.name DESC',
			)
		);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>$sort,
			'pagination'=>array(
				'pageSize'=>20,
			)
		));
	}

	public function behaviors()
	{
		return array(
			'eavAttr' => array(
				'class'     => 'ext.behaviors.eav.EEavBehavior',
				'tableName' => 'StoreProductAttributeEAV',
			),
			'comments' => array(
				'class'       => 'comments.components.CommentBehavior',
				'class_name'  => 'store.models.StoreProduct',
				'owner_title' => 'name', // Attribute name to present comment owner in admin panel
			)
		);
	}

	/**
	 * Save related products. Notice, related product will be saved after save() method called.
	 * @param array $ids Array of related products
	 */
	public function setRelatedProducts($ids = array())
	{
		$this->_related = $ids;
	}

	public function beforeSave()
	{
		if (empty($this->url))
		{
			// Create slug
			Yii::import('ext.SlugHelper.SlugHelper');
			$this->url = SlugHelper::run($this->name);
		}

		// Check if url available
		if($this->isNewRecord)
		{
			$test = StoreProduct::model()
				->withUrl($this->url)
				->count();
		}
		else
		{
			$test = StoreProduct::model()
				->withUrl($this->url)
				->count('id!=:id', array(':id'=>$this->id));
		}

		// Create unique url
		if ($test > 0)
			$this->url .= '-'.date('YmdHis');

		return parent::beforeSave();
	}

	public function beforeValidate()
	{
		// For configurable product set 0 price
		if($this->use_configurations)
			$this->price = 0;

		return parent::beforeValidate();
	}

	public function afterSave()
	{
		// Process related products
		if($this->_related !== null)
		{
			$this->clearRelatedProducts();

			foreach($this->_related as $id)
			{
				$related = new StoreRelatedProduct;
				$related->product_id = $this->id;
				$related->related_id = $id;
				$related->save();
			}
		}

		// Save configurable attributes
		if($this->_configurable_attribute_changed === true)
		{
			// Clear
			Yii::app()->db->createCommand()->delete('StoreProductConfigurableAttributes', 'product_id = :id', array(':id'=>$this->id));

			foreach($this->_configurable_attributes as $attr_id)
			{
				Yii::app()->db->createCommand()->insert('StoreProductConfigurableAttributes', array(
					'product_id'   => $this->id,
					'attribute_id' => $attr_id
				));
			}
		}

		// Process min and max price for configurable product
		if($this->use_configurations)
			$this->updatePrices($this);
		else
		{
			// Check if product is configuration
			$query = Yii::app()->db->createCommand()
				->from('StoreProductConfigurations t')
				->where(array('in', 't.configurable_id', array($this->id)))
				->queryAll();

			foreach ($query as $row)
			{
				$model = StoreProduct::model()->findByPk($row['product_id']);
				if($model)
					$this->updatePrices($model);
			}
		}

		return parent::afterSave();
	}

	/**
	 * Update price and max_price for configurbale product
	 */
	public function updatePrices(StoreProduct $model)
	{
		// Get min and max prices
		$query = Yii::app()->db->createCommand()
			->select('MIN(t.price) as min_price, MAX(t.price) as max_price')
			->from('StoreProduct t')
			->where(array('in', 't.id', $model->configurations))
			->queryRow();

		// Update
		Yii::app()->db->createCommand()
			->update('StoreProduct', array(
			'price'     => $query['min_price'],
			'max_price' => $query['max_price']
		), 'id=:id', array(':id'=>$model->id));
	}

	/**
	 * Delete related data.
	 */
	public function afterDelete()
	{
		// Delete related products
		$this->clearRelatedProducts();
		StoreRelatedProduct::model()->deleteAll('related_id=:id', array('id'=>$this->id));

		// Delete categorization
		StoreProductCategoryRef::model()->deleteAllByAttributes(array(
			'product'=>$this->id
		));

		// Delete images
		$images = $this->images;
		if(!empty($images))
		{
			foreach ($images as $image)
				$image->delete();
		}

		// Delete variants
		$variants = StoreProductVariant::model()->findAllByAttributes(array('product_id'=>$this->id));
		foreach ($variants as $v)
			$v->delete();

		// Clear configurable attributes
		Yii::app()->db->createCommand()->delete('StoreProductConfigurableAttributes', 'product_id=:id', array(':id'=>$this->id));

		// Delete configurations
		Yii::app()->db->createCommand()->delete('StoreProductConfigurations', 'product_id=:id', array(':id'=>$this->id));
		Yii::app()->db->createCommand()->delete('StoreProductConfigurations', 'configurable_id=:id', array(':id'=>$this->id));

		return parent::afterDelete();
	}

	/**
	 * Clear all related products
	 */
	private function clearRelatedProducts()
	{
		StoreRelatedProduct::model()->deleteAll('product_id=:id', array('id'=>$this->id));
	}

	/**
	 * @return array
	 */
	public function getAvailabilityItems()
	{
		return array(
			1=>Yii::t('StoreModule.core', 'Есть на складе'),
			2=>Yii::t('StoreModule.core', 'Нет на складе'),
		);
	}

	/**
	 * Set product categories and main category
	 * @param array $categories ids.
	 * @param integer $main_category Main category id.
	 */
	public function setCategories(array $categories, $main_category)
	{
		$dontDelete = array();

		if(!StoreCategory::model()->countByAttributes(array('id'=>$main_category)))
			$main_category = 1;

		if(!in_array($main_category, $categories))
			array_push($categories, $main_category);

		foreach ($categories as $c)
		{
			$count = StoreProductCategoryRef::model()->countByAttributes(array(
				'category'=>$c,
				'product'=>$this->id
			));

			if($count == 0)
			{
				$record = new StoreProductCategoryRef;
				$record->category = (int)$c;
				$record->product = $this->id;
				$record->save(false);
			}

			$dontDelete[] = $c;
		}

		// Clear main category
		StoreProductCategoryRef::model()->updateAll(array(
			'is_main'=>0
		), 'product=:p', array(':p'=>$this->id));

		// Set main category
		StoreProductCategoryRef::model()->updateAll(array(
			'is_main'=>1
		), 'product=:p AND category=:c ', array(':p'=>$this->id,':c'=>$main_category));

		// Delete not used relations
		if(sizeof($dontDelete) > 0)
		{
			$cr = new CDbCriteria;
			$cr->addNotInCondition('category', $dontDelete);

			StoreProductCategoryRef::model()->deleteAllByAttributes(array(
				'product'=>$this->id,
			), $cr);
		}
		else
		{
			// Delete all relations
			StoreProductCategoryRef::model()->deleteAllByAttributes(array(
				'product'=>$this->id,
			));
		}
	}

	/**
	 * Prepare variations
	 * @return array product variations
	 */
	public function processVariants()
	{
		$result = array();
		foreach($this->variants as $v)
		{
			$result[$v->attribute->id]['attribute'] = $v->attribute;
			$result[$v->attribute->id]['options'][] = $v;
		};
		return $result;
	}

	/**
	 * @param $ids array of StoreAttribute pks
	 */
	public function setConfigurable_attributes(array $ids)
	{
		$this->_configurable_attributes = $ids;
		$this->_configurable_attribute_changed = true;
	}

	/**
	 * @return array
	 */
	public function getConfigurable_attributes()
	{
		if($this->_configurable_attribute_changed === true)
			return $this->_configurable_attributes;

		if($this->_configurable_attributes === null)
		{
			$this->_configurable_attributes = Yii::app()->db->createCommand()
				->select('t.attribute_id')
				->from('StoreProductConfigurableAttributes t')
				->where('t.product_id=:id', array(':id'=>$this->id))
				->group('t.attribute_id')
				->queryColumn();
		}

		return $this->_configurable_attributes;
	}

	/**
	 * @return array of product ids
	 */
	public function getConfigurations()
	{
		if(is_array($this->_configurations))
			return $this->_configurations;

		$this->_configurations = Yii::app()->db->createCommand()
			->select('t.configurable_id')
			->from('StoreProductConfigurations t')
			->where('product_id=:id', array(':id'=>$this->id))
			->group('t.configurable_id')
			->queryColumn();

		return $this->_configurations;
	}

	/**
	 * Calculate product price by its variants, confirugation and self price
	 * @static
	 * @param $product
	 * @param array $variants
	 * @param $configuration
	 */
	public static function calculatePrices($product, array $variants, $configuration)
	{
		if(($product instanceof StoreProduct) === false)
			$product = StoreProduct::model()->findByPk($product);

		if(($configuration instanceof StoreProduct) === false && $configuration > 0)
			$configuration = StoreProduct::model()->findByPk($configuration);

		if($configuration instanceof StoreProduct)
			$result = $configuration->price;
		else
			$result = $product->price;

		// if $variants containts not models
		if(!empty($variants) && ($variants[0] instanceof StoreProductVariant) === false)
			$variants = StoreProductVariant::model()->findAllByPk($variants);

		foreach ($variants as $variant)
		{
			// Price is percent
			if($variant->price_type == 1)
				$result += ($result / 100 * $variant->price);
			else
				$result += $variant->price;
		}

		return $result;
	}

	/**
	 * Apply price format
	 * @static
	 * @param $price
	 * @return string formatted price
	 */
	public static function formatPrice($price)
	{
		return money_format('%.2n', $price);
	}

	/**
	 * Convert to active currency and format price
	 * Used in product listing.
	 * @return string
	 */
	public function priceRange()
	{
		$price = Yii::app()->currency->convert($this->price);
		$max_price = Yii::app()->currency->convert($this->max_price);
		$symbol = Yii::app()->currency->active->symbol;

		if($this->use_configurations && $max_price > 0)
			return self::formatPrice($price).' '.$symbol.' - '.self::formatPrice($max_price).' '.$symbol;
		return self::formatPrice($price).' '.$symbol;
	}

	/**
	 * Convert price to currenct currency
	 * @return float
	 */
	public function toCurrentCurrency()
	{
		return Yii::app()->currency->convert($this->price);
	}

	public function __get($name)
	{
		if(substr($name,0,4) === 'eav_')
		{
			if($this->getIsNewRecord())
				return null;

			$attribute = substr($name, 4);
			$eavData = $this->getEavAttributes();

			if(isset($eavData[$attribute]))
				$value = $eavData[$attribute];
			else
				return null;

			$attributeModel = StoreAttribute::model()->with('options')->findByAttributes(array('name'=>$attribute));
			return $attributeModel->renderValue($value);
		}
		return parent::__get($name);
	}

}