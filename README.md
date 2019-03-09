Identity
========

Installation
------------

```sh
$ composer require geniv/nette-identity
```
or
```json
"geniv/nette-identity": "^1.1"
```

require:
```json
"php": ">=7.0",
"nette/security": ">=2.4",
"nette/utils": ">=2.4",
"dibi/dibi": ">=3.0",
"geniv/nette-general-form": ">=1.0"
```

Include in application
----------------------

neon configure:
```neon
services:
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

model:
```php
// usage IIdentityModel

getColumns(): array
addColumn(string $name)
setColumns(array $columns)

getList(): Fluent
getById(int $id)
getByEmail(string $email)

insert(array $values): int
update(int $id, array $values): bool
delete(int $id): bool

getHash(string $password): string
verifyHash(string $password, string $hash): bool

existLogin(string $login): int
existEmail(string $email): int

cleanUser(string $validate = null): int

getEncodeHash(int $id, string $login, string $linkValidate = null): string
getDecodeHash(string $hash): array

processApprove(string $hash): bool

isValidForgotten(string $hash): bool
processForgotten(string $hash, string $password): bool
```
