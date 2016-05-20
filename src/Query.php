<?php

namespace chsergey\rest;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use yii\base\Component;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use yii\web\HttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class Query 
 * HTTP transport by GuzzleHTTP
 * 
 * @package chsergey\rest 
 */
class Query extends Component implements QueryInterface {
	/**
	 * Data type for requests and responses
	 * Required.
	 * @var string
	 */
	public $dataType = self::JSON_TYPE;
	/**
	 * Headers for requests
	 * @var array
	 */
	public $requestHeaders = [];
	/**
	 * Wildcard for response headers object
	 * @see Query::count()
	 * @var array
	 */
	public $responseHeaders = [
		'totalCount'	=> 'X-Pagination-Total-Count',
		'pageCount'		=> 'X-Pagination-Page-Count',
		'currPage'		=> 'X-Pagination-Current-Page',
		'perPageCount'	=> 'X-Pagination-Per-Page',
		'links'			=> 'Link',
	];
	/**
	 * Response unserializer class
	 * @var array|object
	 */
	public $unserializers = [
		self::JSON_TYPE => [
			'class' => 'chsergey\rest\JsonUnserializer'
		]
	];
	/**
	 * HTTP client that performs HTTP requests
	 * @var object
	 */
	public $httpClient;
	/**
	 * Model class
	 * @var Model
	 */
	public $modelClass;
	/**
	 * Get param name for select fields
	 * @var string
	 */
	public $selectFieldsKey = 'fields';
	/**
	 * Request LIMIT param name
	 * @see chsergey\rest\Model::$limitKey
	 * @var string
	 */
	public $limitKey;
	/**
	 * Request OFFSET param name
	 * @see chsergey\rest\Model::$offsetKey
	 * @var string
	 */
	public $offsetKey;
	/**
	 * Model class envelope
	 * @see chsergey\rest\Model::$collectionEnvelope
	 * @var string
	 */
	private $_collectionEnvelope;
	/**
	 * Model class pagination envelope
	 * @see chsergey\rest\Model::$paginationEnvelope
	 * @var string
	 */
	private $_paginationEnvelope;
	/**
	 * Model class pagination envelope keys mapping
	 * @see chsergey\rest\Model::$paginationEnvelopeKeys
	 * @var array
	 */
	private $_paginationEnvelopeKeys;
	/**
	 * Pagination data from pagination envelope in GET request
	 * @var array
	 */
	private $_pagination;
	/**
	 * Array of fields to select from REST
	 * @var array
	 */
	private $_select = [];
	/**
	 * Conditions
	 * @var array
	 */
	private $_where;
	/**
	 * Query limit
	 * @var int
	 */
	private $_limit;
	/**
	 * Query offset
	 * @var int
	 */
	private $_offset;
	/**
	 * Flag Is this query is sub-query
	 * to prevent recursive requests
	 * for get enveloped pagination
	 * @see Query::count()
	 * @var bool
	 */
	private $_subQuery = false;


	/**
	 * Constructor. Really.
	 * @param Model $modelClass
	 * @param array $config
	 */
	public function __construct($modelClass, $config = []) {
		$modelClass::staticInit();
		$this->modelClass = $modelClass;
		$this->_collectionEnvelope = $modelClass::$collectionEnvelope;
		$this->_paginationEnvelope = $modelClass::$paginationEnvelope;
		$this->_paginationEnvelopeKeys = $modelClass::$paginationEnvelopeKeys;
		$this->offsetKey = $modelClass::$offsetKey;
		$this->limitKey = $modelClass::$limitKey;

		$this->httpClient = new Client([
			/* @link http://docs.guzzlephp.org/en/latest/quickstart.html */
			'base_uri' => $this->_getUrl('api'),
			/* @link http://docs.guzzlephp.org/en/latest/request-options.html#headers */
			'headers' => $this->_getRequestHeaders(),
		]);

		parent::__construct($config);
	}

	/**
	 * GET resource collection request
	 * @inheritdoc
	 */
	public function all() {

		return $this->_populate(
			$this->_request('get',
				$this->_getUrl('collection'),
				[
					'query' => $this->_buildQueryParams()
				]
			)
		);
	}

	/**
	 * Get collection count
	 * If $this->_pagination isset (from get request before call this method) return count from it
	 * else execute HEAD request to collection and get count from X-Pagination-Total-Count(default) response header
	 * If header is empty and isset pagination envelope - do get collection request with limit 1 to get pagination data
	 * @see Query::$_subQuery
	 * @inheritdoc
	 */
	public function count() {

		if($this->_pagination) {
			return isset($this->_pagination['totalCount']) ? (int) $this->_pagination['totalCount'] : 0;
		}

		if($this->_subQuery) {
			return 0;
		}

		// try to get count by HEAD request
		$count = $this->_request('head', $this->_getUrl('collection'), ['query' => $this->_buildQueryParams()])
			->getHeaderLine($this->responseHeaders['totalCount']);

		// REST server not allow HEAD query and X-Total header is empty
		if($count === '' && $this->_paginationEnvelope) {
			$query = clone $this;
			$query->_setSubQueryFlag()->offset(0)->limit(1)->all();
			return $query->count();
		}

		return (int) $count;
	}

	/**
	 * GET resource element request
	 * @inheritdoc
	 */
	public function one($id) {

		if($this->_where) {
			throw new InvalidCallException(__METHOD__.'() can not be called with "where" clause');
		}

		$model = $this->_populate(
			$this->_request('get',
				$this->_getUrl('element', $id),
				[
					'query' => $this->_buildQueryParams()
				]
			),
			false
		);

		return $model;
	}

	/**
	 * POST request
	 * @inheritdoc
	 */
	public function create(Model $model) {

		return $this->_populate(
			$this->_request('post', $this->_getUrl('collection'), [
				'json' => $model->getAttributes()
			]),
			false
		);
	}

	/**
	 * PUT request
	 * // TODO non-json (i.e. form-data) payload
	 * @inheritdoc
	 */
	public function update(Model $model) {

		return $this->_populate(
			$this->_request('put', $this->_getUrl('element', $model->getPrimaryKey()), [
				'json' => $model->getAttributes()
			]),
			false
		);
	}

	/**
	 * @inheritdoc
	 */
	public function select(array $fields) {
		$this->_select = $fields;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function where(array $conditions) {
		$this->_where = $conditions;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function limit($limit) {
		$this->_limit = (int) $limit;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function offset($offset) {
		$this->_offset = (int) $offset;
		return $this;
	}

	/**
	 * HTTP request
	 * @param string $method
	 * @param string $url
	 * @param array $options
	 * @return ResponseInterface
	 * @throws ServerErrorHttpException
	 */
	private function _request($method, $url, array $options) {
		try {
			$response = $this->httpClient->{$method}($url, $options);
		} catch(ClientException $e) {
			$response = $e->getResponse();
		} catch(ConnectException $e) {
			$this->_throwServerError($e);
		} catch (RequestException $e) {
			$this->_throwServerError($e);
		}

		return $response;
	}

	/**
	 * Throw 500 error exception
	 * @param \Exception $e
	 * @throws ServerErrorHttpException
	 */
	private function _throwServerError(\Exception $e) {
		$uri = (string) $this->httpClient->getConfig('base_uri');

		throw new ServerErrorHttpException(get_class($e).': url='.$uri .' '. $e->getMessage(), 500);
	}

	/**
	 * Unserialize and create models
	 * @param ResponseInterface $response
	 * @param bool $asCollection
	 * @return $this|Model|array|void
	 * @throws HttpException
	 */
	private function _populate(ResponseInterface $response, $asCollection = true) {
		$models = [];
		$statusCode = $response->getStatusCode();
		$data = $this->_unserializeResponseBody($response);

		// errors
		if($statusCode >= 400) {

			throw new HttpException(
				$statusCode,
				is_string($data) ? $data : $data->message,
				$statusCode
			);
		}

		// array of objects or arrays - probably resource collection
		if(is_array($data)) {
			$models = $this->_createModels($data);
		}
		// collection with data envelope or single element
		if(is_object($data)) {
			if($asCollection) {
				if($this->_collectionEnvelope) {
					$elements = isset($data->{$this->_collectionEnvelope})
						? $data->{$this->_collectionEnvelope}
						: [];
				} else {
					$elements = [];
				}
				if($this->_paginationEnvelope && isset($data->{$this->_paginationEnvelope})) {
					$this->_setPagination(
						$this->_getProps($data->{$this->_paginationEnvelope})
					);
				}
				$models = $this->_createModels($elements);
			} else {
				$models = $this->_createModels([$data])[0];
			}
		}

		return $models;
	}

	/**
	 * Create models from array of elements
	 * @param array $elements
	 * @return array
	 */
	private function _createModels(array $elements) {
		$modelClass = $this->modelClass;
		$models = [];
		foreach ($elements as $element) {
			$model = $modelClass::instantiate()->setAttributes($this->_getProps($element));
			$models[] = $model->setId(
				$model->getAttribute($modelClass::primaryKey()[0])
			);
		}

		return $models;
	}

	/**
	 * Try to unserialize response body data
	 * @param ResponseInterface $response
	 * @return object[]|object|string
	 * @throws \yii\base\InvalidConfigException
	 */
	private function _unserializeResponseBody(ResponseInterface $response) {

		$body = (string) $response->getBody();
		$contentType = $response->getHeaderLine('Content-type');

		try {
			if(false !== stripos($contentType, $this->dataType)
				&& isset($this->unserializers[$this->dataType])) {
				/** @var UnserializerInterface $unserializer */
				$unserializer = \Yii::createObject($this->unserializers[$this->dataType]);
				if($unserializer instanceof UnserializerInterface) {
					return $unserializer->unserialize($body, false);
				}
			}

			return $body;

		} catch(InvalidParamException $e) {

			return $body;
		}
	}

	/**
	 * Pagination data setter
	 * If pagination data isset in GET request result
	 * @param array $pagination
	 * @return $this
	 */
	private function _setPagination(array $pagination) {
		foreach ($this->_paginationEnvelopeKeys as $key => $name) {
			$this->_pagination[$key] = isset($pagination[$name])
				? $pagination[$name]
				: null;
		}

		return $this;
	}

	/**
	 * Get array of properties from object
	 * @param $object
	 * @return array
	 */
	private function _getProps($object) {

		return is_object($object) ? get_object_vars($object) : $object;
	}

	/**
	 * Build query params
	 * @return array
	 */
	private function _buildQueryParams() {
		$query = [];

		$this->_where = is_array($this->_where) ? $this->_where : [];
		foreach ($this->_where as $key => $val) {
			$query[$key] = is_numeric($val) ? (int) $val : $val;
		}

		if(count($this->_select)) {
			$query[$this->selectFieldsKey] = implode(',', $this->_select);
		}
		if($this->_limit !== null) {
			$query[$this->limitKey] = $this->_limit;
		}
		if($this->_offset !== null) {
			$query[$this->offsetKey] = $this->_offset;
		}

		return $query;
	}

	/**
	 * Get headers for request
	 * @return array
	 */
	private function _getRequestHeaders() {

		return $this->requestHeaders ?: ['Accept' => $this->dataType];
	}

	/**
	 * Get url to collection or element of resource
	 * with check base url trailing slash
	 * @param string $type api|collection|element
	 * @param string $id
	 * @return string
	 */
	private function _getUrl($type = 'base', $id = 'null') {
		$modelClass = $this->modelClass;
		$collection = $modelClass::getResourceName();

		switch($type) {
			case 'api':
				return $this->_trailingSlash($modelClass::getApiUrl());
				break;
			case 'collection':
				return $this->_trailingSlash($collection, false);
				break;
			case 'element':
				return $this->_trailingSlash($collection) . $this->_trailingSlash($id, false);
				break;
		}

		return '';
	}

	/**
	 * Check trailing slash
	 * if $add - add trailing slash
	 * if not $add - remove trailing slash
	 * @param $string
	 * @param bool $add
	 * @return string
	 */
	private function _trailingSlash($string, $add = true) {

		return substr($string, -1) === '/'
			? ($add ? $string : substr($string, 0, strlen($string) - 1))
			: ($add ? $string . '/' : $string);
	}

	/**
	 * Mark query as subquery to prevent queries recursion
	 * @see count()
	 * @return Query
	 */
	private function _setSubQueryFlag() {
		$this->_subQuery = true;
		return $this;
	}
}