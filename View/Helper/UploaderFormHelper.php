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

	function file($uploadAlias, $options = array()){
		$options = array_merge(array(
			'multiple' => false,
			'error' => array(
				'fileType' => 		__d('uploader', 'This filetype is not allowed', true),
				'maxSize' => 		__d('uploader', 'The file is too large', true),
				'noError' => 		__d('uploader', 'Upload failed', true),
				'isUploadedFile' =>	__d('uploader', 'Upload failed', true),
				'max' => 			__d('uploader', 'Maximum number of uploads exceeded', true)
			),
			'element' => 'default_element'
		), $options);

//		$Model = 'Item'; // Where can we get this from??

		$foreignKey = isset($this->request->params['pass'][0]) ? $this->request->params['pass'][0] : 0;
		$model = key((array)$this->_View->Helpers->Form->_models);
		$config = $this->_View->Helpers->Form->_models[$model]->actsAs['Uploader.Uploadable'][$uploadAlias];

		if (empty($options['label'])){
			$options['label'] = Inflector::pluralize($uploadAlias);
		}
		$uploadErrors = (isset($this->_View->viewVars['uploadErrors']))
			? $this->_View->viewVars['uploadErrors']
			: array()
		;

		$out = '';
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

		$out .= $this->Form->input('element', array('value' => $options['element'], 'type' => 'hidden', 'id' => false));
		$out .= $this->uploadList($uploadAlias, null, $options['element']);
		$out .= $this->Html->script(array(
			'/uploader/js/jquery',
			'/uploader/js/jquery.form',
			'/uploader/js/jquery.html5_upload',
			'/uploader/js/uploader'
		), array('inline' => false));
		$out = $this->Html->div(join(' ', $cssClass), $out, array('id' => join('_', array('Uploader', $model, $uploadAlias, $foreignKey))));
		return $this->output($out);
	}


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
				$list[] = $this->Html->tag('li', $item);
			}
		}
		$out = '';
		$out .= $this->Html->css('/uploader/css/uploader.css', null, array('inline' => false));
		$out .= $this->Html->tag('ul', join('', $list), array('class' => 'uploader-list '. $alias));
		return ($this->output($out));
	}
}
