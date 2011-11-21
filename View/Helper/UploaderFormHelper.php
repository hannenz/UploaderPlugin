<?php
/*
 * uploader_form.php
 *
 * Copyright 2011 Johannes Braun <me@hannenz.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 *
 */

class UploaderFormHelper extends AppHelper {

	public $helpers = array('Html', 'Form', 'Number', 'Text', 'Session');

	/* The current upload's Alias */
	var $alias = null;

	/* The current upload's (associated) model */
	var $model = null;

	/* The current upload's associated record id */
	var $id = null;

	/* The UploaderPlugin configuration */
	var $config = null;

	/* We need an instance of the Upload Class */
	var $Upload = null;

	/* An instance of the current upload's model */
	var $Model = null;



	function uploadList($alias, $data = null, $element = null){

		if ($data === null){
			$data = $this->request->data;
		}
		if ($element === null){
			$element = 'default_element';
		}

		$list = array();
		if (isset($data[$alias])){
			foreach ($data[$alias] as $upload){
				$item = $this->_View->element($element, array('upload' => $upload), array('plugin' => 'Uploader'));
				$checkboxLabel = __d('uploader', 'Delete this upload', true);
				$list[] = $this->Html->tag('li', $this->Form->input('uploadDelete[]', array('type' => 'checkbox', 'label' => false)) . $item);
			}
		}
		$out = '';
		$out .= $this->Html->css('/uploader/css/uploader.css', null, array('inline' => false));
		$out .= $this->Html->tag('ul', join('', $list), array('class' => 'uploader-list '. $alias));
		return ($this->output($out));
	}


/* Create an upload form
 *
 * name: create
 * @param string $alias
 * 		The upload alias for which to create the upload form
 * @param $id
 * 		The record id for which to create the upload form
 * 		(0 = No record, save uploads as "pending")
 * @param mixed $title
 * 		If $title is a string, the form will have a H4 tag with this
 * 		title, if $title is an array the key is used as the wrapper tag,
 * 		false or null results in no title at all
 * @param array $options
 * 		Each key in the oprions array will output the form element
 * 		with this name, the value is passed to the according method as
 * 		options array to that.
 *
 * @return
 * 		Markup
 *
 */
	function create($alias, $id, $title = false, $options = array()){
		$options = array_merge(array(
			'flashMessage' => array(),
			'errors' => true,
			'queue' => false,
			'progress' => true,
			'uploadField' => array(
				'label' => __d('uploader', 'Upload files', true),
				'label' => sprintf(__d('uploader', 'Upload %s', true), $alias),
				'redirect' => '',
			),
			'uploadList' => true
		), $options);


		Configure::load('Uploader.uploader_config');
		$this->config = Configure::read('UploaderConfig');

		/* Import Upload model */
		//~ App::import('Model', 'Upload');
		//~ $this->Upload = new Upload();



		$this->alias = $alias;
		// We need value 0, not null or false or whatever...
		$this->id = $id ? $id : 0;

		if (!isset($this->config[$alias])){
			$out = 'UploadAlias \'' . $alias . '\' is not configured in /Plugin/Uploader/Config/uploader_config.php';
			return ($this->output($out));
		}

		$this->model = $this->config[$alias]['model'];

		//~ if ($this->Model === null){
			//~ App::import('Model', $this->model);
			//~ $this->Model = new $this->model();
		//~ }

		$out = '';

		/* We need some scripts and css */
		//~ $out = $this->Html->script(array(
				//~ '/uploader/js/jquery',
				//~ '/uploader/js/jquery.html5_upload',
				//~ '/uploader/js/uploader'
			//~ ), array('inline' => false)
		//~ );
		$out .= $this->Html->css('/uploader/css/uploader.css', null, array('inline' => false));

		/* Output title if desired */
		if ($title){
			if (is_array($title)){
				$tag = key($title);
				$text = $title[$tag];
			}
			else {
				$tag = 'h4';
				$text = $title;
			}
			$out .= $this->Html->tag($tag, $text);
		}

		/* Loop through options and output the desired elements */
		foreach ($options as $name => $opts){
			if ($opts !== false){
				if (!is_array($opts)){
					$opts = array();
				}
				if (method_exists($this, $name)){
					$out .= $this->{$name}($opts);
				}
			}
		}

		/* Wrap */
		$out = $this->Html->div('uploader', $out, array('id' => sprintf('uploader%s', $this->alias)));

		/* Reset instances & variables */
		$this->Model = $this->model = $this->alias = $this->id = null;

		return ($this->output($out));
	}

/* Output flash message section
 *
 * name: flashMessage
 */
	function flashMessage($options = array()){
		$options = array_merge(array(), $options);
		return ($this->Session->flash('uploader' . $this->alias));
	}

/* Output errors section
 *
 * name: errors
 */
	function errors($options = array()){
		$options = array_merge(array(), $options);
		$out = $this->Html->tag('ul', '', array('class' => 'uploader-errors', 'id' => 'UploaderErrors' . $this->alias));
		return ($out);
	}

/* Output the upload field along with the neccessary hidden input fields
 *
 * name: uploadField
 * @param array $options
 * 		$options['inputLabel']: Label for the input field
 * 		$options['submitLabel'] : Label for the submit button
 * 		$options['redirect'] : redirect after upload (non-js only)
 *
 * @return string
 * 		Markup
 */
	function uploadField($options = array()){
		$options = array_merge(array(
			'inputLabel' => __d('uploader', 'Upload files', true),
			'submitLabel' => sprintf(__d('uploader', 'Upload %s', true), $this->alias),
			'redirect' => ''
		), $options);

		$out = '';

		/* Output the form */
		//~ $out .= $this->Form->create('Upload', array(
			//~ 'url' => array(
				//~ 'plugin' => 'uploader',
				//~ 'controller' => 'uploads',
				//~ 'action' => 'add',
				//~ 'admin' => false,
			//~ ),
			//~ 'type' => 'file',
			//~ 'id' => false,
		//~ ));

		/* Hidden fields for meta data */
		$out .= $this->Form->input($this->alias.'.alias', array(
			'type' => 'hidden',
			'value' => $this->alias,
			'id' => false
		));
		$out .= $this->Form->input($this->alias.'.foreign_key', array(
			'type' => 'hidden',
			'value' => $this->id,
			'id' => false
		));
		$out .= $this->Form->input($this->alias.'.model', array(
			'type' => 'hidden',
			'value' => $this->config[$this->alias]['model'],
			'id' => false
		));
		//~ $out .= $this->Form->input('confirm_message', array(
			//~ 'type' => 'hidden',
			//~ 'value' => __d('uploader', 'You are about to delete %n_files% %noun%. Are you sure?', true),
			//~ 'id' => false
		//~ ));
		//~ $out .= $this->Form->input('singular', array(
			//~ 'type' => 'hidden',
			//~ 'value' => __d('uploader', 'upload', true),
			//~ 'id' => false
		//~ ));
		//~ $out .= $this->Form->input('plural', array(
			//~ 'type' => 'hidden',
			//~ 'value' => __d('uploader', 'uploads', true),
			//~ 'id' => false
		//~ ));

		$multiple = false;
		$upload_id = null;
		if (isset($this->Model->hasMany[$this->alias])){
			$multiple = true;
		}
		else if (isset($this->Model->hasOne[$this->alias])){
			if ($this->id > 0){
				$record = $this->Model->read(null, $this->id);
				$upload_id = $record[$this->alias]['id'];
			}
		}
		$input_opts = array(
			'id' => false,
			'class' => 'uploader-upload-field',
			'name' => $multiple ? $this->alias.'[]' : $this->alias,
		);
		if ($multiple){
			$input_opts['multiple'] = 'multiple';
		}
		$out .= $this->Form->input($this->alias.'.id', array(
			'class' => 'UploadId',
			'type' => 'hidden',
			'value' => $upload_id,
			'id' => false
		));
		$out .= $this->Form->file($this->alias.'.file', $input_opts);
		//~ $out .= $this->Form->submit($options['submitLabel'], array('class' => 'uploader-submit-button'));
		//~ $out .= $this->Form->end();
		return ($out);
	}

/* Outputs an info box which tells the user about the requirements for
 * uploading files (max. filesize and allowed file types)
 *
 * name: infoBox
 * @param array $options
 * 		$options['size'] => boolean, show max. filesize
 * 		$options['types'] => boolean, show allowed types
 */
	function infoBox($options = array()){
		$options = array_merge(array(
			'size' => true,
			'types' => true
		), $options);

		$out = '';

		foreach ($options as $option => $value){
			switch ($option){
				case 'size':
					$max = min($this->return_bytes(ini_get('post_max_size')), $this->return_bytes(ini_get('upload_max_filesize')));
					if (!empty($this->config[$this->alias]['max_filesize']) && ($this->config[$this->alias]['max_filesize'] > 0 && $this->config[$this->alias]['max_filesize'] < $max)){
						$max = $this->config[$this->alias]['max_filesize'];
					}
					$out .= $this->Html->tag('dt', $value === true ? __d('uploader', 'Maximum filesize', true) : $value);
					$out .= $this->Html->tag('dd',
						$this->Number->toReadableSize($max) . '(' . $max . ')'
					);
					break;
				case 'types':
					$out .= $this->Html->tag('dt', $value === true ? __d('uploader', 'Allowed filetypes', true) : $value);
					$out .= empty($this->config[$this->alias]['allow']) ? __d('uploader', 'Any', true) : $this->Html->tag('dd', $this->Text->toList($this->config[$this->alias]['allow']));
					break;
			}
		}
		$out = $this->Html->tag('dl', $out, array('class' => 'uploader-infobox', 'id' => 'UploaderInfoBox' . $this->alias));
		return ($out);
	}

/* Output a queue displaying the uploading files and indivual progress
 *
 * name: queue
 */
	function queue($options = array()){
		$options = array_merge(array(), $options);
		$out = $this->Html->tag('ul', '', array('id' => 'uploaderQueue'.$this->alias, 'class' => 'uploader-queue'));
		return ($out);
	}

/* Output the list of already available uploads. This list will be
 * updated by javascript during upload actions
 *
 * name: uploadList
 * @param array $options
 * 		$options['element'] string
 * 			Specify the element to be used for rendering the individual
 * 			list items. Defaults t
 * 			'/app/plugins/uploader/views/elements/default_element'
 * 			If specified give path relative to '/app/views/elements'
 * 		$options['delete'] boolean
 * 			Whether to output as form allowing the user to delete
 * 			individual uploads (witch checkboxes prepending each list
 * 			item and a 'Delete selected' button
 */
	function olDuploadList($uploads = array(), $options = array()){
		$options = array_merge(array(
			'element' => 'default_element',
			'delete' => true
		), $options);

		$list = '';

		$conditions = array(
			'Upload.alias' => $this->alias,
			'Upload.model' => $this->model,
			'Upload.foreign_key' => $this->id
		);
		if ($this->id == 0){
			$conditions['Upload.session_id'] = session_id();
		}

		//~ $uploads = $this->Upload->find('all', array('conditions' => $conditions));

		/** Hmmm, we could get the uploads from $this->data, but we want to have pending uploads included as well.,..
		 * Think about it!*/
		//~ if (!empty($this->data[$this->alias])){
			//~ $uploads = $this->data[$this->alias];
		//~ }

		$data = array();
		if ($options['element'] == 'default_element'){
			$data['plugin'] = 'uploader';
		}
		$View = &ClassRegistry::getObject('view');
		foreach ($uploads as $upload){
			$data['upload'] = $upload;
			$list .= $this->Html->tag('li', ($options['delete'] ? '<input type="checkbox" name="data[UploadDelete][' . $this->alias . '][]" value="'. $upload['Upload']['id'] .'" />' : '') . $View->element($options['element'], $data));
		}

		$out = '';
		$class = 'uploader-list';
		//~ if ($options['delete']){
			//~ $out .= $this->Form->create(null, array(
				//~ 'url' => array(
					//~ 'plugin' => 'uploader',
					//~ 'controller' => 'uploads',
					//~ 'action' => 'delete_many',
					//~ 'admin' => false,
				//~ ),
				//~ 'id' => false
			//~ ));
			//~ $class .= ' uploader-can-delete';
		//~ }
		$out .= $this->Html->tag('ul', $list, array('id' => 'uploaderList'.$this->alias, 'class' => $class));

		$out .= sprintf('<input type="hidden" class="uploadElement" value="%s" />', $options['element']);


		//~ if ($options['delete']){
			//~ $out .= $this->Form->end(__d('uploader', 'Delete selected', true));
		//~ }
		return ($this->output($out));
	}

/* Output a progress area displaying the overall upload progress
 * (File x of y with progress bar)
 * Js only
 *
 * name: progress
 */

	function progress($options = array()){
		$options = array_merge(array(), $options);
		$out = '';

		$out .= $this->Html->div('uploader-progress',
			join(' ', array(
				$this->Html->tag('span', '', array('class' => 'uploader-progress-numbers')),
				$this->Html->tag('span', '', array('class' => 'uploader-progress-filename')),
				$this->Html->div('uploader-progress-progressbar', $this->Html->div('uploader-progressbar', ''))
			)), array('style' => 'display:none')
		);

		return ($out);
	}

/* Converts Filesize strings from php.ini to integer
 * (e.g. conbverts strings like '2M', 100K' etc)
 *
 * name: return_bytes
 * @param string $str
 * 		The string to convert
 *
 * @return int
 * 		Bytes as integer
 */
	private function return_bytes($str){
		$val = trim($str);
		$last = strtolower($val[strlen($val) - 1]);
		switch ($last){
			case 'g':	$val *= 1024;
			case 'm':	$val *= 1024;
			case 'k':	$val *= 1024;
		}
		return ($val);
	}
}
?>
