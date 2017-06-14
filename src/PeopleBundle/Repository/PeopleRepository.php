<?php
namespace PeopleBundle\Repository;

use Doctrine\ORM\EntityRepository;
use PeopleBundle\Entity\People;

class PeopleRepository extends EntityRepository
{
    /**
     * @param string $email
     * @return People|boolean
     */
    public function getFromEmail($email)
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT p FROM PeopleBundle:People p WHERE p.email = :email ")
            ->setParameter('email', $email);
        $results = $query->getResult();

        return empty($results) ? false : $results[0];
    }
}
