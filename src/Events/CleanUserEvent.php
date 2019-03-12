<?php declare(strict_types=1);

namespace Identity\Events;

use Exception;
use GeneralForm\EventException;
use GeneralForm\IEvent;
use GeneralForm\IEventContainer;
use Identity\IIdentityModel;
use Nette\SmartObject;


/**
 * Class CleanUserEvent
 *
 * @author  geniv
 * @package Identity\Events
 */
class CleanUserEvent implements IEvent
{
    use SmartObject;

    /** @var string */
    private $validate;
    /** @var IIdentityModel */
    private $identityModel;


    /**
     * CleanUserEvent constructor.
     *
     * @param string         $validate
     * @param IIdentityModel $identityModel
     */
    public function __construct(string $validate, IIdentityModel $identityModel)
    {
        $this->setValidate($validate);
        $this->identityModel = $identityModel;
    }


    /**
     * Set validate.
     *
     * @param string $validate
     */
    public function setValidate(string $validate)
    {
        $this->validate = $validate;
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
            $result = $this->identityModel->cleanUser($this->validate);
            //TODO pridat mozne nastaveni callbacku pro vlastni notifikaci???
        } catch (Exception $e) {
            // recall exception
            throw new EventException($e->getMessage());
        }
    }
}
