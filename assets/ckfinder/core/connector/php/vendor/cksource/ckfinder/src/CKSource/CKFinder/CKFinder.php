<?php

/*
 * CKFinder
 * ========
 * http://cksource.com/ckfinder
 * Copyright (C) 2007-2015, CKSource - Frederico Knabben. All rights reserved.
 *
 * The software, this file and its contents are subject to the CKFinder
 * License. Please read the license.txt file before using, installing, copying,
 * modifying or distribute this file or part of its contents. The contents of
 * this file is part of the Source Code of CKFinder.
 */

namespace CKSource\CKFinder;

use CKSource\CKFinder\Acl\Acl;
use CKSource\CKFinder\Acl\User\SessionRoleContext;
use CKSource\CKFinder\Backend\BackendFactory;
use CKSource\CKFinder\Cache\CacheManager;
use CKSource\CKFinder\Cache\Adapter\BackendAdapter;
use CKSource\CKFinder\Event\AfterCommandEvent;
use CKSource\CKFinder\Event\CKFinderEvent;
use CKSource\CKFinder\Exception\CKFinderException;
use CKSource\CKFinder\Exception\InvalidPluginException;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use CKSource\CKFinder\Filesystem\Path;
use CKSource\CKFinder\Plugin\PluginInterface;
use CKSource\CKFinder\ResourceType\ResourceTypeFactory;
use CKSource\CKFinder\Response\JsonResponse;
use CKSource\CKFinder\ResizedImage\ResizedImageRepository;
use CKSource\CKFinder\Thumbnail\ThumbnailRepository;
use League\Flysystem\Adapter\Local as LocalFSAdapter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;


/**
 * CKFinder main class
 *
 * It's based on <a href="http://pimple.sensiolabs.org/">Pimple</a>
 * so it serves also as a dependency injection container
 *
 * @copyright 2015 CKSource - Frederico Knabben
 */
class CKFinder extends Container implements HttpKernelInterface
{
    const VERSION = '3.0.0';

    const COMMANDS_NAMESPACE = 'CKSource\\CKFinder\\Command\\';
    const PLUGINS_NAMESPACE = 'CKSource\\CKFinder\\Plugin\\';

    const CHARS = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    protected $plugins = array();

    protected $booted = false;

    /**
     * Constructor
     *
     * @param array|string $config an array containing configuration options or a path
     *                             to configuration file
     *
     * @see config.php
     */
    public function __construct($config)
    {
        parent::__construct();


        $app = $this;

        $this['config'] = function () use ($config) {
            return new Config($config);
        };

        $this['exception_handler'] = function () use ($app) {
            return new ExceptionHandler($app['translator'], $app['debug'], $app['logger']);
        };

        $this['dispatcher'] = function () use ($app) {
            $eventDispatcher = new EventDispatcher();

            $eventDispatcher->addListener(KernelEvents::VIEW, array($this, 'createResponse'), -512);
            $eventDispatcher->addListener(KernelEvents::RESPONSE, array($this, 'afterCommand'), -512);
            $eventDispatcher->addSubscriber($app['exception_handler']);

            return $eventDispatcher;
        };

        $this['resolver'] = function () use ($app) {
            $commandResolver = new CommandResolver($app);
            $commandResolver->setCommandsNamespace(CKFinder::COMMANDS_NAMESPACE);
            $commandResolver->setPluginsNamespace(CKFinder::PLUGINS_NAMESPACE);

            return $commandResolver;
        };

        $this['request_stack'] = function () {
            return new RequestStack();
        };

        $this['working_folder'] = function () use ($app) {
            $workingFolder = new WorkingFolder($app);

            $this['dispatcher']->addSubscriber($workingFolder);

            return $workingFolder;
        };

        $this['kernel'] = function () use ($app) {
            return new HttpKernel($app['dispatcher'], $app['resolver'], $app['request_stack']);
        };

        $this['acl'] = function () use ($app) {
            $config = $app['config'];

            $roleContext = new SessionRoleContext($config->get('roleSessionVar'));

            $acl = new Acl($roleContext);
            $acl->setRules($config->get('accessControl'));

            return $acl;
        };

        $this['backend_factory'] = function () use ($app) {
            return new BackendFactory($app['acl'], $app['config']);
        };

        $this['resource_type_factory'] = function () use ($app) {
            return new ResourceTypeFactory($app);
        };

        $this['thumbnail_repository'] = function () use ($app) {
            return new ThumbnailRepository($app);
        };

        $this['resized_image_repository'] = function () use ($app) {
            return new ResizedImageRepository($app);
        };

        $this['cache'] = function () use ($app) {
            $cacheBackend = $app['backend_factory']->getPrivateDirBackend('cache');
            $cacheDir = $app['config']->getPrivateDirPath('cache') . '/data';

            return new CacheManager(new BackendAdapter($cacheBackend, $cacheDir));
        };

        $this['translator'] = function () {
            return new Translator();
        };

        $this['debug'] = $app['config']->get('debug');

        $this['logger'] = function () use ($app) {
            $logger = new Logger('CKFinder');

            if ($app['config']->isDebugLoggerEnabled('firephp')) {
                $logger->pushHandler(new FirePHPHandler());
            }

            if ($app['config']->isDebugLoggerEnabled('error_log')) {
                $logger->pushHandler(new ErrorLogHandler());
            }

            return $logger;
        };
    }

    /**
     * Method used to check authentication
     */
    public function checkAuth()
    {
        $authenticationCallback = $this['config']->get('authentication');

        if (!call_user_func($authenticationCallback)) {
            ini_set('display_errors', 0);
            throw new CKFinderException('CKFinder is disabled', Error::CONNECTOR_DISABLED);
        }
    }

    /**
     * Create response
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function createResponse(GetResponseForControllerResultEvent $event)
    {
        /* @var $dispatcher EventDispatcher */
        $dispatcher = $this['dispatcher'];

        $commandName = $event->getRequest()->get('command');
        $eventName = CKFinderEvent::CREATE_RESPONSE_PREFIX . lcfirst($commandName);
        $dispatcher->dispatch($eventName, $event);

        $controllerResult = $event->getControllerResult();
        $event->setResponse(JsonResponse::create($controllerResult));
    }

    /**
     * Fires afterCommand events
     *
     * @param FilterResponseEvent $event
     *
     * @return \Symfony\Component\HttpFoundation\Response|static
     */
    public function afterCommand(FilterResponseEvent $event)
    {
        /* @var $dispatcher EventDispatcher */
        $dispatcher = $this['dispatcher'];

        $commandName = $event->getRequest()->get('command');
        $eventName = CKFinderEvent::AFTER_COMMAND_PREFIX . lcfirst($commandName);
        $afterCommandEvent = new AfterCommandEvent($this, $commandName, $event->getResponse());
        $dispatcher->dispatch($eventName, $afterCommandEvent);

        $event->setResponse($afterCommandEvent->getResponse());
    }

    /**
     * Registers listener for an event
     *
     * @param string   $eventName event name
     * @param callable $listener  listener callable
     * @param int      $priority  priority
     */
    public function on($eventName, $listener, $priority = 0)
    {
        /* @var $dispatcher EventDispatcher */
        $dispatcher = $this['dispatcher'];

        $dispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * Main method used to handle request by CKFinder
     *
     * @param Request $request request object
     */
    public function run(Request $request = null)
    {
        $request = null === $request ? Request::createFromGlobals() : $request;

        /* @var $kernel HttpKernel */
        $kernel = $this['kernel'];

        $response = $this->handle($request);
        $response->send();

        $kernel->terminate($request, $response);
    }

    /**
     * @return BackendFactory
     */
    public function getBackendFactory()
    {
        return $this['backend_factory'];
    }

    /**
     * @return Acl
     */
    public function getAcl()
    {
        return $this['acl'];
    }

    /**
     * @return WorkingFolder
     */
    public function getWorkingFolder()
    {
        return $this['working_folder'];
    }

    /**
     * Shorthand for debugging using defined logger
     *
     * @param string $message
     * @param array  $context
     */
    public function debug($message, array $context = array())
    {
        $logger = $this['logger'];

        if ($logger) {
            $logger->debug($message, $context);
        }
    }

    /**
     * Registers plugins defined in configuration file
     *
     * @throws \LogicException in case if plugin was not found or is invalid
     */
    protected function registerPlugins()
    {
        $pluginsEntries = $this['config']->get('plugins');
        $pluginsDirectory = $this['config']->get('pluginsDirectory');

        foreach ($pluginsEntries as $pluginInfo) {
            if (is_array($pluginInfo)) {
                $pluginName = ucfirst($pluginInfo['name']);
                if (isset($pluginInfo['path'])) {
                    require_once $pluginInfo['path'];
                }
            } else {
                $pluginName = ucfirst($pluginInfo);
            }

            $pluginPath = Path::combine($pluginsDirectory, $pluginName, $pluginName . '.php');

            if (file_exists($pluginPath) && is_readable($pluginPath)) {
                require_once $pluginPath;
            }

            $pluginClassName = CKFinder::PLUGINS_NAMESPACE . $pluginName . '\\' . $pluginName;

            if (!class_exists($pluginClassName)) {
                throw new InvalidPluginException(sprintf('CKFinder plugin "%s" not found (%s)', $pluginName, $pluginClassName), array($pluginName));
            }

            $pluginObject = new $pluginClassName($this);

            if ($pluginObject instanceof PluginInterface) {
                $this->registerPlugin($pluginObject);
            } else {
                throw new InvalidPluginException(sprintf('CKFinder plugin class must implement %sPluginInterface', CKFinder::PLUGINS_NAMESPACE), array($pluginName));
            }
        }
    }

    /**
     * Registers plugin
     *
     * @param PluginInterface $plugin
     */
    public function registerPlugin(PluginInterface $plugin)
    {
        $plugin->setContainer($this);

        $pluginNameParts = explode('\\', get_class($plugin));
        $pluginName = end($pluginNameParts);

        $this['config']->extend($pluginName, $plugin->getDefaultConfig());

        if ($plugin instanceof EventSubscriberInterface) {
            $this['dispatcher']->addSubscriber($plugin);
        }

        $this->plugins[$pluginName] = $plugin;
    }

    /**
     * Returns an array containing all registered plugins
     *
     * @return array array of PluginInterface-s
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Returns plugin by name
     *
     * @param string $name plugin name
     *
     * @return null|PluginInterface
     *
     */
    public function getPlugin($name)
    {
        if (isset($this->plugins[$name])) {
            return $this->plugins[$name];
        }

        return null;
    }

    /**
     * Checks PHP requirements
     *
     * @throws CKFinderException
     */
    protected function checkRequirements()
    {
        $errorMessage = 'The PHP installation does not meet the minimum system requirements for CKFinder. %s Please refer to CKFinder documentation for more details.';

        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            throw new CKFinderException(sprintf($errorMessage, 'Your PHP version is too old. CKFinder 3.x requires PHP 5.4+.'), Error::CUSTOM_ERROR);
        }

        $missingExtensions = array();

        if (!function_exists('gd_info')) {
            $missingExtensions[] = 'GD';
        }

        if (!function_exists('finfo_file')) {
            $missingExtensions[] = 'Fileinfo';
        }

        if (!empty($missingExtensions)) {
            throw new CKFinderException(sprintf($errorMessage, 'Missing PHP extensions: ' . implode(', ', $missingExtensions) . '.'), Error::CUSTOM_ERROR);
        }
    }

    /**
     * Prepares application environment before Request is dispatched
     */
    public function boot()
    {
        if (!$this->booted) {
            $this->booted = true;

            $this->checkRequirements();

            if ($this['config']->get('debug') && $this['config']->isDebugLoggerEnabled('ckfinder_log')) {
                $this->registerStreamLogger();
            }

            $this->checkAuth();
            $this->registerPlugins();
        }
    }

    /**
     * Registers stream handler for errors logging
     */
    public function registerStreamLogger()
    {
        $app = $this;

        /* @var $logsBackend \CKSource\CKFinder\Backend\Backend */
        $logsBackend = $app['backend_factory']->getPrivateDirBackend('logs');

        $adapter = $logsBackend->getAdapter();

        if ($adapter instanceof LocalFSAdapter) {
            $logsDir = $app['config']->getPrivateDirPath('logs');

            $errorLogPath = Path::combine($logsDir, 'error.log');

            $logPath = $adapter->applyPathPrefix($errorLogPath);

            $app['logger']->pushHandler(new StreamHandler($logPath));
        }

    }

    /**
     * @param Request $request
     * @param int     $type
     * @param bool    $catch
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        /* @var $kernel HttpKernel */
        $kernel = $this['kernel'];

        // Handle early exceptions
        if (!$this->booted) {
            try {
                $this->boot();
            } catch (\Exception $e) {
                $this['request_stack']->push($request);
                $kernel->terminateWithException($e);
                exit;
            }
        }

        return $kernel->handle($request, $type, $catch);
    }
}
