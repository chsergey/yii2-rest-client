<?php

namespace chsergey\rest;

/**
 * Interface UnserializerInterface
 * 
 * @package chsergey\rest
 */
interface UnserializerInterface {

	/**
	 * Unserialize data from JSON, XML, CSV, etc.
	 * @param string $data
	 * @param bool $asArray
	 * @return mixed
	 */
	public function unserialize($data, $asArray = true);
}