<?php

/**
 * Product cart
 */
class SCart extends CComponent
{
	/**
	 * List of products added to cart.
	 * Sample data:
	 * array(
	 *      'product_id'=>1,
	 *      'variants'=>array(StoreProductVariant_id),
	 *      'configurable_id'=>2, // Id of configurable product or false.
	 *      'quantity'=>3,
	 *      'price'=>123 // Price of one item
	 * )
	 * @var array
	 */
	private $_items = array();

	/**
	 * @var CHttpSession
	 */
	private $session;

	public function init()
	{
		$this->session = Yii::app()->session;

		if(!isset($this->session['cart_data']) || !is_array($this->session['cart_data']))
			$this->session['cart_data'] = array();
	}

	/**
	 * Add product to cart
	 * @param array $data
	 */
	public function add(array $data)
	{
		$itemIndex = $this->getItemIndex($data);

		$currentData = $this->getData();

		if(isset($currentData[$itemIndex]))
			$currentData[$itemIndex]['quantity']++;
		else
			$currentData[$itemIndex] = $data;

		$this->session['cart_data'] = $currentData;
	}

	/**
	 * Removed item from cart
	 * @param $index generated by self::getItemIndex() method
	 */
	public function remove($index)
	{
		$currentData = $this->getData();
		if(isset($currentData[$index]))
			unset($currentData[$index]);
		$this->session['cart_data'] = $currentData;
	}

	/**
	 * Clear all cart data
	 */
	public function clear()
	{
		$this->session['cart_data'] = array();
	}

	/**
	 * @return array current cart data
	 */
	public function getData()
	{
		return $this->session['cart_data'];
	}

	public function getDataWithModels()
	{
		$data = $this->getData();

		if(empty($data)) return array();

		foreach($data as $index=>&$item)
		{

			$item['variant_models'] = array();
			$item['model'] = StoreProduct::model()->findByPk($item['product_id']);

			// Load configurable product
			if($item['configurable_id'])
				$item['configurable_model'] = StoreProduct::model()->findByPk($item['configurable_id']);

			// Process variants
			if(!empty($item['variants']))
				$item['variant_models'] = StoreProductVariant::model()->with(array('attribute', 'option'))->findAllByPk($item['variants']);

			// If product was deleted or id changed.
			if(!$item['model'])
				unset($data[$index]);
		}

		unset($item);

		return $data;
	}

	public function getTotalPrice()
	{

	}

	/**
	 * Recount quantity by index
	 * @param $data array(index=>quntity)
	 */
	public function recount($data)
	{
		if(!is_array($data) || empty($data))
			return;

		$currentData = $this->getData();
		foreach($data as $index=>$quantity)
		{
			if((int)$quantity < 1)
				$quantity = 1;

			if(isset($currentData[$index]))
				$currentData[$index]['quantity'] = (int) $quantity;
		}
		$this->session['cart_data'] = $currentData;
	}

	/**
	 * @return int nuber of items in cart
	 */
	public function countItems()
	{
		return count($this->session['cart_data']);
	}

	/**
	 * Create item index base on data
	 * @param $data
	 */
	public function getItemIndex($data)
	{
		return $data['product_id'].implode('_', $data['variants']).$data['configurable_id'];
	}
}
