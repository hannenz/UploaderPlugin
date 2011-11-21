<?php
//echo $this->element('upload_properties', array('plugin' => 'uploader', 'Upload' => $this->data['Upload']));
echo $this->Form->create('Upload', array('action' => 'edit'));
echo $this->Form->input('title');
echo $this->Form->input('description');
echo $this->Form->input('Upload.id', array('type' => 'hidden', 'value' => $this->data['Upload']['id']));
echo $this->Form->input('Upload.name');
echo $this->Form->input('Upload.type');
echo $this->Form->end(__d('uploader', 'Save changes', true));
?>
