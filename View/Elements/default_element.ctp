<?php
	//~ echo $this->Form->input(null, array(
		//~ 'type' => 'checkbox',
		//~ 'name' => 'data[UploadsToDelete]['.$upload['id'].']',
		//~ 'value' => $upload['id'],
		//~ 'label' => false
	//~ ));

	echo $this->Form->input(null, array(
		'type' => 'checkbox',
		'label' => false,
		'value' => $upload['id'],
		'name' => 'uploadsToDelete[]',
		'hiddenField' => false
	));

	echo $this->Html->image($upload['icon']);
?>
<dl>
<?php
	//~ echo $this->Html->tag('dt', __d('uploader', 'Pos', true));
	//~ echo $this->Html->tag('dd', '#'.$upload['pos']);
	echo $this->Html->tag('dt', __d('uploader', 'Filename', true));
	echo $this->Html->tag('dd', $upload['name']);
	echo $this->Html->tag('dt', __d('uploader', 'Size', true));
	echo $this->Html->tag('dd', $this->Number->toReadableSize($upload['size']));
	echo $this->Html->tag('dt', __d('uploader', 'Type', true));
	echo $this->Html->tag('dd', $upload['type']);
	//~ echo $this->Html->tag('dt', __d('uploader', 'Actions', true));
	//~ echo $this->Html->tag('dd', join(' | ', array($this->Html->link(__d('uploader', 'edit', true), '/uploader/uploads/edit/' . $upload['id']), $this->Html->link(__d('uploader', 'delete', true), '/uploader/uploads/delete/' . $upload['id'], array(), __d('uploader', 'Are you sure?')))));
?>
</dl>
