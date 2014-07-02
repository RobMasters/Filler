<?php

namespace Filler;

use Filler\Exception\ConfigurationLoadingException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Entry point that loads and builds all fixture files
 *
 * @author Rob Masters <robmasters87@gmail.com>
 */
class FixturesLoader
{
    /**
     * @var FixturesBuilder
     */
    protected $builder;

    /**
     * @param FixturesBuilder $builder
     */
    function __construct(FixturesBuilder $builder)
    {
        $this->builder = $builder;
        $this->config = $this->loadConfiguration();
    }

    /**
     * @throws \RuntimeException
     */
    public function load()
    {
        try {
            $this->builder->preLoad();

            $fixtures = $this->getFixtures();
            foreach ($fixtures as $fixture) {
                $fixture->build($this->builder);
            }

            $this->builder->postLoad();
        } catch (\Exception $e) {
            $this->builder->abortLoad();
        }
    }

    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return array
     * @throws ConfigurationLoadingException
     */
    private function loadConfiguration()
    {
        $configPath = $this->getConfigPath();
        if (!is_file($configPath) || !is_readable($configPath)) {
            throw new ConfigurationLoadingException(sprintf('Configuration file `%s` not found.', $configPath));
        }

        $config = (array) Yaml::parse($configPath);

        return $config;
    }

    /**
     * @return string|null
     */
    private function getConfigPath()
    {
        $cwd = rtrim(getcwd(), DIRECTORY_SEPARATOR);
        $fillerDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..';
        $paths = array_filter(
            array(
                // User-defined configuration
                $cwd . DIRECTORY_SEPARATOR . 'filler.yml',
                $cwd . DIRECTORY_SEPARATOR . 'filler.yml.dist',
                $cwd . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'filler.yml',
                $cwd . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'filler.yml.dist',

                // Default configuration
                $fillerDir . DIRECTORY_SEPARATOR . 'filler.yml',
                $fillerDir . DIRECTORY_SEPARATOR . 'filler.yml.dist',
            ),
            'is_file'
        );

        if (count($paths)) {
            return current($paths);
        }

        return null;
    }

    /**
     * @return Fixture[]
     */
    private function getFixtures()
    {
        $out = array();

        return $out;
    }
}
