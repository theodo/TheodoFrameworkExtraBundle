<?php

namespace Theodo\Bundle\FrameworkExtraBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Theodo\Bundle\FrameworkExtraBundle\DependencyInjection\Compiler\RepositoryCompilerPass;

/**
 * TheodoFrameworkExtraBundle
 * 
 * @author Benjamin Grandfond <benjaming@theodo.fr>
 */
class TheodoFrameworkExtraBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RepositoryCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }

}
