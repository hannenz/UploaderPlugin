<?php
/*
 *      uploads_controller.php
 *
 *      Copyright 2011 Johannes Braun <me@hannenz.de>
 *
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 *
 *		File: /app/plugins/uploader/controllers/uploads_controller.php
 */


class UploadsController extends UploaderAppController {

	public  $components = array(
		'Uploader.Image',
		'RequestHandler'
	);

	public $uploadData = null;

	public $helpers = array('Time', 'Number', 'Form', 'Html', 'Text');

	function beforeFilter(){
		parent::beforeFilter();
		$this->uploadData = $this->normalizeInputData();
	}

	/* Normalizes the upload form data to one single array where each
	 * element holds the uploaded file's data in key/value pairs
	 *
	 * name : normalizeInputData
	 *
	 * @return array
	 * 		Normalized array
	 */
	function normalizeInputData(){
		$uploadData = null;
		if (isset($this->request->params['form']['Upload']) && is_array($this->request->params['form']['Upload']['name'])){
			if (!empty($this->request->params['form']['Upload']['name'][0])){
				$uploadData = array();
				foreach ($this->request->params['form']['Upload'] as $key => $data){
					foreach ($this->request->params['form']['Upload']['name'] as $n => $value){
						$this->uploadData[$n][$key] = $this->request['form']['Upload'][$key][$n];
					}
				}
			}
		}
		elseif (isset($this->request->params['form']['Upload']) && is_array($this->request->params['form']['Upload'])){
			if (!empty($this->request->params['form']['Upload']['name'])){
				$uploadData = array($this->request->params['form']['Upload']);
			}
		}
		return ($uploadData);
	}

	function index($alias = null){
		$uploads = $this->Upload->find('all', array(
			'conditions' => ($alias != null) ? array('Upload.alias' => $alias) : array()
		));

		$aliases = array();
		foreach ($this->Upload->config as $alias => $cfg){
			$aliases[] = $alias;
		}

		$this->set(compact(array('uploads', 'aliases')));
	}

	function edit($id){
		if (!empty($this->data)){
			if ($this->Upload->save($this->data, false)){
				$this->Session->setFlash(__d('uploader', 'Upload has been saved', true));
				$this->redirect($this->Session->read('Uploader.edit.referer'));
			}
			$this->Session->setFlash(__d('uploader', 'Upload could not been saved', true));
		}
		else {
			$this->Session->write('Uploader.edit.referer', $this->referer());
			$this->data = $this->Upload->read(null, $id);
			Configure::load('Uploader.mime_types');
			$mimeTypes = Configure::read('MimeTypes');

			$types = array();
			foreach ($mimeTypes as $extension => $type){
				$types[$type] = sprintf('%s (%s)', $type, $extension);
			}
			asort($types);

			$this->set('types', $types);
		}
	}

	/*
	 * Add a new upload
	 *
	 * name: add
	 */
	function add($model = null, $alias = null, $foreign_key = null){
		$errors = array();

		if (!empty($this->data)){
			if ($this->uploadData){
				$data = $this->data['Upload'];
				foreach ($this->uploadData as $uploadData){
					$data = array('Upload' => array_merge($data, $uploadData));
					if (!$this->Upload->save($data)){
						$errors[$data['Upload']['name']] = $this->Upload->validationErrors;
					}
				}

				if (empty($errors)){
					$this->Session->setFlash(__d('uploader', 'All uploads have been successfully processed', true));
					$this->redirect('/uploader/uploads/index');
				}
				else {
					debug ($errors);
				}
			}
		}

		$models = array();
		$aliases = array();
		foreach ($this->Upload->config as $alias => $data){
			$aliases[$alias] = $alias;
			$models[$data['model']] = $data['model'];
		}
		$models = array_unique($models);
		$aliases = array_unique($aliases);
		$this->set(compact(array('models', 'aliases', 'errors')));

		$this->data = array(
			'Upload' => array(
				'model' => $model,
				'alias' => $alias,
				'foreign_key' => $foreign_key
			)
		);
	}


	function get_one($id, $element){
		$upload = $this->Upload->read(null, $id);
		$this->set('upload', $upload);
		$this->render('/elements/default_element', 'ajax');
		return;
	}

	/*
	 * Delete the given upload and all of its associated files
	 *
	 * name: delete
	 * @param int $id
	 * 		Id of upload to delete
	 */
	function delete($id = null){
		$n = 0;
		if ($id){
			$up = $this->Upload->read(null, $id);
			if ($this->Upload->delete($id)){
				$this->Session->setFlash(__d('uploader', 'Upload has been deleted', true));
			}
			else {
				$this->Session->setFlash(__d('uploader', 'Upload could not been deleted', true));
			}
			$n = 1;
		}
		else if (!empty($this->data['Upload'])){
			foreach ($this->data['Upload'] as $id => $delete){
				if ($delete){
					if ($this->Upload->delete($id)){
						$n++;
					}
				}
			}
		}
		$this->Session->setFlash(sprintf(__d('uploader', '%u uploads have been deleted', true), $n));
		$this->redirect($this->referer());
	}


}
