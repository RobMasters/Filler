<?php

namespace Filler;

/**
 * Interface that all fixture classes must implement
 *
 * @author Rob Masters <robmasters87@gmail.com>
 */
interface Fixture
{
    /**
     * @param FixturesBuilder $builder
     * @return mixed
     */
    public function build(FixturesBuilder $builder);
}