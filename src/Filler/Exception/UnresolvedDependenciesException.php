<?php

namespace Filler\Exception;
use Exception;
use Filler\DependencyResolver;

/**
 * Exception thrown when there are unresolved dependencies after loading all fixtures
 *
 * @author Rob Masters <robmasters87@gmail.com>
 */
class UnresolvedDependenciesException extends FixtureBuildingException
{
    /**
     * @param DependencyResolver[] $dependencyResolvers
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($dependencyResolvers, $code = 0, Exception $previous = null)
    {
        $unmet = [];
        foreach ($dependencyResolvers as $dependencyResolver) {
            $dependcies = $dependencyResolver->getDependencies(true);
            foreach ($dependcies as $dependency) {
                $unmet[] = $dependency;
            }
        }

        $unmet = array_unique($unmet);
        $message = sprintf("The following dependencies have not been met:\n\t%s", implode("\n\t", $unmet));

        parent::__construct($message, $code, $previous);
    }

}