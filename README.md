Kohana Smart
============

Module for using Smarty. Smart is a complete replacement of View. Syntax for using this as View (all features of View include).
Smart using files with extension '.tpl', placed in views folder (or set folder in config).

Example of using:
~~~
$smart = Smart::factory('template')
	->bind('variable1', $variable1);
	
$variable1 = 'this is variable1';

$smart->variable2 = 'this is second variable';
$smart->submodule = Smart::factory('submodule');

$smart->submodule->fruits = array('Apple', 'Orange', 'Grape');
~~~

template.tpl
~~~
<h1>This is template file</h1>
<p>Variable 1: {{$variable1}}</p>
<p>Variable 2: {{$variable2}}</p>

{{$submodule}}
~~~

submodule.tpl
~~~
<h2>This is submodule file</h2>
<ul>
	{foreach $fruits as $fruit}
		<li>{{$fruit}}</li>
	{/foreach}
</ul>
~~~

Output we have:
~~~
<h1>This is template file</h1>
<p>Variable 1: this is variable1</p>
<p>Variable 2: this is second variable</p>

<h2>This is submodule file</h2>
<ul>
	<li>Apple</li>
	<li>Orange</li>
	<li>Grape</li>
</ul>
~~~