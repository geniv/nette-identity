Identity registration
=====================

Installation
------------

```sh
$ composer require geniv/nette-identity-registration
```
or
```json
"geniv/nette-identity-registration": ">=1.0.0"
```

require:
```json
"php": ">=7.0.0",
"nette/nette": ">=2.4.0",
"geniv/nette-general-form": ">=1.0.0"
```

http://php.net/manual/en/function.strtotime.php


Include in application
----------------------

neon configure:
```neon
# identity registration
identityRegistration:
#   autowired: true
#   formContainer: Identity\Registration\FormContainer
```

neon configure extension:
```neon
extensions:
    identityRegistration: Identity\Registration\Bridges\Nette\Extension
```

presenters:
```php
protected function createComponentIdentityRegistration(RegistrationForm $registrationForm): RegistrationForm
{
    //$registrationForm->setTemplatePath(__DIR__ . '/templates/RegistrationForm.latte');
    $registrationForm->onRegistered[] = function (array $values) {
        $this->flashMessage('Registration!', 'info');
        $this->redirect('this');
    };
    $registrationForm->onRegisteredException[] = function (EventException $e) {
        $this->flashMessage('Registration exception! ' . $e->getMessage(), 'danger');
        $this->redirect('this');
    };
    return $registrationForm;
}
```

usage:
```latte
{control identityRegistration}
```
