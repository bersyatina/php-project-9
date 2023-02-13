<?php

namespace Hexlet\Code;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

class ConfigureContainer extends \DI\Bridge\Slim\Bridge
{
    protected function configureContainer(ContainerBuilder $builder)
    {
        $definitions = [

            \Slim\Views\Twig::class => function (ContainerInterface $c) {
                $twig = new \Slim\Views\Twig('templates', [
                    'cache' => false
                ]);

                $twig->addExtension(new \Slim\Views\TwigExtension(
                    $c->get('router'),
                    $c->get('request')->getUri()
                ));

                return $twig;
            },

        ];

        $builder->addDefinitions($definitions);
    }
}
