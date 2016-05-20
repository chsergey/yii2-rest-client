<?php

namespace chsergey\rest;

/**
 * Interface QueryInterface
 * Query to REST interface
 * 
 * @package chsergey\rest
 */
interface QueryInterface {
	/**
	 * JSON header
	 */
	const JSON_TYPE = 'application/json';
	/**
	 * XML header
	 */
	const XML_TYPE = 'application/xml';


	/**
	 * GET request to collection
	 * @return Model[]
	 */
	public function all();

	/**
	 * OPTIONS request to collection
	 * @return int
	 */
	public function count();

	/**
	 * GET request to resource element by id
	 * @param string $id
	 * @return Model
	 */
	public function one($id);

	/**
	 * POST request
	 * @param Model $model
	 * @return Model
	 * @internal param Model $payload
	 */
	public function create(Model $model);

	/**
	 * PUT request
	 * @param Model $model
	 * @return Model
	 * @internal param Model $payload
	 */
	public function update(Model $model);

	/**
	 * Set fields to select
	 * @param array $fields
	 * @return Query
	 */
	public function select(array $fields);

	/**
	 * Add conditions to filter in request to collection
	 * @param array $conditions
	 * @return Query
	 */
	public function where(array $conditions);

	/**
	 * Set limit to request to collection
	 * @param int $limit
	 * @return Query
	 */
	public function limit($limit);

	/**
	 * Set offset to request to collection
	 * @param int $offset
	 * @return Query
	 */
	public function offset($offset);
}