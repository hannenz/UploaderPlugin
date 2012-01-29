<?php
class UploadsController extends UploaderAppController {

	public $helpers = array('Form', 'Html', 'Number');

/* Edit an upload
 *
 * name: edit
 * @param $id integer optional
 */
	function edit($id = null){
		if (!empty($this->request->data)){
			$mssg = array();
			if (!empty($_FILES['Poster']['name'])){
				$newPoster = $this->Upload->uploadPoster($this->request->data['Upload']['id'], 'files/posters');
				if (!empty($newPoster)){
					$this->Upload->deletePoster($this->data['Upload']);
					$this->request->data['Upload']['poster'] = $newPoster;
				}
				else {
					$mssg[] = __d('uploader', 'Poster upload failed!');
				}
			}
			if ($this->Upload->save($this->request->data, array('validate' => false, 'callbacks' => false))){
				$mssg[] = __d('uploader', 'Upload has been saved');
				$this->Session->setFlash(join('<br>', $mssg));
				$this->redirect('/items/edit/47'); // DELETEME!
				$this->redirect($this->referer());
			}
			$this->Session->setFlash(__d('uploader', 'Upload could not been saved'));
		}

		Configure::load('Uploader.mime_types');
		$mimeTypes = Configure::read('MimeTypes');
		sort($mimeTypes);
		$mimeTypes = array_unique($mimeTypes);
		$types = array();
		foreach ($mimeTypes as $type){
			$types[$type] = $type;
		}
		$this->set('types', $types);
		$this->request->data = $this->Upload->read(null, $id);
	}

/* Adding an upload via Ajax
 * This method may only be called by AJAX.
 * Uploads the file and assigns it to the record specified by
 * $model, $uploadAlias and $foreignKey, then renders a list item through
 * the element APP/Plugin/Uploader/Views/Elements/default_element.ctp
 * for the new upload and returns it. In case of an error, the element
 * APP/Plugin/Uploader/Views/Elements/error.ctp is rendered
 *
 * name: add
 * @param $model string
 * @param $uploadAlias string
 * @param $foreignKey string
 *
 */
	function add($model, $uploadAlias, $foreignKey, $element = null){
		if ($this->request->is('ajax')){
			$this->Upload->create();
			$data = array(
				$uploadAlias => array_merge(array(
					'model' => $model,
					'alias' => $uploadAlias,
					'foreign_key' => $foreignKey,
					'session_id' => session_id()
				), array_shift($_FILES))
			);
			$this->Upload->bindModel(array('belongsTo' => array(
				$model => array(
					'className' => $model
				)
			)));
			$this->Upload->alias = $uploadAlias;
			$this->Upload->config = $this->Upload->{$model}->actsAs['Uploader.Uploadable'];
			$this->Upload->unbindModel(array('belongsTo' => array($model)));
			if ($this->Upload->save($data)){
				$upload = $this->Upload->read(null, $this->Upload->id);
				$upload = array_shift($upload);
				$upload = $this->Upload->extend($upload);
				$this->set('upload', $upload);
				if (empty($element)){
					$element = 'default_element';
				}
				$this->render('/Elements/'.$element, 'ajax');
			}
			else {
				$this->set(array(
					'uploadErrors' => $this->Upload->validationErrors,
					'upload' => $data,
					'error' => array(
						'maxSize' => __d('uploader', 'The file is too large'),
						'fileType' => __d('uploader', 'Invalid filetype'),
						'max' => __d('uploader', 'Exceeded maximum number of uploads for this item')
					)
				));

				$this->render('/Elements/error', 'ajax');
			}
		}
	}

/* Deletes the poster for the upload with the specified id
 *
 * name: delete_poster
 * @param $id integer
 */
	function delete_poster($id){
		$this->Upload->deletePoster($id);
		$this->redirect($this->referer());
	}

/* Delete an upload
 *
 * name: delete
 * @param $id integer
 */
	function delete($id, $element = null){
		$thisUpload = $this->Upload->read(null, $id);
		if ($this->Upload->delete($id)){
			$this->Session->setFlash(__d('uploader', 'Upload has been deleted', true));
		}
		else {
			$this->Session->setFlash(__d('uploader', 'Upload could not been deleted', true));
		}
		if ($this->request->is('ajax')){
			App::uses($thisUpload['Upload']['model'], 'Model');
			$Model = new $thisUpload['Upload']['model'];
			$data = $Model->read(null, $thisUpload['Upload']['foreign_key']);

			$this->set(array(
				'data' => $data,
				'replace' => false,
				'alias' => $thisUpload['Upload']['alias'],
				'element' => $element ? $element : 'default_element'
			));
			$this->render('/Elements/uploader_list', 'ajax');
			return;
		}
		else {
			$this->redirect($this->referer());
		}
	}

/* Provides a download for the specified upload file
 *
 * name: unbekannt
 * @param $id
 * 		id of the upload
 * @param $fileAlias
 * 		fileAlias name for the file to download
 */
/*
 *
 * name: download
 * @param
 * @return
 *
 */
	function download($id, $fileAlias = null){
		$upload = array_shift($this->Upload->read(null, $id));
		$model = $upload['model'];
		$alias = $upload['alias'];

		$this->Upload->bindModel(array('belongsTo' => array(
			$model => array(
				'className' => $model
			)
		)));
		$this->Upload->alias = $alias;
		$this->Upload->config = $this->Upload->{$model}->actsAs['Uploader.Uploadable'];
		$this->Upload->unbindModel(array('belongsTo' => array($model)));

		$upload = $this->Upload->extend($upload);

		if (!empty($upload['files']) && is_array($upload['files'])){
			if ($fileAlias === null || !isset($upload['files'][$fileAlias])){
				$fileAlias = key($upload['files']);
			}

			$this->response->type($upload['type']);
			$this->response->download($upload['name']);
			$this->response->body(file_get_contents(WWW_ROOT . $upload['files'][$fileAlias]));
			$this->response->send();
		}
		else {
			$this->redirect($this->referer());
		}
	}

/* Reorders the uploads according to an ajax call from a jQuery UI sortable.
 *
 * name: reorder
 *
 */
	function reorder($element = null){
		if ($this->request->is('ajax')){
			$data = array_shift($this->request->data);
			foreach ($data as $pos => $id){
				$this->Upload->id = $id;
				$this->Upload->saveField('pos', $pos + 1);
			}


			$oneUpload = $this->Upload->read(null, $data[0]);

			App::uses($oneUpload['Upload']['model'], 'Model');
			$Model = new $oneUpload['Upload']['model'];
			$data = $Model->read(null, $oneUpload['Upload']['foreign_key']);

			$this->set(array(
				'data' => $data,
				'replace' => false,
				'alias' => $oneUpload['Upload']['alias'],
				'element' => $element ? $element : 'default_element'
			));
			$this->render('/Elements/uploader_list', 'ajax');
			return;
		}
		else {
			$this->redirect($this->referer());
		}
	}

	function move($id, $dir){
		if ($this->Upload->move($id, $dir)){
			$this->Session->setFlash(__d('uploader', 'Upload has been moved'));
		}
		else {
			$this->Session->setFlash(__d('uploader', 'Upload could not been moved'));
		}
		$this->redirect($this->referer());
	}
}
?>
