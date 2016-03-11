<?php

/**
 * @file
 * Contains RestfulFormatterJson.
 */

require_once DRUPAL_ROOT . '/sites/all/modules/contrib/restful/plugins/formatter/json/RestfulFormatterJson.class.php';

class RestfulFormatterCsv extends \RestfulFormatterBase implements \RestfulFormatterInterface {

  /**
   * Content Type
   *
   * @var string
   */
  protected $contentType = 'text/csv; charset=utf-8';

  /**
   * @var string class to export to JSON.
   */
  protected $parserCsvClass = 'Keboola\Json\Parser';

  /**
   * @var Keboola\Json\Parser Parser.
   */
  protected $parser;

  /**
   * @var array Results processed by the parser.
   */
  protected $results = array();

  /**
   * Constructor.
   */
  public function __construct(array $plugin, $handler = NULL) {
    parent::__construct($plugin, $handler);
    $this->parser = $this->parser();
  }

  /**
   * Obtains parser. Load if not set.
   * 
   * @return Keboola\Json\Parser
   *   Parser
   */
  public function parser() {
    if (empty($this->parser)) {
      $this->parser = call_user_func($this->parserCsvClass . '::create', new \Monolog\Logger('json-parser'));
    }
    return $this->parser;
  }


  /**
   * {@inheritdoc}
   */
  public function prepare(array $data) {
    $this->changeFileName();
    $data = is_object($data) ? array($data) : $data;
    $output = $this->dataToObject($data);
    return (array) $output;
  }

  /**
   * Change csv file name to add csv extension.
   */
  public function changeFileName() {
    header('Content-disposition: filename="' . $this->handler->plugin['resource'] . '.csv"');
  }

  /**
   * Convert data to object.
   * 
   * @param array $array
   *   Array.
   * 
   * @return \stdClass
   *   Object
   */
  public function dataToObject($array) {
		$obj = new stdClass;
    foreach($array as $k => $v) {
       if(strlen($k)) {
          // If object, convert all properties in object.
        if (is_object($v)) {
          $obj->{$k} = $this->objectPropertiesToObject($v);
        }
        elseif(is_array($v)) {
          $obj->{$k} = self::dataToObject($v);
        } else {
          $obj->{$k} = $v;
        }
      }
    }
    return $obj;
  }

  /**
   * Convert object properties in object, if it's array.
   *
   * @param object $object
   *   Object.
   */
  public function objectPropertiesToObject($object) {
    $vars = get_object_vars($object);
    $new_object = new stdClass;
    foreach ($vars as $property => $var) {
      if (is_array($var)) {
        $new_object->{$property} = $this->dataToObject($var);
      }
      else {
        $new_object->{$property} = $var;
      }
    }
    return $new_object;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $structured_data) {
    $this->parser->process($structured_data);
    $this->results = $this->parser->getCsvFiles();
    return $this->processResults();
  }

  public function processResults() {
    $content = '';
    $element = $this->results['root'];
    foreach ($element as $result) {
      $content .= $element->rowToStr($result);
    }
    return $content;
  }


  /**
   * {@inheritdoc}
   */
  public function getContentTypeHeader() {
    return $this->contentType;
  }
}

