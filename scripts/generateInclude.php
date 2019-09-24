#!/usr/bin/php
<?php

if (php_sapi_name() !== 'cli')
{
	die('Not in CLI');
}

$root = __DIR__ . '/..';
include $root . '/vendor/autoload.php';
$map = include $root . '/vendor/composer/autoload_classmap.php';
foreach ($map as $filepath)
{
	include_once $filepath;
}

$scores = $relations = array();
foreach (filterNamespace(get_declared_classes()) as $className)
{
	$scores[$className] = 0;
	$relations[$className] = array();
	$class = new ReflectionClass($className);
	foreach (filterNamespace($class->getInterfaceNames()) as $interfaceName)
	{
		$scores[$interfaceName] = 0;
		$relations[$className][] = $interfaceName;
	}
	if (method_exists($class, 'getTraitNames'))
	{
		foreach (filterNamespace($class->getTraitNames()) as $traitName)
		{
			$scores[$traitName] = 0;
			$relations[$className][] = $traitName;
		}
	}
	$parentClass = $class->getParentClass();
	if ($parentClass)
	{
		$parentName = $parentClass->getName();
		$relations[$className][] = $parentName;
	}
}

function filterNamespace(array $fqns)
{
	return array_filter(
		$fqns,
		function ($fqn)
		{
			return (strpos($fqn, 'matthiasmullie\\') === 0);
		}
	);
}

do
{
	$continue = false;
	foreach ($relations as $className => $relationNames)
	{
		foreach ($relationNames as $relationName)
		{
			if ($scores[$className] <= $scores[$relationName])
			{
				$scores[$className] = 1 + $scores[$relationName];
				$continue = true;
			}
		}
	}
}
while ($continue);

$namesByScore = array();
foreach ($scores as $name => $score)
{
	$namesByScore[$score][] = $name;
}
ksort($namesByScore);

$file    = '<?php';
foreach ($namesByScore as $names)
{
	sort($names);
	foreach ($names as $name)
	{
		$file .= substr(file_get_contents($map[$name]), 5);
	}
}

$target = $root . '/s9e/reckless/include.php';
file_put_contents($target, $file);