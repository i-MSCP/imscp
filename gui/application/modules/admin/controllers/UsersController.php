<?php

class Admin_UsersController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
	    $this->view->menuSection = 'Manage Users';
    }

    public function indexAction()
    {
        // action body
    }


}

