<?php

/**
 * PluginManager.php - Jaxon plugin registry
 *
 * Register Jaxon plugins and callables.
 *
 * @package jaxon-core
 * @author Jared White
 * @author J. Max Wilson
 * @author Joseph Woolley
 * @author Steffen Konerow
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright Copyright (c) 2005-2007 by Jared White & J. Max Wilson
 * @copyright Copyright (c) 2008-2010 by Joseph Woolley, Steffen Konerow, Jared White  & J. Max Wilson
 * @copyright 2016 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Plugin\Manager;

use Jaxon\Jaxon;
use Jaxon\Di\Container;
use Jaxon\Exception\SetupException;
use Jaxon\Plugin\Code\CodeGenerator;
use Jaxon\Plugin\Contract\CallableRegistryInterface;
use Jaxon\Plugin\Contract\CodeGeneratorInterface;
use Jaxon\Plugin\Contract\RequestHandlerInterface;
use Jaxon\Plugin\Contract\ResponsePluginInterface;
use Jaxon\Plugin\RequestPlugin;
use Jaxon\Plugin\ResponsePlugin;
use Jaxon\Request\Plugin\CallableClass\CallableClassPlugin;
use Jaxon\Request\Plugin\CallableClass\CallableDirPlugin;
use Jaxon\Request\Plugin\CallableFunction\CallableFunctionPlugin;
use Jaxon\Response\Plugin\DataBag\DataBagPlugin;
use Jaxon\Response\Plugin\JQuery\JQueryPlugin;
use Jaxon\Response\Response;
use Jaxon\Ui\Dialogs\MessageInterface;
use Jaxon\Ui\Dialogs\QuestionInterface;
use Jaxon\Utils\Translation\Translator;

use function class_implements;
use function in_array;

class PluginManager
{
    /**
     * @var Container
     */
    protected $di;

    /**
     * @var Translator
     */
    protected $xTranslator;

    /**
     * The code generator
     *
     * @var CodeGenerator
     */
    private $xCodeGenerator;

    /**
     * Request plugins, indexed by name
     *
     * @var array<CallableRegistryInterface>
     */
    private $aRegistryPlugins = [];

    /**
     * Request handlers, indexed by name
     *
     * @var array<RequestHandlerInterface>
     */
    private $aRequestHandlers = [];

    /**
     * Response plugins, indexed by name
     *
     * @var array<ResponsePluginInterface>
     */
    private $aResponsePlugins = [];

    /**
     * The constructor
     *
     * @param Container $di
     * @param CodeGenerator $xCodeGenerator
     * @param Translator $xTranslator
     */
    public function __construct(Container $di, CodeGenerator $xCodeGenerator, Translator $xTranslator)
    {
        $this->di = $di;
        $this->xCodeGenerator = $xCodeGenerator;
        $this->xTranslator = $xTranslator;
    }

    /**
     * Get the request plugins
     *
     * @return array<RequestPlugin>
     */
    public function getRequestHandlers(): array
    {
        return $this->aRequestHandlers;
    }

    /**
     * Register a plugin
     *
     * Below is a table for priorities and their description:
     * - 0 to 999: Plugins that are part of or extensions to the jaxon core
     * - 1000 to 8999: User created plugins, typically, these plugins don't care about order
     * - 9000 to 9999: Plugins that generally need to be last or near the end of the plugin list
     *
     * @param string $sClassName    The plugin class
     * @param string $sPluginName    The plugin name
     * @param integer $nPriority    The plugin priority, used to order the plugins
     *
     * @return void
     * @throws SetupException
     */
    public function registerPlugin(string $sClassName, string $sPluginName, int $nPriority = 1000)
    {
        $bIsUsed = false;
        $aInterfaces = class_implements($sClassName);
        if(in_array(CodeGeneratorInterface::class, $aInterfaces))
        {
            $this->xCodeGenerator->addGenerator($sClassName, $nPriority);
            $bIsUsed = true;
        }
        if(in_array(CallableRegistryInterface::class, $aInterfaces))
        {
            $this->aRegistryPlugins[$sPluginName] = $sClassName;
            $bIsUsed = true;
        }
        if(in_array(RequestHandlerInterface::class, $aInterfaces))
        {
            $this->aRequestHandlers[$sPluginName] = $sClassName;
            $bIsUsed = true;
        }
        if(in_array(ResponsePluginInterface::class, $aInterfaces))
        {
            $this->aResponsePlugins[$sPluginName] = $sClassName;
            $bIsUsed = true;
        }

        // This plugin implements the Message interface
        if(in_array(MessageInterface::class, $aInterfaces))
        {
            $this->di->getDialog()->setMessage($sClassName);
            $bIsUsed = true;
        }
        // This plugin implements the Question interface
        if(in_array(QuestionInterface::class, $aInterfaces))
        {
            $this->di->getDialog()->setQuestion($sClassName);
            $bIsUsed = true;
        }

        if(!$bIsUsed)
        {
            // The class is invalid.
            $sMessage = $this->xTranslator->trans('errors.register.invalid', ['name' => $sClassName]);
            throw new SetupException($sMessage);
        }

        // Register the plugin in the DI container, if necessary
        if(!$this->di->has($sClassName))
        {
            $this->di->auto($sClassName);
        }
    }

    /**
     * Find the specified response plugin by name and return a reference to it if one exists
     *
     * @param string $sName    The name of the plugin
     * @param Response|null $xResponse    The response to attach the plugin to
     *
     * @return ResponsePlugin|null
     */
    public function getResponsePlugin(string $sName, ?Response $xResponse = null): ?ResponsePlugin
    {
        if(!isset($this->aResponsePlugins[$sName]))
        {
            return null;
        }
        $xPlugin = $this->di->g($this->aResponsePlugins[$sName]);
        if(($xResponse))
        {
            $xPlugin->setResponse($xResponse);
        }
        return $xPlugin;
    }

    /**
     * Register a function or callable class
     *
     * Call the request plugin with the $sType defined as name.
     *
     * @param string $sType    The type of request handler being registered
     * @param string $sCallable    The callable entity being registered
     * @param array|string $xOptions    The associated options
     *
     * @return void
     * @throws SetupException
     */
    public function registerCallable(string $sType, string $sCallable, $xOptions = [])
    {
        if(isset($this->aRegistryPlugins[$sType]) &&
            ($xPlugin = $this->di->g($this->aRegistryPlugins[$sType])))
        {
            $xPlugin->register($sType, $sCallable, $xPlugin->checkOptions($sCallable, $xOptions));
            return;
        }
        throw new SetupException($this->xTranslator->trans('errors.register.plugin',
            ['name' => $sType, 'callable' => $sCallable]));
    }

    /**
     * Register the Jaxon request plugins
     *
     * @return void
     * @throws SetupException
     */
    public function registerPlugins()
    {
        // Request plugins
        $this->registerPlugin(CallableClassPlugin::class, Jaxon::CALLABLE_CLASS, 101);
        $this->registerPlugin(CallableFunctionPlugin::class, Jaxon::CALLABLE_FUNCTION, 102);
        $this->registerPlugin(CallableDirPlugin::class, Jaxon::CALLABLE_DIR, 103);

        // Register the JQuery response plugin
        $this->registerPlugin(JQueryPlugin::class, JQueryPlugin::NAME, 700);
        // Register the DataBag response plugin
        $this->registerPlugin(DataBagPlugin::class, DataBagPlugin::NAME, 700);
    }
}