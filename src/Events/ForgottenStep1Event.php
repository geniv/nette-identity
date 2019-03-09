<?php declare(strict_types=1);

namespace Identity\Events;

use GeneralForm\EventException;
use GeneralForm\IEvent;
use GeneralForm\IEventContainer;
use Identity\IIdentityModel;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\SmartObject;


/**
 * Class ForgottenStep1Event
 *
 * @author  geniv
 * @package Identity\Events
 */
class ForgottenStep1Event implements IEvent
{
    use SmartObject;

    /** @var string */
    private $validate, $destination;
    /** @var IIdentityModel */
    private $identityModel;


    /**
     * ForgottenStep1Event constructor.
     *
     * @param string         $validate
     * @param string         $destination
     * @param IIdentityModel $identityModel
     */
    public function __construct(string $validate, string $destination, IIdentityModel $identityModel)
    {
        $this->setValidate($validate);
        $this->setDestination($destination);
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
     * Set destination.
     *
     * @param string $destination
     */
    public function setDestination(string $destination)
    {
        $this->destination = $destination;
    }


    /**
     * Update.
     *
     * @param IEventContainer $eventContainer
     * @param array           $values
     * @throws EventException
     * @throws InvalidLinkException
     */
    public function update(IEventContainer $eventContainer, array $values)
    {
        $user = $this->identityModel->getByEmail($values['email']);
        if ($user) {
            $hash = $this->identityModel->getEncodeHash($user['id'], $user['login'], $this->validate);

            /** @var Presenter $component */
            $component = $eventContainer->getComponent();
            $eventContainer->addValues($user->toArray());
            $eventContainer->addValues(['approve_link' => $component->presenter->link($this->destination, $hash)]);
        } else {
            throw new EventException('User does not exist or not active!');
        }
    }
}
