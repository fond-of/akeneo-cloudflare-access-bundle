<?php

namespace FondOfAkeneo\Bundle\CloudflareAccessBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class FondOfAkeneoCloudflareAccessExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('fond_of_akeneo_cloudflare_access.team_domain', $config['team_domain']);
        $container->setParameter('fond_of_akeneo_cloudflare_access.application_audience', $config['application_audience']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}
