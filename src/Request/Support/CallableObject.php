<?php

namespace Xajax\Request\Support;

use Xajax\Request\Request;
use Xajax\Request\Manager as RequestManager;
use Xajax\Response\Manager as ResponseManager;

/*
	File: CallableObject.php

	Contains the CallableObject class

	Title: CallableObject class

	Please see <copyright.php> for a detailed description, copyright
	and license information.
*/

/*
	@package Xajax
	@version $Id: CallableObject.php 362 2007-05-29 15:32:24Z calltoconstruct $
	@copyright Copyright (c) 2005-2007 by Jared White & J. Max Wilson
	@copyright Copyright (c) 2008-2010 by Joseph Woolley, Steffen Konerow, Jared White  & J. Max Wilson
	@license http://www.xajaxproject.org/bsd_license.txt BSD License
*/

/*
	Class: CallableObject
	
	A class that stores a reference to an object whose methods can be called from
	the client via a xajax request.  <xajax> will call 
	<CallableObject->getClientScript> so that stub functions can be 
	generated and sent to the browser.
*/

class CallableObject
{
	use \Xajax\Utils\ContainerTrait;

	/*
		Object: obj
		
		A reference to the callable object.
	*/
	private $callableObject;

	/*
		Object: reflectionClass
		
		The reflection class of the callable object.
	*/
	private $reflectionClass;
	
	/*
		Array: aExcludedMethods
		
		An associative array that will contain methods the library must not
		export to javascript code.
	*/
	private $aExcludedMethods;
	
	/*
		String: classpath
		
		The path to the file where the callable object class is defined.
	*/
	private $classpath = '';
	
	/*
		String: namespace
		
		The namespace where the callable object class is defined.
	*/
	private $namespace = '';
	
	/*
		Array: aConfiguration
		
		An associative array that will contain configuration options for zero
		or more of the objects methods.  These configuration options will 
		define the call options for each request.  The call options will be
		passed to the client browser when the function stubs are generated.
	*/
	private $aConfiguration;
	
	/*
		Function: __construct
		
		Constructs and initializes the <CallableObject>
		
		obj - (object):  The object to reference.
	*/
	public function __construct($obj)
	{
		$this->callableObject = $obj;
		$this->reflectionClass = new \ReflectionClass(get_class($this->callableObject));
		$this->aConfiguration = array();
		// By default, the methods of the RequestTrait and ResponseTrait traits are excluded
		$this->aExcludedMethods = array('setGlobalResponse', 'newResponse',
				'setXajaxCallable', 'getXajaxClassName', 'request');
	}

	/*
		Function: getClassName
		
		Returns the class name of this callable object, without the namespace if any.
	*/
	private function getClassName()
	{
		// Get the class name without the namespace.
		return $this->reflectionClass->getShortName();
	}

	/*
		Function: getName
		
		Returns the name of this callable object. This is the name of the generated javascript class.
	*/
	public function getName()
	{
		// The class name without the namespace.
		$name = $this->reflectionClass->getShortName();
		// Append the classpath to the name
		if(($this->classpath))
		{
			$name = $this->classpath . $name;
		}
		return $name;
	}

	/*
		Function: getNamespace
		
		Returns the namespace of this callable object.
	*/
	public function getNamespace()
	{
		// The namespace the class was registered with.
		return $this->namespace;
	}

	/*
		Function: getPath
		
		Returns the class path of this callable object.
	*/
	public function getPath()
	{
		// The class path without the trailing dot.
		return rtrim($this->classpath, '.');
	}

	public function getMethods()
	{
		$aReturn = array();
		foreach($this->reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $xMethod)
		{
			$sMethodName = $xMethod->getShortName();
			// Don't take magic __call, __construct, __destruct methods
			if(strlen($sMethodName) > 2 && substr($sMethodName, 0, 2) == '__')
			{
				continue;
			}
			// Don't take excluded methods
			if(in_array($sMethodName, $this->aExcludedMethods))
			{
				continue;
			}
			$aReturn[] = $sMethodName;
		}
		return $aReturn;
	}

	/*
		Function: configure
		
		Used to set configuration options / call options for each method.
		
		sMethod - (string):  The name of the method.
		sName - (string):  The name of the configuration option.
		sValue - (string):  The value to be set.
	*/
	public function configure($sMethod, $sName, $sValue)
	{
		// Set the namespace
		if($sName == 'namespace')
		{
			if($sValue != '')
				$this->namespace = $sValue;
			return;
		}
		// Set the classpath
		if($sName == 'classpath')
		{
			if($sValue != '')
				$this->classpath = $sValue . '.';
			return;
		}
		// Set the excluded methods
		if($sName == 'excluded')
		{
			if(is_array($sValue))
				$this->aExcludedMethods = array_merge($this->aExcludedMethods, $sValue);
			else if(is_string($sValue))
				$this->aExcludedMethods[] = $sValue;
			return;
		}
		
		if(!isset($this->aConfiguration[$sMethod]))
		{
			$this->aConfiguration[$sMethod] = array();
		}	
		$this->aConfiguration[$sMethod][$sName] = $sValue;
	}

	/*
		Function: generateRequests
		
		Produces an array of <xajaxRequest> objects, one for each method
		exposed by this callable object.
		
		sXajaxPrefix - (string):  The prefix to be prepended to the
			javascript function names; this will correspond to the name
			used for the function stubs that are generated by the
			<CallableObject->getClientScript> call.
	*/
	public function generateRequests()
	{
		$aRequests = array();
		$sClass = $this->getClassName();

		foreach($this->reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $xMethod)
		{
			$sMethodName = $xMethod->getShortName();
			// Don't generate magic __call, __construct, __destruct methods
			if(strlen($sMethodName) > 2 && substr($sMethodName, 0, 2) == '__')
			{
				continue;
			}
			// Don't generate excluded methods
			if(in_array($sMethodName, $this->aExcludedMethods))
			{
				continue;
			}
			$aRequests[$sMethodName] = new Request("{$this->classpath}{$sClass}.{$sMethodName}", 'object');
		}

		return $aRequests;
	}
	
	/*
		Function: getClientScript
		
		Called by <CallableObject->getClientScript> while <xajax> is 
		generating the javascript to be sent to the browser.

		sXajaxPrefix - (string):  The prefix to be prepended to the
			javascript function names.
	*/	
	public function getClientScript()
	{
		$sXajaxPrefix = $this->getOption('core.prefix.class');
		$sClass = $this->classpath . $this->getClassName();
		$aMethods = array();
		$aConfig = array();

		if(isset($this->aConfiguration['*']))
		{
			$aConfig = $this->aConfiguration['*'];
		}
		foreach($this->reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $xMethod)
		{
			$sMethodName = $xMethod->getShortName();
			// Don't export magic __call, __construct, __destruct methods
			if(strlen($sMethodName) > 0 && substr($sMethodName, 0, 2) == '__')
			{
				continue;
			}
			// Don't export excluded methods
			if(in_array($sMethodName, $this->aExcludedMethods))
			{
				continue;
			}
			$aMethod = array('name' => $sMethodName, 'config' => $aConfig);
			if(isset($this->aConfiguration[$sMethodName]))
			{
				$aMethod['config'] = array_merge($aMethod['config'], $this->aConfiguration[$sMethodName]);
			}
			$aMethods[] = $aMethod;
		}

		return $this->render('support/object.js.tpl', array(
			'sPrefix' => $sXajaxPrefix,
			'sClass' => $sClass,
			'aMethods' => $aMethods,
		));
	}
	
	/*
		Function: isClass
		
		Determins if the specified class name matches the class name of the
		object referenced by <CallableObject->obj>.
		
		sClass - (string):  The name of the class to check.
		
		Returns:
		
		boolean - True of the specified class name matches the class of
			the object being referenced; false otherwise.
	*/
	public function isClass($sClass)
	{
		return ($this->reflectionClass->getName() === $sClass);
	}
	
	/*
		Function: hasMethod
		
		Determines if the specified method name is one of the methods of the
		object referenced by <CallableObject->obj>.
		
		sMethod - (object):  The name of the method to check.
		
		Returns:
		
		boolean - True of the referenced object contains the specified method,
			false otherwise.
	*/
	public function hasMethod($sMethod)
	{
		return $this->reflectionClass->hasMethod($sMethod) || $this->reflectionClass->hasMethod('__call');
	}
	
	/*
		Function: call
		
		Call the specified method of the object being referenced using the specified
		array of arguments.
		
		sMethod - (string): The name of the method to call.
		aArgs - (array):  The arguments to pass to the method.
	*/
	public function call($sMethod, $aArgs)
	{
		if(!$this->hasMethod($sMethod))
			return;
		$reflectionMethod = $this->reflectionClass->getMethod($sMethod);
		ResponseManager::getInstance()->append($reflectionMethod->invokeArgs($this->callableObject, $aArgs));
	}

	/*
		Function: getRegisteredObject
		
		Returns the registered callable object.
	*/
	public function getRegisteredObject()
	{
		return $this->callableObject;
	}
}
