<?php

/**
*  @module Canteen\Parser
*/
namespace Canteen\Parser
{
	use Canteen\Profiler\Profiler;
	
	/**
	*  Simple string parser and template API to use for doing html substitutions. 
	*  Located in the namespace __Canteen\Parser__.
	*  
	*  @class Parser
	*/
	class Parser
	{
		/** 
		*  The list of valid templates 
		*  @property {Array} _templates
		*  @private
		*/
		private $_templates;

		/** 
		*  The instance of the profiler
		*  @property {Profiler} _profiler
		*  @private
		*/
		private $_profiler = null;
		
		/** 
		*  The maximum number of loops to parse before bailing
		*  @property {int} limit
		*  @default 10000
		*/
		public $limit = 10000;


		/**
		*  The map of file name to template contents
		*  @property {Dictionary} _cache
		*  @default []
		*/
		private $_cache = [];
	
		/**
		*  Create the loader
		*/
		public function __construct()
		{
			$this->_templates = [];
		}

		/**
		*  Attach an optional Profiler to the parser for debugging purposes
		*  @method setProfiler
		*  @param {Profiler} profiler
		*/
		public function setProfiler(Profiler $profiler)
		{
			$this->_profiler = $profiler;
		}
		
		/**
		*  Add a single template
		*  @method addTemplate
		*  @param {String} name The alias name of the template
		*  @param {String} path The full path to the template file
		*/
		public function addTemplate($name, $path)
		{
			if (isset($this->_templates[$name]))
			{
				throw new ParserError(ParserError::AUTOLOAD_TEMPLATE, $name);
			}
			$this->_templates[$name] = $path;
		}
		
		/**
		*  Register a JSON manfest which is an array that contains template files
		*  the templates paths should be relative to the location of the manifest file.
		*  @method addManifest
		*  @param {String} manifestPath The path of the manifest JSON to autoload
		*/
		public function addManifest($manifestPath)
		{			
			// Load the manifest json
			$templates = $this->load($manifestPath, false);
			
			// Get the directory of the manifest file
			$dir = dirname($manifestPath).'/';	
			
			// Include any templates
			if (isset($templates))
			{
				foreach($templates as $t)
				{
					$this->addTemplate(basename($t, '.html'), $dir . $t);
				}
			}
		}
		
		/**
		*  Load a JSON file from a path, does the error checking
		*  @method load
		*  @private
		*  @param {String} path The path to the .json file
		*  @param {Boolean} [asAssociate=true] Return as associative array
		*  @return {Array} The native object or array
		*/
		private function load($path, $asAssociative=true)
		{
			if (!fnmatch('*.json', $path) || !file_exists($path))
			{
				throw new FileError(FileError::FILE_EXISTS, $path);
			}
			
			$json = json_decode(file_get_contents($path), $asAssociative);
			
			if (empty($json))
			{
				throw new ParserError(ParserError::JSON_DECODE, $this->lastJsonError());
			}
			return $json;
		}
		
		/**
		*  Get the last JSON error message
		*  @method lastJsonError
		*  @private
		*  @return {String} The json error message
		*/
		private function lastJsonError()
		{
			// For PHP 5.5.0+
			if (function_exists('json_last_error_msg'))
			{
				return json_last_error_msg();
			}
			
			// If we can get the specific error, we should
			// Introduced in PHP 5.3.0
			if (function_exists('json_last_error'))
			{
				$errors = [
					JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
					JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
					JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
					JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON'
				];
				// Introduced in PHP 5.3.3
				if (defined('JSON_ERROR_UTF8'))
				{
					$errors[JSON_ERROR_UTF8] = 'Malformed UTF-8 characters, possibly incorrectly encoded';
				}
				return isset($errors[json_last_error()]) ? $errors[json_last_error()] : '';
			}
			return '';
		}
		
		/**
		*  Get a template by name
		*  @method getPath
		*  @param {String} name The template name
		*  @return {String} The path to the template
		*/
		public function getPath($template)
		{
			if (isset($this->_templates[$template]))
			{
				return $this->_templates[$template];
			}
			throw new ParserError(ParserError::TEMPLATE_UNKNOWN, $template);
		}
		
		/**
		*  Get a template content 
		*  @method getContents
		*  @param {String} The name of the template
		*  @param {Boolean} [cache=true] If we should cache the template, good for 
		*    templates that are requested multiple times.
		*  @return {String} The string contents of the template
		*/
		public function getContents($template, $cache=true)
		{
			$path = $this->getPath($template);
			
			// Check to see if file exists in cache
			if (isset($this->_cache[$path])) 
				return $this->_cache[$path];

			$contents = @file_get_contents($path);
			
			// If there's no file, don't do the rest of the regexps
			if ($contents === false)
			{
				throw new ParserError(ParserError::TEMPLATE_NOT_FOUND, $path);
			}
			else if ($cache)
			{
				$this->_cache[$path] = $contents;
			}
			
			return $contents;
		}
		
		/**
		*  Prepare the site content to be displayed
		*  This does all of the data substitutions and url fixes. The order of operations
		*  is to do the templates, loops, if blocks, then individual substitutions. 
		*  @method parse
		*  @param {String} &content The content data
		*  @param {Dictionary} substitutions The substitutions key => value replaces {{key}} in template
		*  @return {String} The parsed template
		*/
		public function parse(&$content, $substitutions)
		{
			$content = Engine::parse($this, $content, $substitutions, $this->_profiler);
			return $content;
		}
		
		/**
		*  Get the template by form name
		*  @method template
		*  @param {String} name The name of the template as defined in Autoloader
		*  @param {Boolean} [cache=true] If we should cache the template, good for 
		*    templates that are requested multiple times.
		*  @param {Dictionary} [substitutions=[]] The collection of data to substitute
		*/
		public function template($name, $substitutions=[], $cache=true)
		{
			return Engine::parse(
				$this, 
				$this->getContents($name, $cache), 
				$substitutions, 
				$this->_profiler
			);
		}
		
		/**
		*  Check to see if a string contains a sub tag
		*  @method contains
		*  @param {String} needle The tag name to look for with out the {{}}
		*  @param {String} haystack The string to search in
		*  @return {Boolean} If the tag is in the string
		*/
		public function contains($needle, $haystack)
		{
			return strpos($haystack, Lexer::OPEN.$needle.Lexer::CLOSE) !== false;
		}
		
		/**
		*  Remove the empty substitution tags
		*  @method removeEmpties 
		*  @param {String} content The content string
		*  @return {String} The content string
		*/
		public function removeEmpties(&$content)
		{
			Engine::checkBacktrackLimit($content);
			$content = preg_replace('/\{\{[^\}]+\}\}/', '', $content);
			return $content;
		}
		
		/**
		*  Parse a url with substitutions
		*  @method parseFile
		*  @param {String} url The path to the template
		*  @param {Dictionary} substitutions The substitutions key => value replaces {{key}} in template
		*  @param {Boolean} [cache=true] If we should cache the template, good for 
		*    templates that are requested multiple times.
		*  @return {String} The parsed template
		*/
		public function parseFile($url, $substitutions, $cache=true)
		{			
			// Check to see if file exists in cache
			if (isset($this->_cache[$url])) 
				return $this->_cache[$url];

			$contents = @file_get_contents($url);
			
			// If there's no file, don't do the rest of the regexps
			if ($contents === false)
			{
				throw new ParserError(ParserError::TEMPLATE_NOT_FOUND, $url);
			}
			else if ($cache)
			{
				$this->_cache[$url] = $contents;
			}
			
			// Do a regular parse with the string
			return Engine::parse($this, $contents, $substitutions, $this->_profiler);
		}
		
		/**
		*  Replaces any path (href/src) with the base
		*  @method fixPath
		*  @param {String} content The content string
		*  @param {Dictionary} basePath The string to prepend all src and href with
		*  @return {String} The content with paths fixed
		*/
		public function fixPath(&$content, $basePath)
		{
			// Replace the path to the stuff
			$content = preg_replace(
				'/(href|src)=["\']([^\/][^:"\']*)["\']/', 
				'$1="'.$basePath.'$2"', 
				$content
			);
			return $content;
		}

		/**
		*  Clear the cache
		*  @method flush
		*/
		public function flush()
		{
			$this->_cache = [];
		}
	}
}