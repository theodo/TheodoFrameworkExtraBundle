Installation
------------

Add the following in your composer.json file:

.. code:: json

    "require": {
        "theodo/framework-extra-bundle": "~1.0@dev"
    }

Then execute the command ``composer update theodo/framework-extra-bundle``

Finally, register the bundle in the Kernel of your Symfony2 application:

.. code:: php

    public function registerBundles()
    {
        $bundles = arra(
            // ...

            new Theodo\Bundle\FrameworkExtraBundle\TheodoFrameworkExtraBundle(),
        );
    }
