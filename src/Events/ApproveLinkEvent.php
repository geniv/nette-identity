<?php declare(strict_types=1);

namespace Identity\Events;

use GeneralForm\IEvent;
use GeneralForm\IEventContainer;
use Identity\IIdentityModel;
use Nette\SmartObject;


/**
 * Class ApproveLinkEvent
 *
 * @author  geniv
 * @package Identity\Events
 */
class ApproveLinkEvent implements IEvent
{
    use SmartObject;

    /** @var string */
    private $validate, $destination;
    /** @var IIdentityModel */
    private $identityModel;


    /**
     * ApproveLinkEvent constructor.
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
     */
    public function update(IEventContainer $eventContainer, array $values)
    {
        if (isset($values['id_user'])) {
            $hash = $this->identityModel->getEncodeHash($values['id_user'], $values['login'], $this->validate);

            $component = $eventContainer->getComponent();
            $eventContainer->addValues(['approve_link' => $component->presenter->link($this->destination, $hash)]);
        }
    }
}
