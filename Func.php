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

class Func
{
	/**
	 * Sets an array key to its array value if it's
	 * key is using its default integer then gives it
	 * a new key value.
	 * @param  [array] $array     
	 * @param  [string] $new_value 
	 * @return [array]            
	 */
	public static function fill_keys($array, $new_value)
	{
		$temp_array = [];
		foreach($array as $key => $value) {
			if(is_int($key) && is_string($value)) {
				$key 	= $value;
				$value 	= $new_value;
			}
			$temp_array[$key] = $value;
		}

		return $temp_array;
	}
}