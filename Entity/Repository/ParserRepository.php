<?php

namespace Newscoop\IngestPluginBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Newscoop\IngestPluginBundle\Events\IngestParsersEvent;

/**
 * Parser repository
 */
class ParserRepository extends EntityRepository
{
    public function getParsers($activeOnly = true, $returnQB = false)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p');

        if ($activeOnly) {
            $queryBuilder
                ->where('p.active = :active')
                ->setParameter('active', true);
        }

        if ($returnQB) {
            return $queryBuilder;
        }

        $parsers =$queryBuilder
            ->getQuery()
            ->getResult();

        return $parsers;
    }

    public function getParserNamespaces()
    {
        // $parsers = $this->createQueryBuilder('p')
        //     ->select('p.namespace')
        //     ->getQuery()
        //     ->getResult();

        // return $parsers;

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
