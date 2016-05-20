<?php

namespace chsergey\rest;

use yii\base\InvalidConfigException;

/**
 * Interface ModelInterface
 * 
 * @package chsergey\rest
 */
interface ModelInterface {
	/**
	 * Scenario for POST request
	 */
	const SCENARIO_CREATE = 'create';
	/**
	 * Scenario for PUT request
	 */
	const SCENARIO_UPDATE = 'update';


	/**
	 * Static constructor. Setup static properties here.
	 * @return void
	 * @throws InvalidConfigException
	 */
	public static function staticInit();

	/**
	 * Get primary key name(s)
	 * @return string[]
	 */
	public static function primaryKey();

	/**
	 * Create instance of QueryInterface
	 * @return Query
	 */
	public static function find();

	/**
	 * Perform request to get collection of resource
	 * with filter by conditions
	 * @param array $conditions
	 * @return Model[]
	 */
	public static function findAll(array $conditions);

	/**
	 * Find record by primary key
	 * @see getPrimaryKey()
	 * @param string|int $id
	 * @return Model
	 */
	public static function findOne($id);

	/**
	 * Get REST API url
	 * @return string
	 */
	public static function getApiUrl();

	/**
	 * Get primary key value
	 * @return string
	 */
	public function getPrimaryKey();

	/**
	 * Set primary key
	 * @param $id
	 * @return Model
	 */
	public function setId($id);

	/**
	 * Get REST resource name
	 * @return string
	 */
	public static function getResourceName();

	/**
	 * Create new instance of class
	 * @return Model
	 */
	public static function instantiate();

	/**
	 * Attribute getter
	 * @param $name
	 * @return mixed
	 */
	public function getAttribute($name);

	/**
	 * Attribute setter
	 * @param $name
	 * @param $value
	 * @return Model
	 */
	public function setAttribute($name, $value);

	/**
	 * Check attribute exists
	 * @param $name
	 * @return mixed
	 */
	public function hasAttribute($name);

	/**
	 * Model attributes getter
	 * @return array
	 */
	public function getAttributes();

	/**
	 * Model attributes setter
	 * @param array $attributes
	 * @param bool $useForce
	 * @return Model
	 */
	public function setAttributes(array $attributes, $useForce = false);

	/**
	 * Remove attribute
	 * @param $name
	 * @return Model
	 */
	public function unsetAttribute($name);

	/**
	 * Save model
	 * @return bool
	 */
	public function save();
}