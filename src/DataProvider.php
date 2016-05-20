<?php

namespace chsergey\rest;

use yii\base\InvalidConfigException;
use yii\data\BaseDataProvider;

/**
 * Class DataProvider
 *
 * @package chsergey\rest
 */
class DataProvider extends BaseDataProvider {
	/**
	 * @var Query
	 */
	public $query;


	/**
	 * Prepares the data models that will be made available in the current page.
	 * @return array the available data models
	 * @throws InvalidConfigException
	 */
	protected function prepareModels() {
		if(!$this->query instanceof QueryInterface) {
			throw new InvalidConfigException(
				'The "query" property must be an instance of a class that implements the '.
				'chsergey\rest\QueryInterface or its subclasses.'
			);
		}

		$query = clone $this->query;

		$this->setPagination([
			'pageSizeLimit' => [1, 1000]
		]);

		if (($pagination = $this->getPagination()) !== false) {
			$pagination->totalCount = $this->getTotalCount();
			$query->limit($pagination->getLimit())
				->offset($pagination->getOffset());
		}

		return $query->all();
	}

	/**
	 * Prepares the keys associated with the currently available data models.
	 * @param Model[] $models the available data models
	 * @return array the keys
	 */
	protected function prepareKeys($models) {
		$keys = [];
		foreach ($models as $model) {
			$keys[] = $model->getPrimaryKey();
		}
		return $keys;
	}

	/**
	 * Returns a value indicating the total number of data models in this data provider.
	 * @return integer total number of data models in this data provider.
	 */
	protected function prepareTotalCount() {

		return $this->query->count();
	}
}