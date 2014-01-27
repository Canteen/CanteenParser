<?php

/**
*  @module Canteen\Parser
*/
namespace Canteen\Parser
{
	/**
	*  Defines the syntax used for the the parsing
	*  @class Lexer
	*/
	abstract class Lexer 
	{
		/**
		*   Lexer for the opening of a parse tag
		*   @property {String} OPEN
		*   @static
		*   @final
		*   @default '{{'
		*/
		const OPEN = '{{';
			
		/**
		*   Lexer for the closing of a parse tag
		*   @property {String} CLOSE
		*   @static
		*   @final
		*   @default '}}'
		*/
		const CLOSE = '}}';
		
		/**
		*   Lexer for definition of an if conditional parse tag
		*   @property {String} COND
		*   @static
		*   @final
		*   @default 'if:'
		*/
		const COND = 'if:';
		
		/**
		*   Lexer for if logical operator
		*   @property {String} NOT
		*   @static
		*   @final
		*   @default '!'
		*/
		const NOT = '!';
		
		/**
		*   Lexer for if closing if tag
		*   @property {String} COND_END
		*   @static
		*   @final
		*   @default '/if:'
		*/
		const COND_END = '/if:';
		
		/**
		*   Lexer for if opening loop tag
		*   @property {String} LOOP
		*   @static
		*   @final
		*   @default 'for:'
		*/
		const LOOP = 'for:';

		/**
		*   The property seperator similar to object "->"
		*   @property {String} SEP
		*   @static
		*   @final
		*   @default '.'
		*/
		const SEP = '.';
		
		/**
		*   Lexer for if closing loop tag
		*   @property {String} LOOP_END
		*   @static
		*   @final
		*   @default '/for:'
		*/
		const LOOP_END = '/for:';
		
		/**
		*   Lexer for if defining a template
		*   @property {String} TEMPLATE
		*   @static
		*   @final
		*   @default 'template:'
		*/
		const TEMPLATE = 'template:';
	}
}