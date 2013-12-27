<?php

namespace Theodo\Bundle\FrameworkExtraBundle\DependencyInjection\Compiler;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * RepositoryCompilerPass
 * 
 * @author Benjamin Grandfond <benjaming@theodo.fr>
 */
class RepositoryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getServiceIds() as $service) {
            // Look for entity managers
            if (strpos($service, '_entity_manager') == false) {
                continue;
            }

            /** @var \Doctrine\ORM\EntityManager */
            $em = $container->get($service);

            if (!is_a($em, 'Doctrine\ORM\EntityManager')) {
                continue;
            }

            $entities = $em->getConfiguration()
                ->getMetadataDriverImpl()
                ->getAllClassNames();

            foreach ($entities as $entity) {
                $this->registerRepository($entity, $service, $container);
            }
        }
    }

    /**
     * @param $entityClassName
     * @param $emServiceId
     * @param ContainerBuilder $container
     */
    private function registerRepository($entityClassName, $emServiceId, ContainerBuilder $container)
    {
        $em = $container->get($emServiceId);
        $metadata = $em->getClassMetadata($entityClassName);
        $repositoryClassName = $metadata->customRepositoryClassName ?: 'Doctrine\ORM\EntityRepository';

        $container->setDefinition(
            $this->createId($repositoryClassName),
            $this->createDefinition($repositoryClassName, $entityClassName, $emServiceId));
    }

    /**
     * Generate an id for the repository service
     *
     * @param $repositoryClassName
     * @return string
     *
     * @todo : add the bundle name before
     */
    private function createId($repositoryClassName)
    {
        $parts = explode('\\', $repositoryClassName);
        $className = end($parts);

        return 'repository.' . Container::underscore($className);
    }

    /**
     * Create a service definition for a repository class.
     *
     * @param  $repository The repository class name
     * @param  $entity     The entity class name
     * @param  $manager    The entity manager service id
     * @return Definition
     */
    private function createDefinition($repository, $entity, $manager)
    {
        $definition = new Definition($repository);
        $definition->setFactoryService($manager);
        $definition->setFactoryMethod("getRepository");
        $definition->addArgument($entity);

        return $definition;
    }
}
