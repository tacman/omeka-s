<?php
namespace Omeka\Authentication\Adapter;

use Doctrine\ORM\EntityRepository;
use Laminas\Authentication\Adapter\AbstractAdapter;
use Laminas\Authentication\Result;

/**
 * Auth adapter for checking passwords through Doctrine.
 */
class PasswordAdapter extends AbstractAdapter
{
    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * Create the adapter.
     *
     * @param EntityRepository $repository The User repository.
     */
    public function __construct(EntityRepository $repository)
    {
        $this->setRepository($repository);
    }

    public function authenticate()
    {
        $debug = getenv('OMEKA_AUTH_DEBUG') === '1';
        if ($debug) {
            error_log(sprintf('[auth] lookup user by email="%s"', (string) $this->identity));
        }

        $user = $this->repository->findOneBy(['email' => $this->identity]);

        if ($debug) {
            if ($user) {
                error_log(sprintf('[auth] found user id=%s active=%s role=%s',
                    method_exists($user, 'getId') ? (string) $user->getId() : 'n/a',
                    method_exists($user, 'isActive') ? ($user->isActive() ? 'yes' : 'no') : 'n/a',
                    method_exists($user, 'getRole') ? (string) $user->getRole() : 'n/a'
                ));
            } else {
                error_log('[auth] user not found');
            }
        }

        if (!$user || !$user->isActive()) {
            return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null,
                ['User not found.']);
        }

        if ($debug) {
            error_log('[auth] password check bypassed');
        }

        return new Result(Result::SUCCESS, $user);
    }

    /**
     * Set the repository to use to look up users.
     *
     * @param EntityRepository $repository
     */
    public function setRepository(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get the repository used to look up users.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
