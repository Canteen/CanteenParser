#Canteen Parser

Canteen Parser is a library for managing, loading and rendering templates. For documentation of the codebase, please see [Canteen Parser docs](http://canteen.github.io/CanteenParser/).

##Installation

Install is available using [Composer](http://getcomposer.org).

```bash
composer require canteen/parser dev-master
```

Including using the Composer autoloader in your index.

```php
require 'vendor/autoload.php';
```

##Sample Usage

```php
use Canteen\Parser\Parser;
$parser = new Parser();

// Load an optional list of templates
$parser->addTemplate('MyTemplate', 'MyTemplate.html');

// Render the template with some substitutions
echo $parser->template(
	'MyTemplate',
	[
		'title' => 'My Page',
		'description' => 'Description goes here!'
	]
);
```

The contents of `MyTemplate.html`

```html
	<h1>{{title}}</h1>
	<div class="description">{{description}}</div>
```

Would echo:

```html
	<h1>My Page</h1>
	<div class="description">Description goes here!</div>
```

###Rebuild Documentation

This library is auto-documented using [YUIDoc](http://yui.github.io/yuidoc/). To install YUIDoc, run `sudo npm install yuidocjs`. Also, this requires the project [CanteenTheme](http://github.com/Canteen/CanteenTheme) be checked-out along-side this repository. To rebuild the docs, run the ant task from the command-line. 

```bash
ant docs
```

##License##

Copyright (c) 2013 [Matt Karl](http://github.com/bigtimebuddy)

Released under the MIT License.