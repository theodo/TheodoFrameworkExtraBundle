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
     * Creates Kernel if undefined
     */
    private static function buildKernel()
    {
        if (!static::$kernel instanceof \Symfony\Component\HttpKernel\KernelInterface) {
            static::$kernel = static::createKernel();
        }
    }

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
     * @param bool $purgeOnly When set to true, only purge the database, else drop it and recreate schema.
     *
     * @throws SchemaException
     */
    protected static function generateSchema($purgeOnly=false)
    {
        static::buildKernel();

        /**
         * @var \Doctrine\ORM\EntityManager $em
         */
        $em = static::$kernel->getContainer()->get('doctrine.orm.entity_manager');

        if ($purgeOnly) {
            // Purge and truncate (reset the id to start from 1) the database
            $purger = new ORMPurger($em);
            $purger->getPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
            $purger->purge();
        } else {
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
    }

    /**
     * If paths is null, get the fixtures from all bundles loaded with the kernel.
     *
     * @param null $paths
     *
     * @return array
     */
    private static function getFixturesPaths($paths = null)
    {
        if (null != $paths) {
            $paths = is_array($paths) ? $paths : array($paths);
        } else {
            $paths = array();
            foreach (static::$kernel->getBundles() as $bundle) {
                $paths[] = $bundle->getPath().'/DataFixtures/ORM';
            }
        }

        return $paths;
    }

    /**
     * Load some fixtures.
     *
     * @param null $paths
     *
     * @throws \InvalidArgumentException
     */
    protected static function loadFixtures($paths = null)
    {
        static::buildKernel();

        $paths = static::getFixturesPaths($paths);

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

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = static::$kernel->getContainer()->get('doctrine.orm.entity_manager');

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($fixtures);
    }

    /**
     * Load YAML fixtures built with AliceBundle.
     *
     * @param null $paths
     */
    protected static function loadAliceFixtures($paths = null)
    {
        static::buildKernel();

        $paths = static::getFixturesPaths($paths);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = static::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        /** @var $loader \Hautelook\AliceBundle\Alice\Loader */
        $loader = static::$kernel->getContainer()->get('hautelook_alice.loader');
        $loader->setObjectManager($em);
        $loader->setProviders(array());
        $loader->load($paths);
    }
}
