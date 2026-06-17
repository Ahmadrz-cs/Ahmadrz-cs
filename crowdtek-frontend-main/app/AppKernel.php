<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

date_default_timezone_set('Europe/London');
class AppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new AppBundle\AppBundle(),
            new ClientBundle\ClientBundle(),
            new Gregwar\CaptchaBundle\GregwarCaptchaBundle(),
            new Craue\FormFlowBundle\CraueFormFlowBundle(),
            new MobileDetectBundle\MobileDetectBundle(),
            new Twig\Extra\TwigExtraBundle\TwigExtraBundle(),
            new Sonata\Exporter\Bridge\Symfony\SonataExporterBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
        }

        return $bundles;
    }

    public function getRootDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return dirname(__DIR__) . '/var/cache/' . $this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return dirname(__DIR__) . '/var/log';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }
}
