<?php

namespace PhotoContainer\PhotoContainer\Infrastructure\Web\Slim;

use DI\Bridge\Slim\App;
use DI\ContainerBuilder;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;

class SlimPHPDI extends App
{
    protected function configureContainer(ContainerBuilder $builder)
    {
        if (getenv('ENVIRONMENT') === 'prod') {
            $cache = new ApcuCache();
        } else {
            $cache = new ArrayCache();
        }

        $cache->setNamespace('PhotoContainer');
        $builder->setDefinitionCache($cache);

        $builder->addDefinitions('slim_config.php');
        $builder->addDefinitions('config.php');
        $builder->addDefinitions('../src/Application/Resources/services.php');

        return $builder;
    }
}