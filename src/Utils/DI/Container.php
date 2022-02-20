<?php

/**
 * Container.php - Jaxon data container
 *
 * Provide container service for Jaxon utils class instances.
 *
 * @package jaxon-core
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright 2016 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Utils\DI;

use Jaxon\Jaxon;
use Pimple\Container as PimpleContainer;
use Pimple\Exception\UnknownIdentifierException;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use Closure;
use ReflectionClass;
use ReflectionException;

use function realpath;

class Container extends PimpleContainer
{
    use Traits\AppTrait;
    use Traits\RequestTrait;
    use Traits\ResponseTrait;
    use Traits\PluginTrait;
    use Traits\ConfigTrait;
    use Traits\CallableTrait;
    use Traits\RegisterTrait;
    use Traits\ViewTrait;
    use Traits\UtilTrait;
    use Traits\SessionTrait;

    /**
     * The Dependency Injection Container
     *
     * @var ContainerInterface
     */
    private $appContainer = null;

    /**
     * The class constructor
     *
     * @param Jaxon $jaxon
     * @param array $aOptions The default options
     */
    public function __construct(Jaxon $jaxon, array $aOptions)
    {
        parent::__construct();

        $sTranslationDir = realpath(__DIR__ . '/../../../translations');
        $sTemplateDir = realpath(__DIR__ . '/../../../templates');
        // Translation directory
        $this->val('jaxon.core.dir.translation', $sTranslationDir);
        // Template directory
        $this->val('jaxon.core.dir.template', $sTemplateDir);
        // Library options
        $this->val('jaxon.core.options', $aOptions);

        $this->val(Jaxon::class, $jaxon);

        $this->registerAll();
    }

    /**
     * Register the values into the container
     *
     * @return void
     */
    private function registerAll()
    {
        $this->registerApp();
        $this->registerRequests();
        $this->registerResponses();
        $this->registerPlugins();
        $this->registerConfigs();
        $this->registerCallables();
        $this->registerViews();
        $this->registerUtils();
        $this->registerSessions();
    }

    /**
     * Get the container provided by the integrated framework
     *
     * @return ContainerInterface
     */
    public function getAppContainer()
    {
        return $this->appContainer;
    }

    /**
     * Set the container provided by the integrated framework
     *
     * @param ContainerInterface  $container     The container implementation
     *
     * @return void
     */
    public function setAppContainer(ContainerInterface $container)
    {
        $this->appContainer = $container;
    }

    /**
     * Check if a class is defined in the container
     *
     * @param string                $sClass             The full class name
     *
     * @return bool
     */
    public function has($sClass)
    {
        if($this->appContainer != null && $this->appContainer->has($sClass))
        {
            return true;
        }
        return $this->offsetExists($sClass);
    }

    /**
     * Get a class instance
     *
     * @param string                $sClass             The full class name
     *
     * @return mixed
     */
    public function g($sClass)
    {
        return $this->offsetGet($sClass);
    }

    /**
     * Get a class instance
     *
     * @param string                $sClass             The full class name
     *
     * @return mixed
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     * @throws UnknownIdentifierException If the identifier is not defined
     */
    public function get($sClass)
    {
        if($this->appContainer != null && $this->appContainer->has($sClass))
        {
            return $this->appContainer->get($sClass);
        }
        return $this->offsetGet($sClass);
    }

    /**
     * Save a closure in the container
     *
     * @param string                $sClass             The full class name
     * @param Closure               $xClosure           The closure
     *
     * @return void
     */
    public function set($sClass, Closure $xClosure)
    {
        $this->offsetSet($sClass, $xClosure);
    }

    /**
     * Save a value in the container
     *
     * @param string                $sKey               The key
     * @param mixed                 $xValue             The value
     *
     * @return void
     */
    public function val($sKey, $xValue)
    {
        $this->offsetSet($sKey, $xValue);
    }

    /**
     * Set an alias in the container
     *
     * @param string                $sAlias             The alias name
     * @param string                $sClass             The class name
     *
     * @return void
     */
    public function alias($sAlias, $sClass)
    {
        $this->set($sAlias, function($c) use ($sClass) {
            return $c->get($sClass);
        });
    }

    /**
     * Create an instance of a class, getting the contructor parameters from the DI container
     *
     * @param string|ReflectionClass $xClass The class name or the reflection class
     *
     * @return object|null
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws UnknownIdentifierException
     */
    public function make($xClass)
    {
        if(is_string($xClass))
        {
            // Create the reflection class instance
            $xClass = new ReflectionClass($xClass);
        }
        if(!($xClass instanceof ReflectionClass))
        {
            return null;
        }
        // Use the Reflection class to get the parameters of the constructor
        if(($constructor = $xClass->getConstructor()) === null)
        {
            return $xClass->newInstance();
        }
        $parameters = $constructor->getParameters();
        $parameterInstances = [];
        foreach($parameters as $parameter)
        {
            // Get the parameter instance from the DI
            $parameterInstances[] = $this->get($parameter->getClass()->getName());
        }
        return $xClass->newInstanceArgs($parameterInstances);
    }

    /**
     * Create an instance of a class by automatically fetching the dependencies from the constructor.
     *
     * @param string                $sClass             The class name
     *
     * @return void
     */
    public function auto($sClass)
    {
        $this->set($sClass, function($c) use ($sClass) {
            return $this->make($sClass);
        });
    }
}
