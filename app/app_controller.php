<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package       cake
 * @subpackage    cake.app
 */

Configure::load('facebook');
App::import('Vendor', 'Facebook');

class AppController extends Controller {
  var $components = array('Auth', 'Session');
  var $uses = array('User');
  
  function beforeFilter() {
    parent::beforeFilter();
    $this->facebook = new Facebook(Configure::read('Facebook'));
    
    //validates the user always
    $this->validateUser();
    
    $this->set('facebook', $this->facebook);
  }
  
  function validateUser() {
  	//set a session var as we are not logged in
  	$this->Session->write('Facebook.loggedIn', false);
	  $this->Session->write('Facebook.facebook', $this->facebook);
  	
  	//convenience vars
    $Auth = $this->Auth;
    $fb = $this->facebook;
    $session = $fb->getSession();
    
    //we don't have a facebook session
    if (empty($session)) {
    	//means that we are not in facebook
      if (!isset($this->params['url']['signed_request'])) {
      	$this->redirect($this->facebook->getLoginUrl(array('fbconnect' => 0, 'req_perms' => 'publish_stream')));
      }
      
      return true;
    }
    
	  // check if the user already has an account
    // User is logged in but doesn't have a 
    if ($Auth->user()) {
      $this->User->id = $Auth->user('id');
      if (!$this->User->field('facebook_id')) {
        $this->User->saveField('facebook_id', $session['uid']);
      }
    } 
    else {
      // attempt to find the user by their facebook id
      $user = $this->User->findByFacebookId($session['uid']);
      
      //if we have a user, set hasAccount
      if (empty($user)) {
        $user[$this->User->alias]['facebook_id'] = $session['uid'];
        $user[$this->User->alias][$Auth->fields['password']] = $Auth->password('disabled');
        $this->User->save($user, array('validate' => false));
      }
      //Login user if we have one
      if ($user) {
        $Auth->fields = array('username' => 'facebook_id', 'password' => $Auth->fields['password']);    		
        $Auth->login($user['User']);
      }
    }
    
    $this->Session->write('Facebook.loggedIn', true);
	}
}
