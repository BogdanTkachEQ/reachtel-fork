<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Container;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class ContainerAccessor
 */
class ContainerAccessor
{
    const PROJECT_ROOT_DIR = __DIR__ . '/../../';
    const SERVICES_DEFINITION_DIR = __DIR__ . '/../Config/Definitions';

    /** @var ContainerBuilder */
    private static $containerBuilder;

    /**
     * @return ContainerBuilder
     */
    public static function getContainer()
    {
        if (is_null(static::$containerBuilder)) {
            static::$containerBuilder = new ContainerBuilder();
            static::$containerBuilder->setParameter('project_root', static::PROJECT_ROOT_DIR);
            static::$containerBuilder->setParameter('is_dev', api_misc_is_test_environment());
            $loader = new YamlFileLoader(
                static::$containerBuilder,
                new FileLocator(static::SERVICES_DEFINITION_DIR)
            );

            $loader->load('services.yml');
            static::$containerBuilder->compile();
        }

        return static::$containerBuilder;
    }
}
