<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Enum\UserStatus;
use App\Entity\Enum\WalletUserVersion;
use App\Entity\Investor;
use App\Entity\KycProfile;
use App\Entity\Status;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements
    PasswordUpgraderInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private LoggerInterface $logger,
    ) {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string $newHashedPassword,
    ): void {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf(
                'Instances of "%s" are not supported.',
                \get_class($user),
            ));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge(
            $this->getEntityManager()->getClassMetadata(User::class)->getFieldNames(),
            $this
                ->getEntityManager()
                ->getClassMetadata(User::class)
                ->getAssociationNames(),
            $this->getEntityManager()->getClassMetadata(Status::class)->getFieldNames(),
            $this
                ->getEntityManager()
                ->getClassMetadata(Company::class)
                ->getFieldNames(),
            $this
                ->getEntityManager()
                ->getClassMetadata(Investor::class)
                ->getFieldNames(),
            $this
                ->getEntityManager()
                ->getClassMetadata(KycProfile::class)
                ->getFieldNames(),
            [
                'companyName',
                'hasInvestments',
                'hasManagedUsers',
                'hasKycProfile',
                'hasVerifiedBy',
                'hasOnboardingProfile',
                'hasMangoPayUserId',
                'hasMangoPayWalletId',
                'wordsOfOwn',
                'phoneNumber',
                'createdAt_gte',
                'createdAt_lt',
            ],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->leftJoin('user.status', 'status')
            ->leftJoin('user.company', 'company')
            ->leftJoin('user.investor', 'investor')
            ->leftJoin('user.kycProfile', 'kycProfile')
            ->leftJoin('user.onboardingProfile', 'onboardingProfile');

        if (!empty(array_intersect(['status'], array_keys($filters)))) {
            // Potential querying cost to these, so only join if there are filters that need them
            // https://stackoverflow.com/a/2111420
            if (isset($filters['status']) && !empty($filters['status'])) {
                $this->logger->debug('Applying status log filters');
                $qb
                    ->leftJoin('user.statusLogs', alias: 'status_log')
                    ->leftJoin(
                        'user.statusLogs',
                        alias: 'status_log_comparison',
                        conditionType: Query\Expr\Join::WITH,
                        condition: $qb->expr()->andX(
                            $qb->expr()->eq('user.id', 'status_log_comparison.user'),
                            $qb->expr()->orX(
                                $qb->expr()->lt(
                                    'status_log.occuredAt',
                                    'status_log_comparison.occuredAt',
                                ),
                                $qb->expr()->andX(
                                    $qb->expr()->eq(
                                        'status_log.occuredAt',
                                        'status_log_comparison.occuredAt',
                                    ),
                                    $qb->expr()->lt(
                                        'status_log.id',
                                        'status_log_comparison.id',
                                    ),
                                ),
                            ),
                        ),
                    )
                    ->andWhere('status_log_comparison.id IS NULL');
            }
        }

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['username', 'email', 'name'])) {
                // loose string match
                if ('name' == $key) {
                    // combined name search
                    $qb->andWhere($qb->expr()->orX(
                        $qb->expr()->like('user.firstname', ':' . $key),
                        $qb->expr()->like('user.lastname', ':' . $key),
                    ));
                } else {
                    $qb->andWhere($qb->expr()->like('user.' . $key, ':' . $key));
                }
            } elseif (in_array($key, ['phoneNumber'])) {
                // phone number wildcard after stripping out specific non-numerical
                // REGEXP_REPLACE would be preferred, but not yet supported by the doctrine extension
                // REGEX_REPLACE also not avialable in sqlite for tests
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->like(
                        "REPLACE(REPLACE(REPLACE(user.phone1, '-', ''), ' ', ''), '+','')",
                        ':' . $key,
                    ),
                    $qb->expr()->like(
                        "REPLACE(REPLACE(REPLACE(user.phone2, '-', ''), ' ', ''), '+','')",
                        ':' . $key,
                    ),
                ));
            } elseif (in_array($key, ['companyName'])) {
                // company relations
                $qb->andWhere($qb->expr()->like('company.name', ':' . $key));
            } elseif (in_array($key, ['corporateInvestor'])) {
                // investor relations - need to handle uninitialised null state
                if ($value) {
                    $qb->andWhere($qb->expr()->in('investor.' . $key, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->orX(
                        $qb->expr()->in('investor.' . $key, ':' . $key),
                        $qb->expr()->isNull('investor.' . $key),
                    ));
                }
            } elseif (in_array($key, ['wordsOfOwn'])) {
                // if field is filled or not
                if ($value) {
                    $qb->andWhere($qb->expr()->isNotNull('investor.' . $key));
                } else {
                    $qb->andWhere($qb->expr()->isNull('investor.' . $key));
                }
                continue;
            } elseif (in_array($key, ['hasInvestments', 'hasManagedUsers'])) {
                // collections existence
                $field = lcfirst(substr($key, strlen('has')));
                if ($value) {
                    $qb->andWhere('user.' . $field . ' IS NOT EMPTY');
                } else {
                    $qb->andWhere('user.' . $field . ' IS EMPTY');
                }
                continue;
            } elseif (in_array($key, [
                'hasKycProfile',
                'hasOnboardingProfile',
                'hasMangoPayUserId',
                'hasMangoPayWalletId',
            ])) {
                // one-to-one existence
                $field = lcfirst(substr($key, strlen('has')));
                if ($value) {
                    $qb->andWhere('user.' . $field . ' IS NOT NULL');
                } else {
                    $qb->andWhere('user.' . $field . ' IS NULL');
                }
                continue;
            } elseif (in_array($key, ['lifecycleStatus'])) {
                // status related
                $qb->andWhere($qb->expr()->in('status.' . $key, ':' . $key));
            } elseif (in_array($key, array_diff(
                $this->getEntityManager()->getClassMetadata(KycProfile::class)->getFieldNames(),
                ['id'],
            ))) {
                $qb->andWhere($qb->expr()->in('kycProfile.' . $key, ':' . $key));
            } elseif (in_array($key, ['hasVerifiedBy'])) {
                // kycProfile manual verification existence
                $field = lcfirst(substr($key, strlen('has')));
                if ($value) {
                    $qb->andWhere('kycProfile.' . $field . ' IS NOT NULL');
                } else {
                    $qb->andWhere('kycProfile.' . $field . ' IS NULL');
                }
                continue;
            } elseif (in_array($key, ['createdAt_gte', 'createdAt_lt'])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'user.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'user.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } elseif (in_array($key, ['status'])) {
                // status_log related
                if (
                    is_array($value)
                    && (
                        in_array(UserStatus::Pending, $value)
                        || in_array(UserStatus::Pending->value, $value)
                    )
                ) {
                    $qb->andWhere($qb->expr()->orX(
                        $qb->expr()->in('status_log.' . $key, ':' . $key),
                        $qb->expr()->isNull('status_log'),
                    ));
                } else {
                    $qb->andWhere($qb->expr()->in('status_log.' . $key, ':' . $key));
                }
            } else {
                $qb->andWhere($qb->expr()->in('user.' . $key, ':' . $key));
            }

            if (in_array($key, [
                'username',
                'email',
                'companyName',
                'name',
                'phoneNumber',
            ])) {
                // note that the preceding % wildcard has a high performance cost
                $qb->setParameter($key, '%' . addcslashes($value, '%_') . '%');
            } elseif ($value instanceof \DateTimeInterface) {
                // Datetimes should be formatted to ISO date
                $qb->setParameter($key, $value->format('Y-m-d'));
            } else {
                $qb->setParameter($key, $value);
            }
        }

        foreach ($orderBy as $key => $direction) {
            if (in_array($key, $filtersAllowed)) {
                $qb->addOrderBy('user.' . $key, $direction);
            }
        }
        return $qb->getQuery();
    }

    public function findByWithAssociations(
        array $filters,
        array $orderBy = [],
        int $limit = 10,
        int $page = 1,
    ): Pagerfanta {
        $query = $this->buildQueryWithAssociations($filters, $orderBy);
        $adapter = new QueryAdapter($query);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage(min($page, $pagerfanta->getNbPages()));
        return $pagerfanta;
    }

    public function findByEmail(string $email): ?User
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult(Query::HYDRATE_OBJECT);
    }

    /**
     * Warning:
     * This may return unexpected results due to naming of roles. e.g 'admin' will return users either with role ROLE_SUPER_ADMIN or ROLE_ADMIN.
     * This will also not return users who have an implicit role due to role hierarchy.
     */
    public function findByRole(string $role): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"' . $role . '"%');

        return $qb->getQuery()->getResult();
    }

    public function findAllPagerfanta($page, $limit): ?Pagerfanta
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u');

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    public function findManagers(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('mgr')
            ->from(User::class, 'mgr')
            ->innerJoin('mgr.managedUsers', 'usr');

        return $qb->getQuery()->getResult();
    }

    public function findNextMangopayReadyUser(?string $lastUserId = null): ?User
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->andWhere('user.mangoPayUserId IS NOT NULL')
            ->andWhere('user.roles NOT LIKE :roles')
            ->addOrderBy('user.id', 'ASC')
            ->setMaxResults(1)
            // Don't want any users with special roles (including any admins with CMS access)
            ->setParameter('roles', '%ROLE_%');
        if ($lastUserId) {
            $qb->andWhere($qb->expr()->gt('user.id', ':lastUser'));
            $qb->setParameter('lastUser', $lastUserId);
        }
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getUserRegistrationsYear(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select('COUNT(usr.id) AS count', 'YEAR(usrstatus.regCompletedOn) AS year')
            ->from('users', 'usr')
            ->leftJoin(
                'usr',
                'users_statuses',
                'usrstatus',
                'usr.status_id = usrstatus.id',
            )
            ->where('usrstatus.isRegCompleted = 1')
            ->groupBy('YEAR(usrstatus.regCompletedOn)');

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function getUserRegistrationsMonth(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select(
                'COUNT(usr.id) AS count',
                'YEAR(usrstatus.regCompletedOn) AS year',
                'MONTH(usrstatus.regCompletedOn) AS month',
            )
            ->from('users', 'usr')
            ->leftJoin(
                'usr',
                'users_statuses',
                'usrstatus',
                'usr.status_id = usrstatus.id',
            )
            ->where('usrstatus.isRegCompleted = 1')
            ->groupBy('MONTH(usrstatus.regCompletedOn)')
            ->orderBy('usrstatus.regCompletedOn', 'DESC');

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function getUsersOnboardedInvested(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select('usr.username as username')
            ->distinct()
            ->from('users', 'usr')
            ->leftJoin('usr', 'investments', 'inv', 'usr.id = inv.user_id')
            ->leftJoin(
                'usr',
                'users_statuses',
                'usrStatus',
                'usr.status_id = usrStatus.id',
            )
            ->where('usrStatus.isRegCompleted = 1 AND inv.investmentValue IS NOT NULL');

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function getLoginActivity(
        \DateTime $dateStart,
        \DateTime $dateEnd,
        ?string $grouping = null,
    ): array {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select('COUNT(id) AS "count"')
            ->from('users', 'usr')
            ->orderBy('last_login', 'DESC');
        if ($dateStart) {
            $qb->andWhere('last_login >= :dateStart')->setParameter(
                'dateStart',
                $dateStart->format('Y-m-d'),
            );
        }
        if ($dateEnd) {
            $qb->andWhere('last_login < :dateEnd')->setParameter(
                'dateEnd',
                $dateEnd->format('Y-m-d'),
            );
        }
        if ('time' === $grouping) {
            $qb
                ->addSelect('HOUR(last_login) AS "hour"')
                ->addGroupBy('HOUR(last_login)')
                ->orderBy('hour', 'ASC');
        }
        if ('date' === $grouping) {
            $qb
                ->addSelect(
                    'YEAR(last_login) AS "year"',
                    'MONTH(last_login) AS "month"',
                )
                ->addGroupBy('YEAR(last_login)')
                ->addGroupBy('MONTH(last_login)');
        }

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function getAuthAccessTokenActivity(string $grouping = 'date'): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb
            ->select('COUNT(identifier) AS "count"')
            ->from('oauth2_access_token', 'acstkn')
            ->orderBy('expiry', 'DESC');

        if ('time' === $grouping) {
            $qb
                ->addSelect('HOUR(expiry) AS "hour"')
                ->addGroupBy('HOUR(expiry)')
                ->orderBy('hour', 'ASC');
        } else {
            $qb
                ->addSelect('YEAR(expiry) AS "year"', 'MONTH(expiry) AS "month"')
                ->addGroupBy('YEAR(expiry)')
                ->addGroupBy('MONTH(expiry)');
        }

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    /**
     * For mangopay user category upgrade exercise issue#2107
     * This relies on a dedicated field to track any incremental upgrades required on the user
     */
    public function getWalletUserVersionSummary(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn
            ->createQueryBuilder()
            ->select('usr.walletUserVersion', 'COUNT(usr.walletUserVersion) AS "count"')
            ->from('users', 'usr')
            ->andWhere('usr.mangoPayUserId IS NOT NULL')
            ->groupBy('usr.walletUserVersion');
        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function findPendingUserCategoryUpgrades(
        int $limit,
        ?int $userId = null,
    ): array {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.walletUserVersion = :walletUserVersion')
            ->andWhere('user.mangoPayUserId IS NOT NULL')
            ->setParameter('walletUserVersion', WalletUserVersion::Original);
        if (!is_null($userId)) {
            $qb->andWhere('user.id = :userId')->setParameter('userId', $userId);
        }
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * All Users data for export
     */
    public function getUserData(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'select * from getUserData order by id DESC';

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function buildUserWithAdminRolesQuery($roles): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('u')
            ->from(User::class, 'u')
            ->where("REGEXP(u.roles, '" . implode('|', $roles) . "') = 1");
        return $qb;
    }

    public function buildNonStaffUserQuery($roles): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('u')
            ->from(User::class, 'u')
            ->where("NOT REGEXP(u.roles, '" . implode('|', $roles) . "') = 1");
        return $qb;
    }

    public function getAsIdAndIdentifier(): \Traversable
    {
        $dbalConnection = $this->getEntityManager()->getConnection();
        $qb = $dbalConnection->createQueryBuilder();
        $qb
            ->select('id', 'username AS identifier')
            ->from('users', 'users')
            ->orderBy('id', 'DESC');

        $stmt = $qb->executeQuery();
        return $stmt->iterateAssociative();
    }
}
