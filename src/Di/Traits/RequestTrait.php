<?php

namespace Jaxon\Di\Traits;

use Jaxon\Config\ConfigManager;
use Jaxon\Di\Container;
use Jaxon\Plugin\Manager\PluginManager;
use Jaxon\Plugin\Request\CallableClass\CallableRegistry;
use Jaxon\Plugin\Response\DataBag\DataBagPlugin;
use Jaxon\Request\Call\Paginator;
use Jaxon\Request\Factory\Factory;
use Jaxon\Request\Factory\ParameterFactory;
use Jaxon\Request\Factory\RequestFactory;
use Jaxon\Request\Handler\CallbackManager;
use Jaxon\Request\Handler\ParameterReader;
use Jaxon\Request\Handler\RequestHandler;
use Jaxon\Request\Handler\UploadHandler;
use Jaxon\Request\Upload\NameGeneratorInterface;
use Jaxon\Request\Upload\UploadManager;
use Jaxon\Request\Validator;
use Jaxon\Response\Manager\ResponseManager;
use Jaxon\Ui\Dialog\Library\DialogLibraryManager;
use Jaxon\Utils\Http\UriDetector;
use Jaxon\Utils\Translation\Translator;

use function bin2hex;
use function random_bytes;

trait RequestTrait
{
    /**
     * Register the values into the container
     *
     * @return void
     */
    private function registerRequests()
    {
        // The parameter reader
        $this->set(ParameterReader::class, function($c) {
            return new ParameterReader($c->g(Container::class), $c->g(ConfigManager::class),
                $c->g(Translator::class), $c->g(UriDetector::class));
        });
        // Callback Manager
        $this->set(CallbackManager::class, function() {
            return new CallbackManager();
        });
        // Request Handler
        $this->set(RequestHandler::class, function($c) {
            return new RequestHandler($c->g(Container::class), $c->g(PluginManager::class),
                $c->g(ResponseManager::class), $c->g(CallbackManager::class),
                $c->g(UploadHandler::class), $c->g(DataBagPlugin::class));
        });
        // Upload file and dir name generator
        $this->set(NameGeneratorInterface::class, function() {
            return new class implements NameGeneratorInterface
            {
                public function random(int $nLength): string
                {
                    return bin2hex(random_bytes((int)($nLength / 2)));
                }
            };
        });
        // File upload manager
        $this->set(UploadManager::class, function($c) {
            return new UploadManager($c->g(NameGeneratorInterface::class), $c->g(ConfigManager::class),
                $c->g(Validator::class), $c->g(Translator::class));
        });
        // File upload plugin
        $this->set(UploadHandler::class, function($c) {
            return !$c->g(ConfigManager::class)->getOption('core.upload.enabled') ? null :
                new UploadHandler($c->g(Container::class), $c->g(ResponseManager::class), $c->g(Translator::class));
        });
        // Request Factory
        $this->set(Factory::class, function($c) {
            return new Factory($c->g(CallableRegistry::class), $c->g(RequestFactory::class),
                $c->g(ParameterFactory::class));
        });
        // Factory for requests to functions
        $this->set(RequestFactory::class, function($c) {
            $sPrefix = $c->g(ConfigManager::class)->getOption('core.prefix.function');
            return new RequestFactory($sPrefix, $c->g(DialogLibraryManager::class), $c->g(Paginator::class));
        });
        // Parameter Factory
        $this->set(ParameterFactory::class, function() {
            return new ParameterFactory();
        });
    }

    /**
     * Get the factory
     *
     * @return Factory
     */
    public function getFactory(): Factory
    {
        return $this->g(Factory::class);
    }

    /**
     * Get the request handler
     *
     * @return RequestHandler
     */
    public function getRequestHandler(): RequestHandler
    {
        return $this->g(RequestHandler::class);
    }

    /**
     * Get the upload handler
     *
     * @return UploadHandler|null
     */
    public function getUploadHandler(): ?UploadHandler
    {
        return $this->g(UploadHandler::class);
    }

    /**
     * Get the upload manager
     *
     * @return UploadManager
     */
    public function getUploadManager(): UploadManager
    {
        return $this->g(UploadManager::class);
    }

    /**
     * Get the callback manager
     *
     * @return CallbackManager
     */
    public function getCallbackManager(): CallbackManager
    {
        return $this->g(CallbackManager::class);
    }

    /**
     * Get the parameter reader
     *
     * @return ParameterReader
     */
    public function getParameterReader(): ParameterReader
    {
        return $this->g(ParameterReader::class);
    }
}
