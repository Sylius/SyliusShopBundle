<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Sylius\Bundle\AdminBundle\SyliusAdminBundle;

return static function (ContainerConfigurator $container) {
    if (class_exists(SyliusAdminBundle::class)) {
        $container->import('@SyliusShopBundle/Resources/config/app/integration/twig_hooks/sylius_admin/**/*.yaml');
    }
};
