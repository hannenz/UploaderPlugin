<?php
class UploadsController extends UploaderAppController {

	public $helpers = array('Form', 'Html');

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

	function delete_poster($id){
		$this->Upload->deletePoster($id);
		$this->redirect($this->referer());
	}

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
