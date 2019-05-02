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

namespace Jaxon\DI;

use Lemon\Event\EventDispatcher;
use Jaxon\App\View\Renderer;

use Jaxon\Jaxon;
use Jaxon\Response\Response;
use Jaxon\Config\Config;
use Jaxon\Config\Reader as ConfigReader;
use Jaxon\Request\Support\CallableRepository;
use Jaxon\Request\Handler as RequestHandler;
use Jaxon\Request\Factory as RequestFactory;
use Jaxon\Response\Manager as ResponseManager;
use Jaxon\Plugin\Manager as PluginManager;
use Jaxon\Plugin\CodeGenerator;
use Jaxon\App\Dialogs\Dialog;
use Jaxon\Utils\Template\Minifier;
use Jaxon\Utils\Translation\Translator;
use Jaxon\Utils\Template\Template;
use Jaxon\Utils\Validation\Validator;
use Jaxon\Utils\Pagination\Paginator;
use Jaxon\Utils\Pagination\Renderer as PaginationRenderer;

class Container
{
    // The Dependency Injection Container
    private $libContainer = null;

    // The Dependency Injection Container
    private $appContainer = null;

    // The only instance of the Container (Singleton)
    private static $xInstance = null;

    public static function getInstance()
    {
        if(!self::$xInstance)
        {
            self::$xInstance = new Container();
        }
        return self::$xInstance;
    }

    private function __construct()
    {
        $this->libContainer = new \Pimple\Container();

        $sTranslationDir = realpath(__DIR__ . '/../../translations');
        $sTemplateDir = realpath(__DIR__ . '/../../templates');
        $this->init($sTranslationDir, $sTemplateDir);
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
     * Set the parameters and create the objects in the dependency injection container
     *
     * @param string        $sTranslationDir     The translation directory
     * @param string        $sTemplateDir        The template directory
     *
     * @return void
     */
    private function init($sTranslationDir, $sTemplateDir)
    {
        /*
         * Parameters
         */
        // Translation directory
        $this->libContainer['jaxon.core.translation_dir'] = $sTranslationDir;
        // Template directory
        $this->libContainer['jaxon.core.template_dir'] = $sTemplateDir;

        /*
         * Core library objects
         */
        // Jaxon Core
        $this->libContainer[Jaxon::class] = function () {
            return new Jaxon();
        };
        // Global Response
        $this->libContainer[Response::class] = function () {
            return new Response();
        };
        // Dialog
        $this->libContainer[Dialog::class] = function () {
            return new Dialog();
        };

        /*
         * Managers
         */
        // Callable objects repository
        $this->libContainer[CallableRepository::class] = function () {
            return new CallableRepository();
        };
        // Plugin Manager
        $this->libContainer[PluginManager::class] = function () {
            return new PluginManager();
        };
        // Request Handler
        $this->libContainer[RequestHandler::class] = function ($c) {
            return new RequestHandler($c[PluginManager::class], $c[ResponseManager::class]);
        };
        // Request Factory
        $this->libContainer[RequestFactory::class] = function ($c) {
            return new RequestFactory($c[CallableRepository::class]);
        };
        // Response Manager
        $this->libContainer[ResponseManager::class] = function () {
            return new ResponseManager();
        };
        // Code Generator
        $this->libContainer[CodeGenerator::class] = function ($c) {
            return new CodeGenerator($c[PluginManager::class]);
        };

        /*
         * Config
         */
        $this->libContainer[Config::class] = function () {
            return new Config();
        };
        $this->libContainer[ConfigReader::class] = function () {
            return new ConfigReader();
        };

        /*
         * Services
         */
        // Minifier
        $this->libContainer[Minifier::class] = function () {
            return new Minifier();
        };
        // Translator
        $this->libContainer[Translator::class] = function ($c) {
            return new Translator($c['jaxon.core.translation_dir'], $c[Config::class]);
        };
        // Template engine
        $this->libContainer[Template::class] = function ($c) {
            return new Template($c['jaxon.core.template_dir']);
        };
        // Validator
        $this->libContainer[Validator::class] = function ($c) {
            return new Validator($c[Translator::class], $c[Config::class]);
        };
        // Pagination Renderer
        $this->libContainer[PaginationRenderer::class] = function ($c) {
            return new PaginationRenderer($c[Template::class]);
        };
        // Pagination Paginator
        $this->libContainer[Paginator::class] = function ($c) {
            return new Paginator($c[PaginationRenderer::class]);
        };
        // Event Dispatcher
        $this->libContainer[EventDispatcher::class] = function () {
            return new EventDispatcher();
        };

        // View Renderer Facade
        // $this->libContainer[\Jaxon\App\View\Facade::class] = function ($c) {
        //     $aRenderers = $c['jaxon.view.data.renderers'];
        //     $sDefaultNamespace = $c['jaxon.view.data.namespace.default'];
        //     return new \Jaxon\App\View\Facade($aRenderers, $sDefaultNamespace);
        // };
    }

    /**
     * Get a class instance
     *
     * @return object        The class instance
     */
    public function get($sClass)
    {
        if($this->appContainer != null && $this->appContainer->has($sClass))
        {
            return $this->appContainer->get($sClass);
        }
        return $this->libContainer[$sClass];
    }

    /**
     * Set a DI closure
     *
     * @param string                $sClass             The full class name
     * @param Closure               $xClosure           The closure
     *
     * @return void
     */
    public function set($sClass, $xClosure)
    {
        $this->libContainer[$sClass] = $xClosure;
    }

    /**
     * Get the plugin manager
     *
     * @return \Jaxon\Plugin\Manager
     */
    public function getPluginManager()
    {
        return $this->libContainer[PluginManager::class];
    }

    /**
     * Get the request handler
     *
     * @return \Jaxon\Request\Handler
     */
    public function getRequestHandler()
    {
        return $this->libContainer[RequestHandler::class];
    }

    /**
     * Get the request factory
     *
     * @return \Jaxon\Factory\Request
     */
    public function getRequestFactory()
    {
        return $this->libContainer[RequestFactory::class];
    }

    /**
     * Get the response manager
     *
     * @return \Jaxon\Response\Manager
     */
    public function getResponseManager()
    {
        return $this->libContainer[ResponseManager::class];
    }

    /**
     * Get the code generator
     *
     * @return \Jaxon\Code\Generator
     */
    public function getCodeGenerator()
    {
        return $this->libContainer[CodeGenerator::class];
    }

    /**
     * Get the config manager
     *
     * @return \Jaxon\Config\Config
     */
    public function getConfig()
    {
        return $this->libContainer[Config::class];
    }

    /**
     * Create a new the config manager
     *
     * @return \Jaxon\Config\Config            The config manager
     */
    public function newConfig()
    {
        return new \Jaxon\Config\Config();
    }

    /**
     * Get the dialog wrapper
     *
     * @return \Jaxon\App\Dialogs\Dialog
     */
    public function getDialog()
    {
        return $this->libContainer[Dialog::class];
    }

    /**
     * Get the minifier
     *
     * @return \Jaxon\Utils\Template\Minifier
     */
    public function getMinifier()
    {
        return $this->libContainer[Minifier::class];
    }

    /**
     * Get the translator
     *
     * @return \Jaxon\Utils\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->libContainer[Translator::class];
    }

    /**
     * Get the template engine
     *
     * @return \Jaxon\Utils\Template\Template
     */
    public function getTemplate()
    {
        return $this->libContainer[Template::class];
    }

    /**
     * Get the validator
     *
     * @return \Jaxon\Utils\Validation\Validator
     */
    public function getValidator()
    {
        return $this->libContainer[Validator::class];
    }

    /**
     * Get the paginator
     *
     * @return \Jaxon\Utils\Pagination\Paginator
     */
    public function getPaginator()
    {
        return $this->libContainer[Paginator::class];
    }

    /**
     * Set the pagination renderer
     *
     * @param Jaxon\Utils\Pagination\Renderer  $xRenderer    The pagination renderer
     *
     * @return void
     */
    public function setPaginationRenderer(PaginationRenderer $xRenderer)
    {
        $this->libContainer[PaginationRenderer::class] = $xRenderer;
    }

    /**
     * Get the event dispatcher
     *
     * @return Lemon\Event\EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->libContainer[EventDispatcher::class];
    }

    /**
     * Get the global Response object
     *
     * @return \Jaxon\Response\Response
     */
    public function getResponse()
    {
        return $this->libContainer[Response::class];
    }

    /**
     * Create a new Jaxon response object
     *
     * @return \Jaxon\Response\Response
     */
    public function newResponse()
    {
        return new Response();
    }

    /**
     * Get the main Jaxon object
     *
     * @return \Jaxon\Jaxon
     */
    public function getJaxon()
    {
        return $this->libContainer[Jaxon::class];
    }

    /**
     * Get the Jaxon library version number
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->getJaxon()->getVersion();
    }

    /**
     * Get the App instance
     *
     * @return \Jaxon\App\App
     */
    public function getApp()
    {
        return $this->libContainer['jaxon.app'];
    }

    /**
     * Set the App instance
     *
     * @param Jaxon\App\App     $xApp            The App instance
     *
     * @return void
     */
    public function setApp($xApp)
    {
        $this->libContainer['jaxon.app'] = $xApp;
    }

    /**
     * Get the Armada instance
     *
     * @return \Jaxon\Armada\Armada
     */
    public function getArmada()
    {
        return $this->libContainer['jaxon.armada'];
    }

    /**
     * Set the Armada instance
     *
     * @param Jaxon\Armada\Armada     $xArmada            The Armada instance
     *
     * @return void
     */
    public function setArmada($xArmada)
    {
        $this->libContainer['jaxon.armada'] = $xArmada;
    }

    /**
     * Set the view renderers data
     *
     * @param array                $aRenderers          Array of renderer names with namespace as key
     *
     * @return void
     */
    public function initViewRenderers($aRenderers)
    {
        $this->libContainer['jaxon.view.data.renderers'] = $aRenderers;
    }

    /**
     * Set the view namespaces data
     *
     * @param array                $aNamespaces         Array of namespaces with renderer name as key
     *
     * @return void
     */
    public function initViewNamespaces($aNamespaces, $sDefaultNamespace)
    {
        $this->libContainer['jaxon.view.data.namespaces'] = $aNamespaces;
        $this->libContainer['jaxon.view.data.namespace.default'] = $sDefaultNamespace;
    }

    /**
     * Add a view renderer
     *
     * @param string                $sId                The unique identifier of the view renderer
     * @param Closure               $xClosure           A closure to create the view instance
     *
     * @return void
     */
    public function addViewRenderer($sId, $xClosure)
    {
        // Return the non-initialiazed view renderer
        $this->libContainer['jaxon.app.view.base.' . $sId] = $xClosure;

        // Return the initialized view renderer
        $this->libContainer['jaxon.app.view.' . $sId] = function ($c) use ($sId) {
            // Get the defined renderer
            $renderer = $c['jaxon.app.view.base.' . $sId];
            // Init the renderer with the template namespaces
            $aNamespaces = $this->libContainer['jaxon.view.data.namespaces'];
            if(key_exists($sId, $aNamespaces))
            {
                foreach($aNamespaces[$sId] as $ns)
                {
                    $renderer->addNamespace($ns['namespace'], $ns['directory'], $ns['extension']);
                }
            }
            return $renderer;
        };
    }

    /**
     * Get the view renderer
     *
     * @param string                $sId                The unique identifier of the view renderer
     *
     * @return \Jaxon\App\Contracts\View
     */
    public function getViewRenderer($sId = '')
    {
        if(!$sId)
        {
            // Return the view renderer facade
            return $this->libContainer[\Jaxon\App\View\Facade::class];
        }
        // Return the view renderer with the given id
        return $this->libContainer['jaxon.app.view.' . $sId];
    }

    /**
     * Get the session object
     *
     * @return \Jaxon\App\Contracts\Session
     */
    public function getSessionManager()
    {
        return $this->libContainer['jaxon.armada.session'];
    }

    /**
     * Set the session
     *
     * @param Closure      $xClosure      A closure to create the session instance
     *
     * @return void
     */
    public function setSessionManager($xClosure)
    {
        $this->libContainer['jaxon.armada.session'] = $xClosure;
    }
}
