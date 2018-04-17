<?php declare(strict_types=1);

namespace Identity\Events;

use GeneralForm\EventException;
use GeneralForm\IEvent;
use GeneralForm\IEventContainer;
use Identity\IIdentityModel;
use Nette\SmartObject;


/**
 * Class RegistrationEvent
 *
 * @author  geniv
 * @package Identity\Events
 */
class RegistrationEvent implements IEvent
{
    use SmartObject;

    /** @var IIdentityModel */
    private $identityModel;


    /**
     * RegistrationEvent constructor.
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
     */
    public function update(IEventContainer $eventContainer, array $values)
    {
        try {
            $idUser = $this->identityModel->insert($values);
            $eventContainer->addValues(['id_user' => $idUser]);
        } catch (\Dibi\Exception $e) {
            // recall exception
            throw new EventException($e->getMessage());
        }
    }
}
