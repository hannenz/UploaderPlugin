<div id="uploader-uploads">
	<h2><?php __d('uploader', 'Uploads'); ?></h2>
	<ul>
	<?php
		echo $this->Html->css('/uploader/css/uploader', null, array('inline' => false));
		foreach ($aliases as $alias){
			echo $this->Html->tag('li',
				$this->Html->link($alias, '/uploader/uploads/index/'.$alias)
			);
		}
	?>
	<li><?php echo $this->Html->link(__d('uploader', 'All uploads', true), '/uploader/uploads/index'); ?></li>
	</ul>
	<?php if (count($uploads) > 0): ?>

		<?php echo $this->Form->create('Upload', array('url' => array('plugin' => 'uploader', 'controller' => 'uploads', 'action' => 'delete', 'admin' => false)));?>
		<table>
			<thead>
				<?php echo $this->Html->tableHeaders(array(
					__d('uploader', '', true),
					__d('uploader', 'Id', true),
					__d('uploader', 'Icon', true),
					__d('uploader', 'Description', true),
					__d('uploader', 'Filename', true),
					__d('uploader', 'Files', true),
					__d('uploader', 'Size', true),
					__d('uploader', 'Mime Type', true),
					__d('uploader', 'Model#Id', true),
					__d('uploader', 'Alias', true),
					__d('uploader', 'Created', true),
					__d('uploader', 'Actions', true)
				));
				?>
			</thead>
			<tbody>
				<?php
					//debug ($uploads); die();
					foreach ($uploads as $upload){
						$li_items = '';
						foreach ($upload['Upload']['files'] as $name => $file){
							$li_items .= $this->Html->tag('li', $this->Html->link($upload['Upload']['filename'], DS . $file, array('title' => $file)), array('class' => file_exists($file) ? 'file_exists' : 'file_exists_not'));
						}
						$files_list = $this->Html->tag('ul', $li_items);

						echo $this->Html->tableCells(array(
							$this->Form->input($upload['Upload']['id'], array('type' => 'checkbox', 'value' => 1, 'label' => false, 'hiddenField' => false)),
							$upload['Upload']['id'],
							$this->Html->image($upload['Upload']['icon']),
							$this->Html->tag('h4', $upload['Upload']['title']) . $upload['Upload']['description'],
							$upload['Upload']['original_filename'],
							$files_list,
							//~ $this->Number->toReadableSize($upload['Upload']['filesize']),
							$upload['Upload']['filesize'],
							$upload['Upload']['mime_type'],

							$this->Html->tag('span', join('#', array($upload['Upload']['model'], $upload['Upload']['foreign_key']))),
							$upload['Upload']['alias'],
							strftime('%x %H:%M', strtotime($upload['Upload']['created'])),
							$this->Html->link(__d('uploader', 'edit', true), '/uploader/uploads/edit/'.$upload['Upload']['id'], array('class' => 'button'))
						), array(
							'class' => 'odd'
						), array(
							'class' => 'even'
						));
					}
				?>
			</tbody>
		</table>
		<?php echo $this->Form->button(__d('uploader', 'Delete selected uploads', true), array('id' => 'delete-uploads-button')); ?>
		<?php echo $this->Form->button(__d('uploader', 'Select all', true), array('id' => 'select-all-button', 'type' => 'button')); ?>
		<?php echo $this->Form->button(__d('uploader', 'Unselect all', true), array('id' => 'unselect-all-button', 'type' => 'button')); ?>
		<?php echo $this->Form->button(__d('uploader', 'Invert selection', true), array('id' => 'invert-selection-button', 'type' => 'button')); ?>
		<?php echo $this->Form->end(); ?>

	<?php endif; ?>
</div>
<?php echo $this->Html->script('/uploader/js/jquery', array('inline' => false)); ?>
<script>
$(document).ready(function(){
	$('#select-all-button').click(function(){
		$('input[type=checkbox]').attr('checked', 'checked');
	});
	$('#unselect-all-button').click(function(){
		$('input[type=checkbox]').removeAttr('checked');
	});
	$('#invert-selection-button').click(function(){
		$('input[type=checkbox]').each(function(){
			if ($(this).attr('checked')){
				$(this).removeAttr('checked');
			}
			else {
				$(this).attr('checked', 'checked');
			}
		});
	});
	$('#delete-uploads-button').click(function(){
		return (confirm('Are you sure?'));
	});
});
</script>
