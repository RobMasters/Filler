<?php

namespace Filler\Command;

use Filler\DependencyManager;
use Filler\FixturesBuilder;
use Filler\FixturesLoader;
use Filler\Persistor\PropelPersistor;
use Propel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;

class LoadFixturesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('fixtures:load')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loader = new FixturesLoader();
        $this->configureConnection($loader->getConfig());
        $builder = new FixturesBuilder(new PropelPersistor(), new DependencyManager(new EventDispatcher()));
        $loader->setBuilder($builder)->load();
    }

    /**
     * @param $config
     */
    private function configureConnection($config)
    {
        $type = $config['filler']['connection'];
        switch ($type) {
            case 'propel':
                $this->configurePropel($config['propel']);
                break;
        }
    }

    /**
     * @param $propelConfig
     */
    private function configurePropel($propelConfig)
    {
        $classPath = getcwd() . DIRECTORY_SEPARATOR . trim($propelConfig['class_path'], DIRECTORY_SEPARATOR);
        set_include_path($classPath . PATH_SEPARATOR . get_include_path());
        Propel::init($propelConfig['config_file']);
    }
}