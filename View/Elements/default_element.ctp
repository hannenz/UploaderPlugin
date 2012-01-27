<?php
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
<ul class="uploader-list-actions">
	<li><?php echo $this->Html->link(__('edit'), array('plugin' => 'uploader', 'controller' => 'uploads', 'action' => 'edit', $upload['id']), array('title' => __d('uploader', 'Edit this upload'), 'class' => 'uploader-list-edit')); ?></li>
	<li><?php echo $this->Html->link(__('up'), array('plugin' => 'uploader', 'controller' => 'uploads', 'action' => 'move', $upload['id'], -1), array('title' => __d('uploader', 'Move up'), 'class' => 'uploader-list-up')); ?></li>
	<li><?php echo $this->Html->link(__('down'), array('plugin' => 'uploader', 'controller' => 'uploads', 'action' => 'move', $upload['id'], 1), array('title' => __d('uploader', 'Move down'), 'class' => 'uploader-list-down')); ?></li>
	<li><?php echo $this->Html->link(__('delete'), array('plugin' => 'uploader', 'controller' => 'uploads', 'action' => 'delete', $upload['id']), array('title' => __d('uploader', 'Delete this upload'), 'class' => 'uploader-list-delete')); ?></li>
</ul>
