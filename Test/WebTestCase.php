<?php

namespace Theodo\Bundle\FrameworkExtraBundle\Test;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\Tools\SchemaTool;
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
}