<?php
class UploadsController extends UploaderAppController {
	
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
