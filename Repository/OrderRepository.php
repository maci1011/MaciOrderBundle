<?php

namespace Maci\OrderBundle\Repository;

use Doctrine\ORM\EntityRepository;

class OrderRepository extends EntityRepository
{
    public function getConfirmed()
    {
        $query = $this->createQueryBuilder('o')
            ->where('o.invoice IS NOT NULL')
            ->orderBy('o.invoice', 'DESC')
            ->getQuery()
        ;

        return $query->getResult();
    }
}
