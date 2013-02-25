<?php

/**
 * \Checkdomain\Toml is a TOML Parser for PHP
 * 
 * Copyright (C) 2013 Benjamin Paap (Checkdomain Gmbh)
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Checkdomain;

/**
 * TOML PHP Parser
 * 
 * @author @benjaminpaap (https://twitter.com/benjaminpaap)
 * @organization Checkdomain GmbH (https://www.checkdomain.de)
 */
class Toml
{
	
	protected $values = array();
	
	/**
	 * Constructor
	 * 
	 * @param string $s
	 */
	public function __construct($s = null)
	{
		if (!is_null($s)) {
			$this->parse($s);
		}
	}
	
	/**
	 * Gets the value from a specified path
	 * 
	 * @param string $path
	 */
	public function get($path)
	{
		$parts = explode('.', $path);
		$item = &$this->values;
		
		foreach ($parts as $part) {
			if (!isset($item[$part])) 
				throw new \Exception('Path not found.');
			
			$item = &$item[$part];
		}
		
		return $item;
	}
	
	/**
	 * Converts a value to the desired data type
	 * 
	 * @param string $value
	 * @return mixed
	 */
	protected function convertValue($value)
	{
		$value = trim($value);
		
		switch (true) {
			// Array values
			case substr($value, 0, 1) == '[':
				$value = $this->parseArray($value);
				break;
				
			// Boolean values
			case $value == "true":
			case $value == "false":
				$value = ($value == "true") ? true : false;
				break;
				
			// Integer values
			case preg_match('[^\d+$]', $value):
				$value = intval($value);
				break;
				
			// Float values
			case preg_match('[^\d+\.\d+$]', $value):
				$value = floatval($value);
				break;
				
			// DateTime values
			case preg_match('[^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$]', $value):
				$value = new \DateTime($value);
				break;
				
			// String values
			default:
				$value = trim($value, '"');
		}
		
		return $value;
	}
	
	/**
	 * Parses an array value
	 * 
	 * @param string $value
	 * @return array
	 */
	protected function parseArray($value)
	{
		$level = 0;
		$isString = false;
		$isEscaped = false;
		$stringIdentifier = '';
		$item = '';
		
		$result = array();
		
		// Go through each character in this line
		for ($c = 0; $c < strlen($value); $c ++) {
			$char = substr($value, $c, 1);
			if (strlen(trim($char)) == 0 && !$isString) continue;
			
			// Check if this is a string
			if ($char == '"' || $char == "'") {
				if (empty($stringIdentifier)) {
					$isString = true;
					$stringIdentifier = $char;
					$item .= $char;
					continue;
				}
					
				if ($isEscaped) {
					$item .= $char;
					continue;
				} else {
					$isString = !$isString;
				}
			}
			
			// Check the current character
			switch ($char) {
				// Opener for new array element
				case "[":
					$level ++;
					if ($level == 1) continue 2;
					break;
				// Close of current array element
				case "]":
					$level --;
					if ($level == 0) continue 2;
					break;
				// Escape character
				case "\\":
					$isEscaped = true;
					break;
				// Item seperator
				case ",":
					if (!$isString && $level == 1) {
						$result[] = $item;
						$item = '';
						continue 2;
					}
					break;
			}
			
			$item .= $char;
		}
		
		if ($level !== 0) {
			throw new \Exception(sprintf('Array not properly closed near "%1$s"', $item));
		}
		
		if (!empty($item)) {
			$result[] = $item;
		}
		
		// Convert the value for each item
		foreach ($result as &$item) {
			$item = $this->convertValue($item);
		}

		return $result;
	}
	
	/**
	 *  Parses a TOML string
	 *  
	 *  @param string $s
	 */
	public function parse($s) 
	{
		$values  = array();
		$inArray = false;
		$group   = '';
		$current = '';
		$arrayStartLine = null;
		
		// Iterate over all lines
		$lines = explode(PHP_EOL, $s);
		foreach ($lines as $linenum => $line) {
			// Trim and strip comments
			$line = trim($line);
			$line = preg_replace('[#.*]', '', $line);
			
			// Skip empty lines
			if (empty($line)) continue;
			
			// Check if this is a group name
			if (preg_match('[^\[(.*)\]$]', $line, $match) && !$inArray) {
				$group = $match[1];
				continue;
			// Check if this could be a key value pair
			} elseif (preg_match('[^[a-zA-Z0-9._\s]+=.+$]', $line)) {
				$current .= $line;
				// It's possible to span arrays over more than one line
				if (preg_match('[^.+=\s*\[]', $line) && !preg_match('[\]$]', $line)) {
					$arrayStartLine = $linenum;
					$inArray = true;
					continue;
				}
			} else {
				$current .= $line;
				// Check if the last array was properly closed
				if ($inArray && substr_count($current, '[') != substr_count($current, ']')) {
					continue;
				} else {
					$inArray = false;
				}
			}
			
			if (empty($current)) continue;

			// Get the key value pair and set the item
			$keyvalue = explode('=', $current);
			$this->setItem($values, (!empty($group) ? $group.'.' : '').trim($keyvalue[0]), trim($keyvalue[1]));
			
			$current = '';
		}
		
		$this->values = $values;
		
		return $values;
	}
	
	/**
	 * Sets a value for a specified path
	 * 
	 * @param array $array
	 * @param string $path
	 * @param mixed $value
	 */
	protected function setItem(&$array, $path, $value)
	{
		// Iterate over the path to get the correct array element
		$parts = explode('.', $path);
		$item = &$array;
		$name = array_pop($parts);
		
		foreach ($parts as $part) {
			$item = &$item[$part];
		}
		
		$item[$name] = $this->convertValue($value);
	}
	
}