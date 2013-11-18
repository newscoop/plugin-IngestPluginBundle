<?php

namespace Newscoop\IngestPluginBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Parser repository
 */
class ParserRepository extends EntityRepository
{
    public function getParsers()
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT p.id, p.title FROM '.$this->getEntityName().' AS p');
        $parsers = $query->getResult();

        if (empty($parsers)) {
            return null;
        } else {
            return $parsers;
        }
    }

    public function getParserNamespaces()
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT p.namespace FROM '.$this->getEntityName().' AS p');
        $parsers = $query->getScalarResult();

        if (empty($parsers)) {
            return null;
        } else {
            return $parsers;
        }
    }
}
