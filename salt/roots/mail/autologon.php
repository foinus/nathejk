<?php

/**
 * Sample plugin to try out some hooks.
 * This performs an automatic login if accessed from localhost
 *
 * @license GNU GPLv3+
 * @author Thomas Bruederli
 */
class autologon extends rcube_plugin
{
  public $task = 'login';

  function init()
  {
    $this->add_hook('startup', array($this, 'startup'));
    $this->add_hook('authenticate', array($this, 'authenticate'));
  }

  function startup($args)
  {
    $rcmail = rcmail::get_instance();

    // change action to login
//    if (empty($_SESSION['user_id']) && !empty($_GET['_autologin']) && $this->is_localhost())
      $args['action'] = 'login';

    return $args;
  }

  function authenticate($args)
  {
 //   if (!empty($_GET['_autologin']) && $this->is_localhost()) {
      $args['user'] = 'mailrecipient';
      $args['pass'] = 'p0stm4ndp3r';
      $args['host'] = 'tls://localhost';
      $args['cookiecheck'] = false;
      $args['valid'] = true;
   // }
  
    return $args;
  }

  function is_localhost()
  {
    return $_SERVER['REMOTE_ADDR'] == '::1' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1';
  }

}

