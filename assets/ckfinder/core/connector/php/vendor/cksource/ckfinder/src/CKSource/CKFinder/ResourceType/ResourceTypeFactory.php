<?php

namespace CKSource\CKFinder\ResourceType;

use CKSource\CKFinder\CKFinder;
use Pimple\Container;

class ResourceTypeFactory extends Container
{
    protected $app;
    protected $config;
    protected $backendFactory;
    protected $thumbnailRepository;

    public function __construct(CKFinder $app)
    {
        $this->app = $app;
        $this->config = $app['config'];
        $this->backendFactory = $app['backend_factory'];
        $this->thumbnailRepository = $app['thumbnail_repository'];
        $this->resizedImageRepository = $app['resized_image_repository'];
    }

    /**
     * Returns resource type object with given name
     *
     * @param string $name resource type name
     *
     * @return ResourceType
     */
    public function getResourceType($name)
    {
        if (!$this->offsetExists($name)) {
            $resourceTypeConfig = $this->config->getResourceTypeNode($name);
            $backend = $this->backendFactory->getBackend($resourceTypeConfig['backend']);

            $this[$name] = new ResourceType($name, $resourceTypeConfig, $backend, $this->thumbnailRepository, $this->resizedImageRepository);
        }

        return $this[$name];
    }
}
