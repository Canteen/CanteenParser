<?php

/**
*  @module Canteen\Parser
*/
namespace Canteen\Parser
{
	use \Exception;
	
	/**
	*  Parser specific errors. Located in the namespace __Canteen\Errors__.
	*  
	*  @class ParserError
	*  @extends Exception
	*  @constructor
	*  @param {int} code The code number
	*  @param {String|Array} [data=''] Any extra data associated with error
	*  @param {Dictionary} [messages=null] The collection of messages to lookup
	*/
	class ParserError extends Exception
	{				
		/** 
		*  The parse template can't be found 
		*  @property {int} TEMPLATE_NOT_FOUND
		*  @static
		*  @final
		*/
		const TEMPLATE_NOT_FOUND = 110;	
		
		/** 
		*  There was a problem decoding the JSON 
		*  @property {int} JSON_DECODE
		*  @static
		*  @final
		*/
		const JSON_DECODE = 115;
		
		/** 
		*  Duplicate named autoload template 
		*  @property {int} AUTOLOAD_TEMPLATE
		*  @static
		*  @final
		*/
		const AUTOLOAD_TEMPLATE = 119;
		
		/** 
		*  The template alias is wrong
		*  @property {int} TEMPLATE_UNKNOWN
		*  @static
		*  @final
		*/
		const TEMPLATE_UNKNOWN = 121;

		/** 
		*  The parse substitution is invalid
		*  @property {int} PARSE_ARRAY
		*  @static
		*  @final
		*/
		const PARSE_ARRAY = 125;
		
		/**
		*  The collection of messages
		*  @property {Array} messages
		*  @private
		*  @static
		*  @final
		*/
		private static $messages = [
			self::JSON_DECODE => 'Failure decoding JSON',
			self::TEMPLATE_NOT_FOUND => 'Cannot load template file',
			self::TEMPLATE_UNKNOWN => 'Template not registered',
			self::AUTOLOAD_TEMPLATE => 'Template has already been loaded',
			self::PARSE_ARRAY => 'The parse substitution value \'%s\' cannot be an array \'%s\''
		];
		
		/** 
		*  The label for an error that is unknown or unfound in messages 
		*  @property {int} UNKNOWN
		*  @static
		*  @final
		*/
		const UNKNOWN = 'Unknown error';
		
		/**
		*  Create the Canteen error
		*/
		public function __construct($code, $data='', $messages=null)
		{
			$messages = ifsetor($messages, self::$messages);
			$message =  ifsetor($messages[$code], self::UNKNOWN);
			
			// If the string contains substitution strings
			// we should apply the subs
			if (preg_match('/\%s/', $message))
			{
				$args = array_merge(array($message), is_array($data) ? $data : [$data]);
				$message = call_user_func_array('sprintf', $args);
			}
			// Just add the extra data at the end of the message
			else if (!empty($data))
			{
				$message .= ' : ' . $data;	
			}	
			parent::__construct($message, $code);
		}
	}
}