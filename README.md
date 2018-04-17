Identity
========

Installation
------------

```sh
$ composer require geniv/nette-identity
```
or
```json
"geniv/nette-identity": ">=1.0.0"
```

require:
```json
"php": ">=7.0.0",
"nette/nette": ">=2.4.0",
"geniv/nette-general-form": ">=1.0.0"
```


Include in application
----------------------

neon configure:
```neon
- Identity\IdentityModel(%tablePrefix%)
```

events:
```neon
## for registration
events:
    - Identity\Events\CleanUserEvent(-14 days)								# clean non active user
#    - CallbackEvent															# check duplicity
#    - SetValueEvent([active: false, role: guest])							# default value for registration
    - Identity\Events\RegistrationEvent										# core registration
    - Identity\Events\ApproveLinkEvent(+1 hour, //Registration:approve)		# generate approve link
#    admin: Identity\Events\RegistrationEmailNotifyEvent						# email for admin
#    user: Identity\Events\RegistrationEmailNotifyEvent						# email for user

## for forgotten
eventsStep1:
    - Identity\Events\ForgottenStep1Event(+1 hour, //Forgotten:reset)		# generate forgotten link
#    - Identity\Events\ForgottenEmailNotifyEvent								# email for user
eventsStep2:
    - Identity\Events\ForgottenStep2Event
```
