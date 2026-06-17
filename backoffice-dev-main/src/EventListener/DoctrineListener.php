<?php

namespace App\EventListener;

use App\Entity\Asset;
use App\Entity\Log;
use App\Entity\User;
use App\Entity\UserLog;
use App\Exception\LogActionNotDefinedException;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Debug\Exception\ContextErrorException;

class DoctrineListener
{
    public const ACTION_NEW_USER = 101;
    public const ACTION_NEW_ASSET = 102;

    // User actions
    public const ACTION_USER_UPDATED_BIRTHCOUNTRY = 201;
    public const ACTION_USER_UPDATED_GENDER = 202;

    public const ACTION_ASSET_UPDATED_VISIBILITY = 301;

    protected $environment;

    protected $userLogMessages = [
        // New User created
        self::ACTION_NEW_USER => 'New user registration with email %username%',

        // New Asset created
        self::ACTION_NEW_ASSET => 'New Asset created by user #%userid%',

        // User updated her birth country
        self::ACTION_USER_UPDATED_BIRTHCOUNTRY => 'You changed your birth country from %oldvalue% to %newvalue%',

        // User updated her birth country
        self::ACTION_ASSET_UPDATED_VISIBILITY => 'You changed your visibility from %oldvalue% to %newvalue%',

        // User updated her gender
        self::ACTION_USER_UPDATED_GENDER => 'You changed your gender from %oldvalue% to %newvalue%',
    ];

    public function __construct($environment)
    {
        $this->environment = $environment;
    }

    public function prePersist(PrePersistEventArgs $args)
    {
        return;
        if ($this->isTestEnvironment()) {
            return;
        }

        $object = $args->getObject();

        if ($object instanceof Log) {
            return;
        }

        $action = 'ACTION_NEW_' . strtoupper(get_class($object));

        if (!$this->isActionDefined($action)) {
            // Dont throw the exception until we have all the actions defined
            return;
            throw new LogActionNotDefinedException($action);
        }

        $log = new UserLog();

        $log
            ->setEvent($action)
            ->setMessage($this->getLogMessageForAction($action))
            ->setType(UserLog::TYPE_USER);

        $args
            ->getEntityManager()
            ->getEventManager()
            ->removeEventListener('prePersist', $this);

        $args->getEntityManager()->persist($log);

        $args->getEntityManager()->flush();

        $args
            ->getEntityManager()
            ->getEventManager()
            ->addEventListener('prePersist', $this);
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        //@todo we are not using this logging system
        return;
        if ($this->isTestEnvironment()) {
            return;
        }

        $object = $args->getObject();

        if ($object instanceof Log) {
            return;
        }

        if ($object instanceof User) {
            return $this->userPreUpdate($args);
        }

        if ($object instanceof Asset) {
            return $this->assetPreUpdate($args);
        }
    }

    /**
     * Handles User logging
     *
     * @param PreUpdateEventArgs $args
     * @throws LogActionNotDefinedException if event action is not defined
     */
    protected function userPreUpdate(PreUpdateEventArgs $args)
    {
        // Ignore these fields as they are system controlled
        // And are updated automatically
        $fieldsToIgnore = ['lastLogin', 'createdAt', 'updatedAt'];

        $changeSet = $args->getEntityChangeSet();

        foreach ($changeSet as $key => $value) {
            if (in_array($key, $fieldsToIgnore)) {
                continue;
            }

            $action = 'ACTION_USER_UPDATED_' . strtoupper($key);

            if (!$this->isActionDefined($action)) {
                return;
                throw new LogActionNotDefinedException($action);
            }

            $log = new UserLog();

            $log
                ->setUser($args->getObject())
                ->setMessage($this->getLogMessageForAction($action))
                ->setType(UserLog::TYPE_USER)
                ->setEvent($action)
                ->setOldValue($args->getOldValue($key))
                ->setNewValue($args->getNewValue($key));

            $args
                ->getEntityManager()
                ->getEventManager()
                ->removeEventListener('preUpdate', $this);

            $args->getEntityManager()->persist($log);

            $args->getEntityManager()->flush();

            $args
                ->getEntityManager()
                ->getEventManager()
                ->addEventListener('preUpdate', $this);
        }
    }

    /**
     * Handles Asset logging
     *
     * @param PreUpdateEventArgs $args
     */
    public function assetPreUpdate(PreUpdateEventArgs $args)
    {
        // Ignore these fields as they are system controlled
        // And are updated automatically
        $fieldsToIgnore = ['createdAt', 'updatedAt'];

        $changeSet = $args->getEntityChangeSet();

        foreach ($changeSet as $key => $value) {
            if (in_array($key, $fieldsToIgnore)) {
                continue;
            }

            $action = 'ACTION_ASSET_UPDATED_' . strtoupper($key);

            if (!$this->isActionDefined($action)) {
                throw new LogActionNotDefinedException($action);
            }

            /*$log = new UserLog();
             *
             * $log->setUser( $args->getObject() )
             * ->setMessage( $this->getLogMessageForAction( $action ) )
             * ->setType( UserLog::TYPE_USER )
             * ->setEvent( $action )
             * ->setOldValue( $args->getOldValue( $key ) )
             * ->setNewValue( $args->getNewValue( $key ) );
             *
             * $args->getEntityManager()->getEventManager()->removeEventListener('preUpdate', $this);
             *
             * $args->getEntityManager()->persist( $log );
             *
             * $args->getEntityManager()->flush();
             */

            $args
                ->getEntityManager()
                ->getEventManager()
                ->addEventListener('preUpdate', $this);
        }
    }

    protected function getLogMessageForAction($action)
    {
        if (isset($this->userLogMessages[constant('self::' . $action)])) {
            return $this->userLogMessages[constant('self::' . $action)];
        }

        throw new LogActionNotDefinedException($action);
    }

    protected function isActionDefined($action)
    {
        try {
            return constant('self::' . $action);
        } catch (ContextErrorException $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function isTestEnvironment()
    {
        return $this->environment === 'test';
    }
}
