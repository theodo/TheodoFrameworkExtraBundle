<?php

namespace Theodo\Bundle\FrameworkExtraBundle\Test;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\Tools\SchemaTool;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

/**
 * WebTestCase
 *
 * @author Benjamin Grandfond <benjaming@theodo.fr>
 */
class WebTestCase extends BaseWebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createClient(array $options = array(), array $server = array())
    {
        $client = parent::createClient($options, $server);

        self::generateSchema();

        return $client;
    }

    /**
     * Generates the schema to use on the test environment.
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected static function generateSchema()
    {
        if (!static::$kernel instanceof \Symfony\Component\HttpKernel\KernelInterface) {
            static::$kernel = static::createKernel();
        }

        /**
         * @var \Doctrine\ORM\EntityManager
         */
        $em = static::$kernel->getContainer()->get('doctrine.orm.entity_manager');

        // Get the metadata of the application to create the schema.
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        if (!empty($metadata)) {
            // Create SchemaTool
            $tool = new SchemaTool($em);
            $tool->dropDatabase();
            $tool->createSchema($metadata);
        } else {
            throw new SchemaException('No Metadata Classes to process.');
        }
    }

    /**
     * Load some fixtures.
     *
     * @param null $paths
     * @throws \InvalidArgumentException
     */
    protected static function loadFixtures($paths = null)
    {
        if (!static::$kernel instanceof \Symfony\Component\HttpKernel\KernelInterface) {
            static::$kernel = static::createKernel();
        }

        if (null != $paths) {
            $paths = is_array($paths) ? $paths : array($paths);
        } else {
            $paths = array();
            foreach (static::$kernel->getBundles() as $bundle) {
                $paths[] = $bundle->getPath().'/DataFixtures/ORM';
            }
        }

        $loader = new DataFixturesLoader(static::$kernel->getContainer());
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }
        $fixtures = $loader->getFixtures();
        if (!$fixtures) {
            throw new InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths))
            );
        }

        $em = static::$kernel->getContainer()->get('doctrine.orm.entity_manager');

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($fixtures);
    }
}