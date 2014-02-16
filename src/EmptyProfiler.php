<?php

/**
*  @module Canteen\Parser
*/
namespace Canteen\Parser
{
	/**
	*  An empty profiler to use if the Profiler isn't included
	*  @class EmptyProfiler
	*/
	class EmptyProfiler
	{
		/**
		*  Start profiling a section of code
		*  @method start 
		*  @param {String} nodeName The name of the section to profile
		*/
		public function start($nodeName){}

		/**
		*  Stop profiling a section of code
		*  @method end
		*  @param {String} nodeName The name of the section to profile
		*/
		public function end($nodeName){}
	}
}