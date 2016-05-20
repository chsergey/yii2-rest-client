<?php

namespace chsergey\rest;

use yii\helpers\Json;

/**
 * Class JsonUnserializer
 * 
 * @package chsergey\rest
 */
class JsonUnserializer extends Unserializer {

	/**
	 * @param string $data
	 * @param bool $asArray
	 * @return mixed
	 */
	public function unserialize($data, $asArray = true) {

		return Json::decode($data, $asArray);
	}
}