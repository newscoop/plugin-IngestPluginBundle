<?php
/**
 * @package Newscoop\IngestPluginBundle
 * @author Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2015 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Event;

use Symfony\Component\EventDispatcher\GenericEvent as SymfonyGenericEvent;

/**
 * Collect external adapters
 */
class IngestParsersEvent extends SymfonyGenericEvent
{
    /**
     * Parsers array
     *
     * @var array
     */
    public $parsers = array();

    /**
     * Register new parser
     *
     * @param string $name
     * @param array  $parser
     */
    public function registerParser($name, array $parser)
    {
        $this->parsers[$name] = $parser;
    }

    /**
     * Get all parsers
     *
     * @return array
     */
    public function getParsers()
    {
        return $this->parsers;
    }
}
