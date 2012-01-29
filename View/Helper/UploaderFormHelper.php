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

	public $config;

/* Output a file input element
 *
 * name: file
 * @param $uploadAlias
 * 		The alias of the upload
 * @param $options
 * 		Options:
 * 			multiple: multiple file upload field
 * 			list: whether to display a list of uploads
 * 			element: element used to render the list items
 * 			error: Array of error messages
 */
	function file($uploadAlias, $options = array()){
		$options = array_merge(array(
			'multiple' => false,
			'error' => array(
				'fileType' => 		__d('uploader', 'This filetype is not allowed', true),
				'maxSize' => 		__d('uploader', 'The file is too large', true),
				'noError' => 		__d('uploader', 'Upload failed', true),
				'isUploadedFile' =>	__d('uploader', 'Upload failed', true),
				'max' => 			__d('uploader', 'Maximum number of uploads exceeded', true),
			),
			'element' => 'default_element',
			'list' => true,
			'showMaxSize' => true,
			'showAllowedTypes' => true
		), $options);

		// Read Uploader configuration
		$this->config = Configure::read('Uploader.settings.'.$uploadAlias);

		// Read the foreign key from request data
		$model = key((array)$this->_View->Helpers->Form->_models);

		$foreignKey = isset($this->request->data[$model]['id']) ? $this->request->data[$model]['id'] : 0;

		// Generate label text
		if (empty($options['label'])){
			$options['label'] = Inflector::pluralize($uploadAlias);
		}

		$uploadErrors = $this->_View->Helpers->Form->_models[$model]->uploadErrors;

		$out = '';

		if ($options['showMaxSize']){
			$out .= $this->Html->div('uploader-max-filesize', sprintf('%s %s',
				is_string($options['showMaxSize']) ? $options['showMaxSize'] : __d('uploader', 'Max filsize:'),
				$this->Number->toReadableSize($this->maxUploadSize($uploadAlias))
			));
		}
		if ($options['showAllowedTypes']){
			$at = Configure::read('Uploader.settings.'.$uploadAlias.'.allow');
			$out .= $this->Html->div('uploader-allowed-filetypes', sprintf('%s %s',
				is_string($options['showAllowedTypes']) ? $options['showAllowedTypes'] : __d('uploader', 'Allowed filetypes:'),
				empty($at) ? __d('uploader', 'all') : join(', ', $at)
			));
		}


		$cssClass = array('input', 'file', 'uploader');
		if (!empty($uploadErrors[$uploadAlias])){
			$cssClass[] = 'error';
		}

		$inputName = $uploadAlias;
		$inputOpts = array(
			'id' => false
		);
		if ($options['multiple']){
			$inputName.='[]';
			$inputOpts['multiple'] = 'multiple';
		}
		$inputOpts['name'] = $inputName;

		$out .= $this->Html->tag('label', $options['label'], array('for' => $inputName));
		$out .= $this->Form->file(null, $inputOpts);

		if (!empty($uploadErrors[$uploadAlias])){
			$list = array();
			foreach ($uploadErrors[$uploadAlias] as $file => $errors){
				foreach ($errors as $field => $rules){
					foreach ($rules as $rule){
						$errorMessage = !empty($options['error'][$rule]) ? $options['error'][$rule] : __d('uploader', 'Upload failed', true);
						$list[] = $this->Html->tag('li', sprintf('%s: %s', $file, $errorMessage), array('class' => 'uploader error-message'));
					}
				}
			}
			$out .= $this->Html->tag('ul', join('', $list), array('class' => 'uploader errors'));
		}

		$out .= $this->Form->input('element', array('value' => $options['element'], 'type' => 'hidden', 'class' => 'uploader-element', 'id' => false));
		if ($options['list']){
			//$out .= $this->uploadList($uploadAlias, null, $options['element']);
			$out .= $this->_View->element(
				'uploader_list',
				array(
					'alias' => $uploadAlias,
					'data' => $this->request->data,
					'element' => $options['element'],
					'replace' => $this->config['max'] == 1
				),
				array(
					'plugin' => 'Uploader'
				)
			);
		}
		$out = $this->Html->div(join(' ', $cssClass), $out, array('id' => join('_', array('Uploader', $model, $uploadAlias, $foreignKey))));
		return $this->output($out);
	}

/* name: maxUploadSize
 *
 * Returns the effective ma xupload filesize, taking in regard
 * values from php.ini and the max upload sitze specified
 * in the uploadAlias' configuration
 *
 * @param $uploadAlias: The uploadAlias
 * @return: Max upload filesize for this uploadAlias' files in bytes
 */
	function maxUploadSize($uploadAlias){

		$values = array(
			$this->toBytes(ini_get('upload_max_filesize')),
			$this->toBytes(ini_get('post_max_size'))
		);

		$m = Configure::read('Uploader.settings.'.$uploadAlias.'.maxSize');
		if (!empty($m)){
			$values[] = $m;
		}

		return min($values);
	}

/* name toBytes
 *
 * Converts strings (e.g. from php.ini) into number of bytes
 *
 * @param $str string: The string to convert
 * @return int: namber of bytes
 */
	private function toBytes($str){
		$n = 0;
		$units = array(
			'k' => 1024,
			'm' => 1048576,
			'g' => 1073741824,
		);
		$str = trim($str);
		for ($i = 0; $i < strlen($str) && $str[$i] >= '0' && $str[$i] <= '9' ; $i++){
			$n = $n * 10 + (int)$str[$i];
		}
		while ($i < strlen($str)){
			$u = strtolower($str[$i]);
			if (isset($units[$u])){
				$n *= $units[$u];
				break;
			}
			$i++;
		}
		return (int)$n;
	}


/* Render a list of already uploaded files
 *
 * name: uploadList
 * @param $alias
 * 		UploadAlias
 * @param $data optional
 * 		Array of uploads
 * @param $element
 * 		Name of the element to be used as list item.
 * 		Elements must be in the plugin's element path (APP/Plugin/Uploader/View/Elements/)
 * @return
 *
 */
	//~ function uploadList($alias, $data = null, $element = null){
//~
		//~ if ($data === null){
			//~ $data = $this->request->data;
		//~ }
		//~ if ($element === null){
			//~ $element = 'default_element';
		//~ }
//~
		//~ $list = array();
		//~ if (isset($data[$alias])){
			//~ $total = count($data[$alias]);
			//~ foreach ($data[$alias] as $n => $upload){
				//~ $nth = array();
				//~ if ($n == 0){
					//~ $nth[] = 'first-child';
				//~ }
				//~ if ($n == $total - 1){
					//~ $nth[] = 'last-child';
				//~ }
				//~ $item = $this->_View->element($element, array('upload' => $upload, 'nth', $nth), array('plugin' => 'Uploader'));
				//~ $checkboxLabel = __d('uploader', 'Delete this upload', true);
				//~ $list[] = $this->Html->tag('li', $item, array('class' => join(' ', $nth), 'id' => join('_', array($upload['model'].'.'.$alias, $upload['id']))));
			//~ }
		//~ }
		//~ $out = '';
		//~ $out .= $this->Html->css('/uploader/css/uploader.css', null, array('inline' => false));
		//~ $cssClass = array('uploader-list', $alias);
		//~ if ($this->config['max'] == 1){
			//~ $cssClass[] = 'uploader-replace';
		//~ }
		//~ $out .= $this->Html->tag('ul', join('', $list), array('class' => join(' ', $cssClass)));
		//~ return ($this->output($out));
	//~ }
}
