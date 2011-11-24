<?php
class UploadsController extends UploaderAppController {

	public $helpers = array('Form', 'Html', 'Number');

/* Edit an upload
 *
 * name: edit
 * @param $id integer optional
 */
	function edit($id = null){
		if (!empty($this->data)){
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
				if ($this->Upload->save($this->data, array('validate' => false, 'callbacks' => false))){
					$mssg[] = __d('uploader', 'Upload has been saved');
					$this->Session->setFlash(join('<br>', $mssg));
					$this->redirect($this->referer());
				}
				$this->Session->setFlash(__d('uploader', 'Upload could not been saved'));
			}
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
				//~ $upload['icon'] = $this->Upload->getIcon($upload);
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
	function delete($id){
		if ($this->Upload->delete($id)){
			$this->Session->setFlash(__d('uploader', 'Upload has been deleted', true));
		}
		else {
			$this->Session->setFlash(__d('uploader', 'Upload could not been deleted', true));
		}
		$this->redirect($this->referer());
	}
}
?>
