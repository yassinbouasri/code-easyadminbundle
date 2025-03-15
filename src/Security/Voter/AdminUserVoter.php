<?php

namespace App\Security\Voter;

use App\Entity\User;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class AdminUserVoter extends Voter
{
    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['ADMIN_USER_EDIT'])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!$subject instanceof User) {
            throw new \LogicException('AdminUserVoter can only use ADMIN_USER_EDIT method');
        }
        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'ADMIN_USER_EDIT':
               return $user === $subject || $this->security->isGranted('ROLE_SUPER_ADMIN');
        }

        return false;
    }
}
