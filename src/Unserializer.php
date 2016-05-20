<?php

namespace chsergey\rest;

use yii\base\Component;

/**
 * Class Unserializer
 * 
 * @package chsergey\rest
 */
abstract class Unserializer extends Component implements UnserializerInterface {

	/**
	 * @inheritdoc
	 */
	public function unserialize($data, $asArray = true) {

		return $asArray ? (array) $data : $data;
	}
}