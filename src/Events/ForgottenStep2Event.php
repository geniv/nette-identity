<?php declare(strict_types=1);

namespace Identity\Events;

use GeneralForm\EventException;
use GeneralForm\IEvent;
use GeneralForm\IEventContainer;
use Identity\IdentityException;
use Identity\IIdentityModel;
use Nette\SmartObject;


/**
 * Class DibiForgottenEvent
 *
 * @author  geniv
 * @package Identity\Events
 */
class ForgottenStep2Event implements IEvent
{
    use SmartObject;

    /** @var IIdentityModel */
    private $identityModel;


    /**
     * ForgottenStep2Event constructor.
     *
     * @param IIdentityModel $identityModel
     */
    public function __construct(IIdentityModel $identityModel)
    {
        $this->identityModel = $identityModel;
    }


    /**
     * Update.
     *
     * @param IEventContainer $eventContainer
     * @param array           $values
     * @throws EventException
     * @throws \Dibi\Exception
     */
    public function update(IEventContainer $eventContainer, array $values)
    {
        try {
            $this->identityModel->processForgotten($values['hash'], $values['password']);
        } catch (IdentityException $e) {
            throw new EventException($e->getMessage());
        }
    }
}
