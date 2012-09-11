<?php

namespace ua_parser;

use \JsonSerializable;

/**
 * holds extra informations for the client
 * like: "os" or "device"
 * 
 */
class ClientExtra implements JsonSerializable
{
  private $data = [];
  
  /**
   * toArray()
   * 
   * @return array
   */
  public function toArray()
  {
    $data = [];
    
    foreach ($this->data as $k => $v)
      $data[$k] = ($v instanceof self) ? $v->toArray() : $v;
    
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
      if ($v instanceof self)
        $this->data[$k] = clone $v;
  }
}
