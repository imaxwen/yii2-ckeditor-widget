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

namespace CKSource\CKFinder\Backend;

use CKSource\CKFinder\Acl\AclInterface;
use CKSource\CKFinder\Backend\Adapter\Local as LocalFilesystemAdapter;
use CKSource\CKFinder\Backend\Adapter\Dropbox as DropboxAdapter;
use CKSource\CKFinder\Config;
use CKSource\CKFinder\Filesystem\Path;
use Pimple\Container;
use CKSource\CKFinder\Backend\Adapter\Ftp as FtpAdapter;
use League\Flysystem\AwsS3v2\AwsS3Adapter;
use Dropbox\Client as DropboxClient;
use Aws\S3\S3Client;

/**
 * BackendFactory class
 *
 * BackendFactory responsible for backend adapters instantiation.
 *
 * @copyright 2015 CKSource - Frederico Knabben
 */
class BackendFactory extends Container
{
    /**
     * Acl
     *
     * @var AclInterface $acl
     */
    protected $acl;

    /**
     * Config
     *
     * @var Config $config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param AclInterface $acl
     * @param Config       $config
     */
    public function __construct(AclInterface $acl, Config $config)
    {
        $this->acl = $acl;
        $this->config = $config;

        $backendsConfig = $config->get('backends');

        foreach ($backendsConfig as $backendConfig) {
            switch ($backendConfig['adapter']) {
                case 'local':
                    $this[$backendConfig['name']] = function () use ($backendConfig) {
                        return new Backend($backendConfig, $this->acl, $this->config, new LocalFilesystemAdapter($backendConfig));
                    };
                    break;
                case 'ftp':
                    $this[$backendConfig['name']] = function () use ($backendConfig) {

                        $configurable = array('host', 'port', 'username', 'password', 'ssl', 'timeout', 'root', 'permPrivate', 'permPublic', 'passive');

                        $config = array_intersect_key($backendConfig, array_flip($configurable));

                        return new Backend($backendConfig, $this->acl, $this->config, new FtpAdapter($config));
                    };
                    break;
                case 'dropbox':
                    $this[$backendConfig['name']] = function () use ($backendConfig) {

                        $client = new DropboxClient($backendConfig['token'], $backendConfig['username']);

                        return new Backend($backendConfig, $this->acl, $this->config, new DropboxAdapter($client, $backendConfig));
                    };
                    break;
                case 's3':
                    $this[$backendConfig['name']] = function () use ($backendConfig) {
                        $clientConfig = array(
                            'key'    => $backendConfig['key'],
                            'secret' => $backendConfig['secret'],
                        );

                        if (isset($backendConfig['region'])) {
                            $clientConfig['region'] = $backendConfig['region'];
                        }

                        $client = S3Client::factory($clientConfig);

                        $config = array(
                            'visibility' => isset($backendConfig['visibility']) ? $backendConfig['visibility'] : 'private'
                        );

                        $prefix = isset($backendConfig['root']) ? $backendConfig['root'] : null;

                        return new Backend($backendConfig, $this->acl, $this->config, new AwsS3Adapter($client, $backendConfig['bucket'], $prefix), $config);
                    };
                    break;
            }
        }
    }

    /**
     * Returns backend object by name
     *
     * @param string $backendName
     *
     * @return Backend
     *
     * @throws \InvalidArgumentException in case if backend with given name is not defined
     */
    public function getBackend($backendName)
    {
        if (!isset($this[$backendName])) {
            throw new \InvalidArgumentException(sprintf('Backend %s not found. Please check configuration file.', $backendName));
        }

        return $this[$backendName];
    }

    /**
     * Returns backend object for given private directory identifier
     *
     * @param string $privateDirIdentifier
     *
     * @return Backend
     */
    public function getPrivateDirBackend($privateDirIdentifier)
    {
        $privateDirConfig = $this->config->get('privateDir');

        if (!array_key_exists($privateDirIdentifier, $privateDirConfig)) {
            throw new \InvalidArgumentException(sprintf('Private dir with identifier %s not found. Please check configuration file.', $privateDirIdentifier));
        }

        $privateDir = $privateDirConfig[$privateDirIdentifier];

        $backend = null;

        if (is_array($privateDir) && array_key_exists('backend', $privateDir)) {
            $backend = $this->getBackend($privateDir['backend']);
        } else {
            $backend = $this->getBackend($privateDirConfig['backend']);
        }

        // Create a default .htaccess to disable access to current private directory
        $privateDirPath = $this->config->getPrivateDirPath($privateDirIdentifier);
        $htaccessPath = Path::combine($privateDirPath, '.htaccess');
        if (!$backend->has($htaccessPath)) {
            $backend->write($htaccessPath, "Order Deny,Allow\nDeny from all\n");
        }

        return $backend;
    }
}
