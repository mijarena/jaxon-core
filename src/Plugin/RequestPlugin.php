<?php

/**
 * RequestPlugin.php - Jaxon Request interface
 *
 * Interface for Jaxon Request plugins.
 *
 * Request plugins handle the registration, client script generation and processing of jaxon enabled requests.
 * Each plugin should have a unique signature for both the registration and processing of requests.
 * During registration, the user will specify a type which will allow the plugin to detect and handle it.
 * During client script generation, the plugin will generate a <jaxon.request> stub with the prescribed call options and request signature.
 * During request processing, the plugin will detect the signature generated previously and process the request accordingly.
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

namespace Jaxon\Plugin;

use Jaxon\Request\Target;

abstract class RequestPlugin extends Plugin
{
    /**
     * Check if the provided options are correct, and convert them into an array.
     *
     * @param string $sCallable
     * @param mixed $xOptions
     *
     * @return array
     */
    abstract public function checkOptions(string $sCallable, $xOptions): array;

    /**
     * Register a function, an event or an object.
     *
     * Called by the <Jaxon\Plugin\PluginManager> when a user script
     * when a function or callable object is to be registered.
     * Additional plugins may support other registration types.
     *
     * @param string $sType    The type of request handler being registered
     * @param string $sCallable    The callable entity being registered
     * @param array $aOptions    The associated options
     *
     * @return bool
     */
    public function register(string $sType, string $sCallable, array $aOptions): bool
    {
        return false;
    }

    /**
     * Get the target function or class and method
     *
     * @return Target|null
     */
    public function getTarget(): ?Target
    {
        return null;
    }

    /**
     * Check if this plugin can process the current request
     *
     * Called by the <Jaxon\Plugin\PluginManager> when a request has been received to determine
     * if the request is destinated to this request plugin.
     *
     * @return bool
     */
    abstract public function canProcessRequest(): bool;

    /**
     * Process the current request
     *
     * Called by the <Jaxon\Plugin\PluginManager> when a request is being processed.
     * This will only occur when <Jaxon> has determined that the current request
     * is a valid (registered) jaxon enabled function via <jaxon->canProcessRequest>.
     *
     * @return bool
     */
    abstract public function processRequest(): bool;
}