<?php

/**
 * DialogPlugin.php - ModalInterface, message and question dialogs for Jaxon.
 *
 * Show modal, message and question dialogs with various javascript libraries
 * based on user settings.
 *
 * @package jaxon-core
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright 2016 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-dialogs
 */

namespace Jaxon\Plugin\Response\Dialog;

use Jaxon\Config\ConfigManager;
use Jaxon\Exception\SetupException;
use Jaxon\Plugin\ResponsePlugin;
use Jaxon\Response\Response;
use Jaxon\Ui\Dialog\Library\DialogLibraryManager;
use Jaxon\Ui\Dialog\MessageInterface;
use Jaxon\Ui\Dialog\ModalInterface;

use function array_reduce;
use function trim;

class DialogPlugin extends ResponsePlugin implements ModalInterface, MessageInterface
{
    use DialogLibraryTrait;

    /**
     * @const The plugin name
     */
    const NAME = 'dialog';

    /**
     * @var DialogLibraryManager
     */
    protected $xLibraryManager;

    /**
     * @var ConfigManager
     */
    protected $xConfigManager;

    /**
     * @var array
     */
    protected $aLibraries = null;

    /**
     * The constructor
     *
     * @param ConfigManager $xConfigManager
     * @param DialogLibraryManager $xLibraryManager
     */
    public function __construct(ConfigManager $xConfigManager, DialogLibraryManager $xLibraryManager)
    {
        $this->xConfigManager = $xConfigManager;
        $this->xLibraryManager = $xLibraryManager;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getHash(): string
    {
        // The version number is used as hash
        return '4.0.0';
    }

    /**
     * Register the javascript dialog libraries from config options.
     *
     * @return void
     * @throws SetupException
     */
    public function registerLibraries()
    {
        $aLibraries = $this->xConfigManager->getOption('dialogs.libraries', []);
        foreach($aLibraries as $sClassName => $sName)
        {
            $this->xLibraryManager->registerLibrary($sClassName, $sName);
        }
    }

    /**
     * Set the default library for each dialog feature.
     *
     * @return void
     * @throws SetupException
     */
    public function setDefaultLibraries()
    {
        // Set the default modal library
        if(($sName = $this->xConfigManager->getOption('dialogs.default.modal', '')))
        {
            $this->xLibraryManager->setModalLibrary($sName);
        }
        // Set the default message library
        if(($sName = $this->xConfigManager->getOption('dialogs.default.message', '')))
        {
            $this->xLibraryManager->setMessageLibrary($sName);
        }
        // Set the default question library
        if(($sName = $this->xConfigManager->getOption('dialogs.default.question', '')))
        {
            $this->xLibraryManager->setQuestionLibrary($sName);
        }
    }

    /**
     * @inheritDoc
     */
    public function setResponse(Response $xResponse)
    {
        parent::setResponse($xResponse);

        // Hack the setResponse() method, to set the default libraries on each access to this plugin.
        $this->xLibraryManager->setNextLibrary('');
    }

    /**
     * Set the library to use for the next call.
     *
     * @param string $sLibrary The name of the library
     *
     * @return DialogPlugin
     */
    public function with(string $sLibrary): DialogPlugin
    {
        $this->xLibraryManager->setNextLibrary($sLibrary);
        return $this;
    }

    /**
     * Get the library adapter to use for modals.
     *
     * @return ModalInterface|null
     */
    protected function getModalLibrary(): ?ModalInterface
    {
        $xLibrary = $this->xLibraryManager->getModalLibrary();
        $xLibrary->setResponse($this->xResponse);
        return $xLibrary;
    }

    /**
     * Get the library adapter to use for messages.
     *
     * @return MessageInterface|null
     */
    protected function getMessageLibrary(): ?MessageInterface
    {
        $xLibrary = $this->xLibraryManager->getMessageLibrary();
        $xLibrary->setResponse($this->xResponse);
        // By default, always add commands to the response
        $xLibrary->setReturnCode(false);
        return $xLibrary;
    }

    /**
     * @return array
     */
    private function getLibraries(): array
    {
        if($this->aLibraries === null)
        {
            $this->aLibraries = $this->xLibraryManager->getLibraries();
        }
        return $this->aLibraries;
    }

    /**
     * @inheritDoc
     */
    public function getJs(): string
    {
        return array_reduce($this->getLibraries(), function($sCode, $xLibrary) {
            return $sCode . $xLibrary->getJs() . "\n\n";
        }, '');
    }

    /**
     * @inheritDoc
     */
    public function getCss(): string
    {
        return array_reduce($this->getLibraries(), function($sCode, $xLibrary) {
            return $sCode . trim($xLibrary->getCss()) . "\n\n";
        }, '');
    }

    /**
     * @inheritDoc
     * @throws SetupException
     */
    public function getScript(): string
    {
        // The default scripts need to be set in the js code.
        $this->setDefaultLibraries();

        return array_reduce($this->getLibraries(), function($sCode, $xLibrary) {
            return $sCode . trim($xLibrary->getScript()) . "\n\n";
        }, "jaxon.dialogs = {};\n");
    }

    /**
     * @inheritDoc
     */
    public function getReadyScript(): string
    {
        return array_reduce($this->getLibraries(), function($sCode, $xLibrary) {
            return $sCode . trim($xLibrary->getReadyScript()) . "\n\n";
        }, '');
    }

    /**
     * @inheritDoc
     */
    public function show(string $sTitle, string $sContent, array $aButtons = [], array $aOptions = [])
    {
        $this->getModalLibrary()->show($sTitle, $sContent, $aButtons, $aOptions);
    }

    /**
     * @inheritDoc
     */
    public function hide()
    {
        $this->getModalLibrary()->hide();
    }

    /**
     * @inheritDoc
     */
    public function success(string $sMessage, string $sTitle = ''): string
    {
        return $this->getMessageLibrary()->success($sMessage, $sTitle);
    }

    /**
     * @inheritDoc
     */
    public function info(string $sMessage, string $sTitle = ''): string
    {
        return $this->getMessageLibrary()->info($sMessage, $sTitle);
    }

    /**
     * @inheritDoc
     */
    public function warning(string $sMessage, string $sTitle = ''): string
    {
        return $this->getMessageLibrary()->warning($sMessage, $sTitle);
    }

    /**
     * @inheritDoc
     */
    public function error(string $sMessage, string $sTitle = ''): string
    {
        return $this->getMessageLibrary()->error($sMessage, $sTitle);
    }
}