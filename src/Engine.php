<?php

/**
*  @module Canteen\Parser
*/
namespace Canteen\Parser
{
	use Canteen\Profiler\Profiler;

	/**
	*  The parser engine does the actual string substitutions
	*  @class Engine
	*/
	abstract class Engine 
	{
		/**
		*  Prepare the site content to be displayed
		*  This does all of the data substitutions and url fixes. The order of operations
		*  is to do the templates, loops, if blocks, then individual substitutions. 
		*  @method parse
		*  @static
		*  @param {String} content The content data
		*  @param {Dictionary} substitutions The substitutions key => value replaces {{key}} in template
		*  @param {Profiler} [profiler=null] Optional profiler usage to track performance
		*  @return {String} The parsed template
		*/
		public static function parse(Parser $parser, $content, $substitutions, Profiler $profiler=null)
		{
			self::checkBacktrackLimit($content);
			
			// Don't proceed if the string is empty
			if (empty($content)) return $content;
			
			// Search pattern for all
			$pattern = '/'.Lexer::OPEN.'('
					.Lexer::LOOP.'|'
					.Lexer::TEMPLATE.'|'
					.Lexer::COND.'|'
					.Lexer::COND.Lexer::NOT.
				')'
				.'([a-zA-Z0-9\''.Lexer::SEP.']+)'.Lexer::CLOSE.'/';
				
			preg_match_all($pattern, $content, $matches);
			
			if (count($matches))
			{			
				if ($profiler) $profiler->start(ProfilerLabel::MAIN);
				
				// length of opening and closing
				$closeLen = strlen(Lexer::CLOSE);
				$openLen = strlen(Lexer::OPEN);

				// Loop through all of the matches in order
				foreach($matches[0] as $i=>$tag)
				{
					$modifier = $matches[1][$i];
					$id = $matches[2][$i];
					$o1 = strpos($content, $tag);
					$o2 = $o1 + strlen($tag);

					if ($o1 === false) continue;

					// Get the tag prefix
					switch($modifier)
					{
						case Lexer::COND :
						case Lexer::COND.Lexer::NOT :
						{
							if($profiler) $profiler->start(ProfilerLabel::COND);

							$isNot = $modifier == Lexer::COND.Lexer::NOT;
							$endTag = Lexer::OPEN . Lexer::COND_END 
								. ($isNot ? Lexer::NOT : '')
								. $id . Lexer::CLOSE;
							
							// Remove the tags if content is true
							$value = self::lookupValue($substitutions, $id);
							
							// The position order $o1{{if:}}$o2...$c2{{/if:}}$c1
							$c2 = strpos($content, $endTag);
							$c1 = $c2  + strlen($endTag);
							
							// There's no ending tag, we shouldn't continue
							// maybe we should throw an exception here
							if ($c2 === false) continue;
							
							// Default is to replace with nothing
							$buffer = '';
							
							// If statement logic
							if ($isNot != self::asBoolean($value))
							{
								// Get the contents of if and parse it
								$buffer = self::parse(
									$parser,
									substr($content, $o2, $c2 - $o2),
									$substitutions,
									$profiler
								);
							}
							// Remove the if statement and it's contents							
							$content = substr_replace($content, $buffer, $o1, $c1 - $o1);
							if($profiler) $profiler->end(ProfilerLabel::COND);
							break;
						}
						case Lexer::LOOP :
						{
							if($profiler) $profiler->start(ProfilerLabel::LOOP);
												
							$endTag = Lexer::OPEN . Lexer::LOOP_END . $id . Lexer::CLOSE;
							
							// The position order $o1{{for:}}$o2...$c2{{/for:}}$c1
							$c2 = strpos($content, $endTag);
							$c1 = $c2  + strlen($endTag);
							
							// There's no ending tag, we shouldn't continue
							// maybe we should throw an exception here
							if ($c2 === false) continue;
							
							$value = self::lookupValue($substitutions, $id);

							// Remove the loop contents if there's no data
							if ($value === null || !is_array($value))
							{
								$content = substr_replace($content, '', $o1, $c1 - $o1);
								if($profiler) $profiler->end(ProfilerLabel::LOOP);
								continue;
							}

							$buffer = '';
							$template = substr($content, $o2, $c2 - $o2);

							foreach($value as $sub)
							{				
								// If the item is an object
								if (is_object($sub))
									$sub = get_object_vars($sub);
							
								// The item should be an array
								if (!is_array($sub))
								{
									error('Parsing for-loop substitution needs to be an array');
									continue;
								}
								$buffer .= self::parse($parser, $template, $sub, $profiler);
							}

							// Replace the template with the buffer
							$content = substr_replace($content, $buffer, $o1, $c1 - $o1);
							if($profiler) $profiler->end(ProfilerLabel::LOOP);
							break;
						}
						case Lexer::TEMPLATE :
						{
							$template = $parser->template($id, $substitutions);
							$content = preg_replace('/'.$tag.'/', $template, $content);
							break;
						}
					}
				}
				if ($profiler) $profiler->end(ProfilerLabel::MAIN);
			}
			
			$pattern = '/'.Lexer::OPEN.'([a-zA-Z0-9\''.Lexer::SEP.']+)'.Lexer::CLOSE.'/';
			preg_match_all($pattern, $content, $matches);
			
			if (count($matches))
			{
				if ($profiler) $profiler->start(ProfilerLabel::SINGLES);

				foreach($matches[0] as $i=>$tag)
				{
					$id = $matches[1][$i];
					$value = self::lookupValue($substitutions, $id);
				
					// only do replacements if the id exists in the substitutions
					// there might be another pass that actually does the replacement
					// for instance the Canteen parse then the Controller parse
					if ($value === null) continue;

					if (is_array($value))
					{
						throw new ParserError(ParserError::PARSE_ARRAY, [$id, implode(', ', $value)]);
					}
					
					$content = preg_replace('/'.$tag.'/', (string)$value, $content);
				}
				if ($profiler) $profiler->end(ProfilerLabel::SINGLES);
			}
			return $content;
		}

		/**
		*  Get the nested value for a dot-syntax array/object lookup
		*  For instance, `getNextVar($substitutions, 'event.name')`
		*  @method lookupValue
		*  @private
		*  @param {Dictionary|Object} context The associative array or object
		*  @param {String} name The do matrix name
		*  @return {mixed} The value of the lookup
		*/
		private static function lookupValue($context, $name)
		{
		    $pieces = explode(Lexer::SEP, $name);
		    foreach($pieces as $piece)
		    {
		        if (is_array($context) && array_key_exists($piece, $context))
		        {
		           $context = $context[$piece];
		        }
		        else if (is_object($context) && property_exists($context, $piece))
		        {
		        	$context = $context->$piece;
		        }
		        else
		        {
		        	return null;
		        }
		    }
		    return $context;
		}

		/**
		*  Global functions to check for a string-based boolean
		*  @method asBoolean
		*  @static
		*  @param {mixed} str The value to check as Boolean
		*  @return {Boolean} A boolean value
		*/
		public static function asBoolean($str)
		{
			if (is_array($str)) return (boolean)$str;
			$str = $str ? (string)$str : 'false';
			return (strtolower(trim($str)) === 'false') ? false : (boolean)$str;
		}

		/**
		*  The default backtrack limit for preg expressions is 100KB, 
		*  we may have pages which ar larger than 100,000, and 
		*  need to increase the pcre.backtrack_limit
		*  @method checkBacktrackLimit
		*  @static
		*  @param {String} string The string to limit test
		*/
		public static function checkBacktrackLimit($string)
		{
			$defaultLimit = ini_get('pcre.backtrack_limit');
			$length = strlen($string);
			if ($length > $defaultLimit)
			{
				ini_set('pcre.backtrack_limit', $length);
			}
		}
	}
}