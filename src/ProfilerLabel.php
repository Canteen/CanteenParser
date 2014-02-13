<?php

/**
*  @module Canteen\Parser
*/
namespace Canteen\Parser
{
	/**
	*  The label for use by the profiler
	*  @class ProfilerLabel
	*/
	abstract class ProfilerLabel
	{
		/**
		*   Engine is starting the rendering
		*   @property {String} MAIN
		*   @static
		*   @final
		*   @default 'Parse Main'
		*/
		const MAIN = 'Parse Main';
			
		/**
		*   Engine is rendering template for loop
		*   @property {String} LOOP
		*   @static
		*   @final
		*   @default 'Parse Loop'
		*/
		const LOOP = 'Parse Loop';

		/**
		*   Engine is rendering individual substitution tags
		*   @property {String} SINGLES
		*   @static
		*   @final
		*   @default 'Parse Singles'
		*/
		const SINGLES = 'Parse Singles';

		/**
		*   Engine is rendering conditional tags
		*   @property {String} COND
		*   @static
		*   @final
		*   @default 'Parse Conditional'
		*/
		const COND = 'Parse Conditional';
	}
}