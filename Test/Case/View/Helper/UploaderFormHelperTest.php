<?php

App::uses('Controller', 'Controller');
App::uses('View', 'View');
App::uses('UploaderFormHelper', 'Uploader.View/Helper');

class UploaderFormHelperTest extends CakeTestCase {
	public function setUp(){
		parent::setUp();
		$Controller = new Controller();
		$View = new View($Controller);
		$this->UploaderForm = new UploaderFormHelper($View);
	}

	public function testFile(){
		$result = $this->UploaderForm->file('SomeAlias');
		$this->assertContains('file', $result);
	}
}

