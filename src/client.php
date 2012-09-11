<?php

namespace ua_parser;

use \JsonSerializable;

/**
 * holds client-informations
 * 
 */
class Client implements JsonSerializable
{
  private $ua;
  private $data = [];
  
  /**
   * constructor
   * 
   * @param string $ua
   */
  public function __construct($ua)
  {
    $this->ua = $ua;
  }
  
  /**
   * getUserAgent()
   * 
   * @return string
   */
  public function getUserAgent()
  {
    return $this->ua;
  }
  
  /**
   * toJSON()
   * 
   * @return string
   */
  public function toJSON()
  {
    return json_encode($this->data);
  }
  
  /**
   * toArray()
   * 
   * @return array
   */
  public function toArray()
  {
    $data = [];
    
    foreach ($this->data as $k => $v)
      $data[$k] = ($v instanceof ClientExtra) ? $v->toArray() : $v;
    
    return $data;
  }
  
  /**
   * JsonSerializable::jsonSerialize()
   * 
   * @return array
   */
  public function jsonSerialize()
  {
    return $this->toArray();
  }
  
  // where the magic happens
  
  public function __get($key) 
  {
    return $this->data[$key];
  }
  
  public function __set($key, $val)
  {
    $this->data[$key] = $val;
  }
  
  public function __isset($key)
  {
    return isset($this->data[$key]);
  }
  
  public function __unset($key)
  {
    unset($this->data[$key]);
  }
  
  public function __clone()
  {
    foreach ($this->data as $k => $v)
      if ($v instanceof ClientExtra)
        $this->data[$k] = clone $v;
  }
  
  public function __toString()
  {
    return $this->toJSON();
  }
}
