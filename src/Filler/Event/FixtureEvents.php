<?php

namespace Filler\Event;

/**
 * Class to provide event names/patterns
 *
 * @author Rob Masters <robmasters87@gmail.com>
 */
final class FixtureEvents
{
    /**
     * Event that is fired when the dependency manager is given a labelled fixture.
     *
     * Listeners will be passed an instance of Filler\Event\FixtureAddedEvent
     */
    const RESOLVE_DEPENDENCY_PATTERN = 'fixture.resolve.%s';
}