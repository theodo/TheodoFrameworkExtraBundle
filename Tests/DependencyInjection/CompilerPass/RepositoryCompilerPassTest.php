<?php

namespace Theodo\Bundle\FrameworkExtraBundle\Tests\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Definition;
use Theodo\Bundle\FrameworkExtraBundle\DependencyInjection\Compiler\RepositoryCompilerPass;

/**
 * RepositoryCompilerPassTest
 * 
 * @author Benjamin Grandfond <benjaming@theodo.fr>
 */
class RepositoryCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldRegisterEveryRepositoryAsAService()
    {
        $driver = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\MappingDriver', array('loadMetadataForClass', 'getAllClassNames', 'isTransient'));
        $driver->expects($this->once())
            ->method('getAllClassNames')
            ->will($this->returnValue(array('Foo\Entity\Bar')))
        ;

        $config = $this->getMock('Doctrine\ORM\Configuration', array('getMetadataDriverImpl'));
        $config->expects($this->once())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($driver))
        ;

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getConfiguration', 'getClassMetadata'))
            ->getMock()
        ;
        $em->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($config))
        ;
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata))
        ;

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('getServiceIds', 'get', 'setDefinition'))
            ->getMock()
        ;

        $emId = 'doctrine.orm.default_entity_manager';

        $container->expects($this->once())
            ->method('getServiceIds')
            ->will($this->returnValue(array($emId)))
        ;

        $container->expects($this->exactly(2))
            ->method('get')
            ->with($emId)
            ->will($this->returnValue($em))
        ;

        $container->expects($this->atLeastOnce())
            ->method('setDefinition')
            ->with(
                $this->isType('string'),
                $this->isInstanceOf('Symfony\Component\DependencyInjection\Definition')
            )
        ;

        $compilerPass = new RepositoryCompilerPass();
        $compilerPass->process($container);
    }
}
 