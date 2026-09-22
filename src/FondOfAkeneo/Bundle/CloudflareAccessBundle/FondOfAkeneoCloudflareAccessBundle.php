<?php

namespace FondOfAkeneo\Bundle\CloudflareAccessBundle;

use FondOfAkeneo\Bundle\CloudflareAccessBundle\DependencyInjection\FondOfAkeneoCloudflareAccessExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FondOfAkeneoCloudflareAccessBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new FondOfAkeneoCloudflareAccessExtension();
    }
}
