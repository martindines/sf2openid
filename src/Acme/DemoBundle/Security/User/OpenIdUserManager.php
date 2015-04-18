<?php

namespace Acme\DemoBundle\Security\User;

use Fp\OpenIdBundle\Model\UserManager;
use Fp\OpenIdBundle\Model\IdentityManagerInterface;
use Doctrine\ORM\EntityManager;
use Acme\DemoBundle\Entity\OpenIdIdentity;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Acme\DemoBundle\Entity\User;

class OpenIdUserManager extends UserManager
{
    // we will use an EntityManager, so inject it via constructor
    public function __construct(IdentityManagerInterface $identityManager, EntityManager $entityManager)
    {
        parent::__construct($identityManager);

        $this->entityManager = $entityManager;
    }

    /**
     * @param string $identity
     * @param array $attributes
     * @return \UserInterface
     * @throws \Exception
     */
    public function createUserFromIdentity($identity, array $attributes = array())
    {
        $steamId = $this->getSteamIdFromIdentity($identity);
        if (false === $steamId) {
            throw new \Exception('Steam ID not found');
        }

        // in this example, we fetch User entities by e-mail
        $user = $this->entityManager->getRepository('AcmeDemoBundle:User')->findOneBy(array(
            'steam_id' => $steamId
        ));

        if (null === $user) {
            $user = new User();
            $user->setSteamId($steamId);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        // we create an OpenIdIdentity for this User
        $openIdIdentity = new OpenIdIdentity();
        $openIdIdentity->setIdentity($identity);
        $openIdIdentity->setAttributes($attributes);
        $openIdIdentity->setUser($user);

        $this->entityManager->persist($openIdIdentity);
        $this->entityManager->flush();

        // end of example

        return $user; // you must return an UserInterface instance (or throw an exception)
    }

    private function getSteamIdFromIdentity($identity)
    {
        $ptn = "/^http:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
        preg_match($ptn, $identity, $matches);

        return $matches[1];
    }
}