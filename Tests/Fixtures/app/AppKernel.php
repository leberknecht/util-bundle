<?php

namespace Tps\UtilBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;
use tps\UtilBundle\TpsUtilBundle;

/**
 * App Test Kernel for functional tests.
 */
class AppKernel extends Kernel
{
    public function __construct($environment, $debug)
    {
        parent::__construct($environment, $debug);
    }

    public function registerBundles()
    {
        return array(
            new FrameworkBundle(),
            new TwigBundle(),
            new TpsUtilBundle()
        );
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir(). DIRECTORY_SEPARATOR .Kernel::VERSION. DIRECTORY_SEPARATOR .
        'Tps-util-bundle' . DIRECTORY_SEPARATOR . 'cache'. DIRECTORY_SEPARATOR . $this->environment;
    }

    public function getLogDir()
    {
        return sys_get_temp_dir(). DIRECTORY_SEPARATOR . Kernel::VERSION. DIRECTORY_SEPARATOR . 'Tps-util-bundle' . DIRECTORY_SEPARATOR . 'logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__. DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR .$this->environment.'.yml');
    }

    public function serialize()
    {
        return serialize(array($this->getEnvironment(), $this->isDebug()));
    }

    public function unserialize($str)
    {
        call_user_func_array(array($this, '__construct'), unserialize($str));
    }
}
