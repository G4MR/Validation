<?php

/**
 * Selfish 
 *
 * Copyright (c) 2014, Lamonte Harris <lamonte.org>
 *
 * The Don't Ask Me About It License
 * 
 * Copying and distribution of this file, with or without modification, 
 * are permitted in any medium provided:
 * you do not contact the author about the file or any problems 
 * you are having with the file.
 */

namespace Selfish;

class Validation 
{
	protected $input 			= [];
	protected $errors 			= [];
	protected $messages 		= [];
	protected $rules_stop 		= [];
	protected $fields_stop 		= [];
	protected $rules_array 		= [];
	protected static $callbacks = [];

	protected $rule_messages 	= [
		'min' 		=> '{field} should be greater than or equal to $0',
		'max' 		=> '{field} should be equal or less than $0',
		'email'		=> '{field} must be a valid email',
		'required' 	=> '{field} is required',
	];

	public function __construct(array $input_data, $rule_files = null)
	{
		$this->input = $input_data;

		//load/extend rule messages
		if(!empty($rule_files)) {
			$this->loadRuleMessages($rule_files);
		}

		//setup default callbacks
		$this->defaultCallbacks();

	}

	public function debug() {
		var_dump($this->input, $this->rules_array, $this->messages, $this->fields_stop, $this->rules_stop, static::$callbacks);
	}

	/**
	 * Check input validation
	 * @return boolean 
	 */
	public function is_valid()
	{
		//loop through rules & check validation
		foreach($this->rules_array as $field => $rules) {
			if(!isset($this->input[$field])) continue;

			//loop through rules
			foreach($rules as $rule => $parameters) {
				if(isset(static::$callbacks[$rule]) && (static::$callbacks[$rule] instanceof \Closure)) {
					$callback_params 	= array_merge([$field, $this->input[$field]], $parameters);
					$callback 			= call_user_func_array(static::$callbacks[$rule], $callback_params);
					if(!$callback) {
						$this->error($field, $rule, $parameters);
					}
				}

				//check if rule stop exists & stop checking for rules following this one
				if(isset($this->rules_stop[$field]) && in_array($rule, $this->rules_stop[$field]) && !empty($this->errors)) {
					break;
				}
			}

			//check if the field stop exists & stop checking for other field validation rules
			if(in_array($field, $this->fields_stop) && !empty($this->errors)) {
				break;
			}
		}

		return empty($this->errors) ? true : false;
	}

	/**
	 * Get errors array
	 * @return [array] 
	 */
	public function errors()
	{
		return $this->errors;
	}

	/**
	 * Set error messages for field based on custom rule messages & default
	 * @param  [string] $field  
	 * @param  [string] $rule   
	 * @param  [array] $params 
	 * @return [array]         
	 */
	public function error($field, $rule, $params)
	{
		//check if user error was created by the user
		if(isset($this->messages[$field][$rule])) {
			$error_message = $this->errorParse($this->messages[$field][$rule], $field, $rule, $params);
			return $this->errors[] = $error_message;
		}

		if(isset($this->rule_messages[$rule])) {
			$error_message = $this->errorParse($this->rule_messages[$rule], $field, $rule, $params);
			return $this->errors[] = $error_message;
		}
	}

	/**
	 * Parse Error message
	 * @param  [string] $rule_message 
	 * @param  [string] $field        
	 * @param  [string] $rule         
	 * @param  [array] $params       
	 * @return [string]               
	 */
	public function errorParse($rule_message, $field, $rule, $params)
	{
		$error_message = str_replace('{field}', $field, $rule_message);

		//handle parameters in error message using $0 notation 0 = position in array
		if(preg_match_all('/\$(\d+)/', $error_message, $matches) && isset($matches[1]) && !empty($matches[1])) {
			foreach($matches[1] as $p) {
				if(isset($params[$p])) {
					$error_message = str_replace('$' . $p, $params[$p], $error_message);
				}
			}
		}
		return $error_message;
	}

	/**
	 * Load rules from file and merge/overwrite default rules
	 * @param  [string] $rules_file path to return array file
	 * @return [void]             
	 */
	public function loadRuleMessages($rules_file)
	{
		if(file_exists($rules_file)) {
			$rules = include $rules_file;
			$this->rule_messages = array_merge($this->rule_messages, $rules);
		}
	}

	/**
	 * Set rules for an array of fields
	 * @param [array] $fields   
	 * @param [array] $messages [array of rule error messages]
	 */
	public function setRules($fields, $messages = [])
	{
		foreach($fields as $field => $rules) {
			$this->setRule($field, $rules, $messages);
		}
		return $this;
	}

	/**
	 * Set individual field rules
	 * @param [string] $field    
	 * @param [string] $rules    rules with | notation to split rules
	 * @param [array] $messages  array of error messages for custom rules
	 */
	public function setRule($field, $rules, $messages = [])
	{
		$this->setMessages($messages);
		
		$field 	= trim($field);

		//handle rules
		$rules 	= trim($rules);
		$rules 	= @explode('|', $rules);

		if(!is_array($rules) && empty($rules)) {
			return;
		}

		foreach($rules as $rule) {
			
			//handle individual rules
			$rule = trim($rule);
			if(empty($rule)) continue;

			$rule_name 		= null;
			$rule_params 	= [];

			$rule_data 		= @explode(':', $rule);
			if(!is_array($rule_data) && !empty($rule_data)) {
				$rule_name = $rule_data;
			}

			if(is_array($rule_data) && count($rule_data) == 1) {
				$rule_name = $rule_data[0];
			}

			if(is_array($rule_data) && count($rule_data) > 1) {
				$params 	= trim($rule_data[1]);
				$rule_name 	= $rule_data[0];

				$params 	= @explode(',', $params);
				foreach($params as $param) {
					$param 	= trim($param);
					if(!empty($param)) {
						$param = preg_match('/^[0-9]+/', $param) ? (int) $param : $param;
						$rule_params[] = $param;
					}
				}
			}

			$this->rules_array[$field][$rule_name] = $rule_params;
		}
		return $this;
	}

	/**
	 * Store array of custom callback rule error messages
	 * @param [array] $messages [description]
	 */
	public function setMessages($messages) {
		if(!empty($messages) && is_array($messages)) {
			$this->messages = $messages;
		}
		return $this;
	}

	/**
	 * Stop displaying rule errors when you hit the first matched
	 * rule error in order. If there's 3 stop rules then it'll
	 * keep stopping in the order of the rules matched for errors
	 * @param  [string] $field [field name]
	 * @param  string  $rules 
	 */
	public function stopRule($field, $rules)
	{
		$rules = @explode('|', $rules);
		foreach($rules as $rule) {
			$this->rules_stop[$field][] = $rule;
		}
		return $this;
	}

	public function stopRules($fields)
	{
		foreach($fields as $field => $rules) {
			$this->stopRule($field, $rules);
		}
		return $this;
	}

	/**
	 * Same as stop rules except it stops processing rule
	 * errors following this one until all rules are valid
	 * @param  array  $fields 
	 */
	public function stopFields(array $fields)
	{
		foreach($fields as $field) {
			$this->stopField($field);
		}
		return $this;
	}

	public function stopField($field) {
		$this->fields_stop[] = $field;
		return $this;
	}

	/**
	 * Register validation callbacks
	 * @param  [string] $callback_name callback name
	 * @param  Closure 	$callback      callback closure
	 * @return [void]                 
	 */
	public function registerCallback($callback_name, \Closure $callback)
	{
		static::makeCallback($callback_name, $callback);
	}

	public static function makeCallback($callback_name, \Closure $callback)
	{
		if(!is_string($callback_name)) {
			throw new \Exception("Validation Callback should be a string");
		}

		static::$callbacks[$callback_name] = $callback;
	}

	/**
	 * Setup default rules callbacks which are overwriteable
	 * @return [type] [description]
	 */
	public function defaultCallbacks()
	{
		//default required field callback
		$this->registerCallback('required', function($field, $value) {
			return !empty($value);
		});

		//default minimum field callback
		$this->registerCallback('min', function($field, $value, $min_value) {
			print_r(func_get_args());
			if(is_string($value)) {
				return strlen($value) >= $min_value;
			}
			if(is_array($value)) {
				return count($value) >= $min_value;
			}
			return $value >= $min_value;
		});

		//default maximum field callback
		$this->registerCallback('max', function($field, $value, $max_value) {
			if(is_string($value)) {
				return strlen($value) <= $max_value;
			}
			if(is_array($value)) {
				return count($value) <= $max_value;
			}
			return $value <= $max_value;
		});

		$this->registerCallback('email', function($field, $value) {
			return filter_var($value, FILTER_VALIDATE_EMAIL);
		});
	}
}