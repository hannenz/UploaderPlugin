<?php
	$cssClass = array('uploader-list', $alias);
	if ($replace){
		$cssClass[] = 'uploader-replace';
	}
	$total = count($data[$alias]);
?>
<ul class="<?php echo join(' ', $cssClass); ?>">

	<?php foreach ($data[$alias] as $n => $upload){
			$nth = array();
			if ($n == 0){
				$nth[] = 'first-child';
			}
			if ($n == $total - 1){
				$nth[] = 'last-child';
			}
			$item = $this->element($element, array('upload' => $upload, 'nth', $nth), array('plugin' => 'Uploader'));
			$checkboxLabel = __d('uploader', 'Delete this upload', true);
			echo $this->Html->tag('li', $item, array('class' => $element . ' ' . join(' ', $nth), 'id' => join('_', array($upload['model'].'.'.$alias, $upload['id']))));
		}
	?>
</ul>
