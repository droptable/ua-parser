<?php

/*!
 * ua-parser for php 5.4
 * 
 * Based on: 
 * - https://github.com/tobie/ua-parser
 * - https://github.com/tobie/ua-parser/blob/master/php/UAParser.php
 * 
 * Copyright (c) 2012 murdoc.eu
 * Licensed under the MIT license
 *  
 */

namespace ua_parser;

use \InvalidArgumentException as IAEx;

class Parser
{
  private $_regexes;
  
  /**
   * constructor
   * 
   * @param string $yaml (optional)
   */
  public function __construct($yaml = null)
  {
    require_once __DIR__ . '/../lib/spyc-0.5/spyc.php';
    
    if (is_null($yaml))
      $yaml = __DIR__ . '/../lib/regexes.yaml';
    
    $yaml = realpath($yaml);
    
    if ($yaml === false)
      throw new IAEx('yaml file not found: ' . $yaml);
    
    // load regexes from yaml-file
    $this->_regexes = spyc_load_file($yaml);
  }
  
  /**
   * parses an user-agent
   * 
   * @param  string $ua
   * @return Client
   */
  public function parse($ua)
  {
    require_once 'client.php';
    
    $cl = new Client($ua);
    
    foreach ($this->_regexes['user_agent_parsers'] as $psr) {
      if (!preg_match('/' . addcslashes($psr['regex'], '/') . '/', $ua, $match))
        continue;
      
      $this->process($cl, $psr, $match);
      break;
    }
    
    return $cl;
  }
  
  /**
   * applies matched user-agent data to the client-interface
   * 
   * @param  Client $cl
   * @param  array  $psr
   * @param  array  $match
   * @return void
   */
  protected function process(Client $cl, array &$psr, array &$match)
  {
    $ua = $cl->getUserAgent();
     
    // MAJOR
    if (isset($psr['v1_replacement']))
      $cl->major = $psr['v1_replacement'];
    elseif (isset($match[2]))
      $cl->major = $match[2];
    
    // MINOR
    if (isset($psr['v2_replacement']))
      $cl->minor = $psr['v2_replacement'];
    elseif (isset($match[3]))
      $cl->minor = $match[3];
    
    // BUILD
    if (isset($match[4])) $cl->build = $match[4];

    // REVISION
    if (isset($match[5])) $cl->revision = $match[5];
    
    // BROWSER
    $cl->browser = isset($psr['family_replacement'])
      ? strtr($psr['family_replacement'], [ '$1' => $cl->major ])
      : $match[1];
    
    // VERSION
    $cl->version = isset($cl->major) ? $cl->major : '';
    foreach ([ 'minor', 'build', 'revision '] as $vt)
      if (isset($cl->{$vt})) $cl->version .= '.' . $cl->{$vt};
    
    // prettify
    $cl->full = $cl->browser . (!empty($cl->version) ? " {$cl->version}" : '');
    
    // detect if this is a uiwebview call on iOS
    $cl->isUIWebview = $cl->browser === 'Mobile Safari' && !strstr($ua, 'Safari');
    
    // check to see if this is a mobile browser
    $cl->isMobile = in_array($cl->browser, $this->_regexes['mobile_user_agent_families']);
    
    // figure out the OS for the browser, if possible
    $this->parseOs($cl);
    
    // figure out the device name for the browser, if possible
    $this->parseDevice($cl);
    
    // if OS is Android check to see if this is a tablet. won't work on UA strings less than Android 3.0
    // based on: http://googlewebmastercentral.blogspot.com/2011/03/mo-better-to-also-detect-mobile-user.html
    // opera doesn't follow this convention though...
    if (isset($cl->os) && $cl->os->name === 'Android'
      && stristr($ua, 'Mobile') !== false
      && stristr($ua, 'Opera') !== false)
      $cl->device->isTablet = true;
    
    // record if this is a spider
    $cl->isSpider = isset($cl->device) && $cl->device->name === 'Spider';
    
    // record if this is a computer
    $cl->isComputer = !($cl->isMobile || $cl->isSpider) && (!isset($cl->device) || !$cl->device->isMobile);
  }
  
  /**
   * tries to check the ua-device
   * 
   * @param  Client $cl
   * @return void
   */
  protected function parseDevice(Client $cl)
  {
    static $tablets = [ 
      'Kindle', 'iPad', 'Playbook', 'TouchPad', 
      'Dell Streak', 'Galaxy Tab', 'Xoom' 
    ];
    
    $ua = $cl->getUserAgent();
    
    foreach ($this->_regexes['device_parsers'] as $psr) {
      if (!preg_match('/' . addcslashes($psr['regex'], '/'). '/', $ua, $match))
        continue;
      
      require_once 'client_extra.php';
      
      $dv = $cl->device = new ClientExtra;
      
      for ($i = 1; $i < 4; ++$i)
        if (!isset($match[$i])) $match[$i] = 0;
      
      // MAJOR / MINOR
      $dv->major = isset($psr['device_v1_replacement']) ? $psr['device_v1_replacement'] : $match[2];
      $dv->minor = isset($psr['device_v2_replacement']) ? $psr['device_v2_replacement'] : $match[3];
      
      $dv->device = $dv->name = isset($psr['device_replacement'])
        ? strtr($psr['device_replacement'], [ '$1' => $match[1] ])
        : $match[1];
      
      $dv->version = "{$dv->major}.{$dv->minor}";
      
      // prettify
      $dv->full = "{$dv->name} {$dv->version}";
      
      if (!($dv->isMobile = $cl->isMobile) && in_array($dv->device, $this->_regexes['mobile_os_families']))
        $dv->isMobile = $cl->isMobile = true;
      
      $dv->isTablet = false;
      
      foreach ($tablets as $tn) {
        if (stristr($dv->name, $tn) !== false) {
          $dv->isTablet = true;
          $dv->isMobile = true;
          break;
        }
      }
      
      break;
    }
  }
  
  /**
   * tries to check the ua-os
   * 
   * @param  Client $cl
   * @return void
   */
  protected function parseOs(Client $cl)
  {
    $ua = $cl->getUserAgent();
    
    foreach ($this->_regexes['os_parsers'] as $psr) {
      if (!preg_match('/' . addcslashes($psr['regex'], '/') . '/', $ua, $match))
        continue;
      
      require_once 'client_extra.php';
      
      $os = $cl->os = new ClientExtra;
    
      for ($i = 1; $i < 4; ++$i)
        if (!isset($match[$i])) $match[$i] = 0;
        
      // MAJOR / MINOR
      $os->major = isset($psr['os_v1_replacement']) ? $psr['os_v1_replacement'] : $match[2];
      $os->minor = isset($psr['os_v2_replacement']) ? $psr['os_v2_replacement'] : $match[3];
      
      // BUILD
      if (isset($match[4])) $os->build = $match[4];
      
      // REVISION
      if (isset($match[5])) $os->revision = $match[5];
      
      $os->os = $os->name = isset($psr['os_replacement'])
        ? strtr($psr['os_replacement'], [ '$1' => $os->major ])
        : $match[1];
      
      // os version
      $os->version = "{$os->major}.{$os->minor}";
      foreach ([ 'build', 'revision' ] as $vt)
        if (isset($os->{$vt})) $os->version .= '.' . $os->{$vt};
      
      // prettify
      $os->full = "{$os->name} {$os->version}";
      break;
    }
  }
}
