<?php declare(strict_types=1);

namespace Identity;

use dibi;
use Dibi\Connection;
use Dibi\IDataSource;
use Nette\DateTime;
use Nette\Security\Passwords;
use Nette\SmartObject;


/**
 * Class IdentityModel
 *
 * @author  geniv
 * @package Identity
 */
class IdentityModel implements IIdentityModel
{
    use SmartObject;

    // define constant table names
    const
        TABLE_NAME = 'identity',
        NO_TIME = 'nostrtotime';

    const
        COLUMN_ID = 'id',
        REQUIRE_COLUMNS = [self::COLUMN_ID, 'login', 'hash', 'role', 'active',];    //id, login, hash, role, active

    /** @var Connection */
    private $connection;
    /** @var string */
    private $tableIdentity;
    /** @var array */
    private $columns = ['id', 'login', 'hash', 'username', 'email', 'role', 'active', 'added'];


    /**
     * IdentityModel constructor.
     *
     * @param string     $prefix
     * @param Connection $connection
     */
    public function __construct(string $prefix, Connection $connection)
    {
        $this->connection = $connection;
        // define table names
        $this->tableIdentity = $prefix . self::TABLE_NAME;
    }


    /**
     * Get columns.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }


    /**
     * Add column.
     *
     * @param string $name
     * @return $this
     */
    public function addColumn(string $name)
    {
        $this->columns[] = $name;
        return $this;
    }


    /**
     * Set columns.
     *
     * @param array $columns
     * @return $this
     * @throws IdentityException
     */
    public function setColumns(array $columns)
    {
        $diff = array_diff(self::REQUIRE_COLUMNS, $columns);
        if ($diff) {
            throw new IdentityException('This column(s) are required: "' . implode('", "', $diff) . '" !');
        }
        $this->columns = $columns;
        return $this;
    }


    /**
     * Get list.
     *
     * @return IDataSource
     */
    public function getList(): IDataSource
    {
        return $this->connection->select($this->columns)->from($this->tableIdentity);
    }


    /**
     * Get by id.
     *
     * @param int $id
     * @return \Dibi\Row|false
     */
    public function getById(int $id)
    {
        return $this->getList()
            ->where([self::COLUMN_ID => $id])
            ->fetch();
    }


    /**
     * Get by email.
     *
     * @param string $email
     * @return \Dibi\Row|false
     */
    public function getByEmail(string $email)
    {
        // get by id and active must by true
        return $this->getList()
            ->where(['email' => $email, 'active' => true])
            ->fetch();
    }


    /**
     * Insert.
     *
     * @param array $values
     * @return int
     * @throws \Dibi\Exception
     */
    public function insert(array $values): int
    {
        $values['added%sql'] = 'NOW()';
        $values['hash'] = $this->getHash($values['password']);  // auto hash password
        unset($values['password']);

        $res = $this->connection->insert($this->tableIdentity, $values)->execute(Dibi::IDENTIFIER);
        return $res;
    }


    /**
     * Update.
     *
     * @param int   $id
     * @param array $values
     * @return bool
     * @throws \Dibi\Exception
     */
    public function update(int $id, array $values): bool
    {
        if (isset($values['password'])) {
            if ($values['password']) {
                $values['hash'] = $this->getHash($values['password']);  // auto hash password
            }
            unset($values['password']);
        }

        $res = (bool) $this->connection->update($this->tableIdentity, $values)->where([self::COLUMN_ID => $id])->execute();
        return $res;
    }


    /**
     * Delete.
     *
     * @param int $id
     * @return bool
     * @throws \Dibi\Exception
     */
    public function delete(int $id): bool
    {
        $res = (bool) $this->connection->delete($this->tableIdentity)->where([self::COLUMN_ID => $id])->execute();
        return $res;
    }


    /**
     * Get hash.
     *
     * @param string $password
     * @return string
     */
    public function getHash(string $password): string
    {
        return Passwords::hash($password);
    }


    /**
     * Verify hash.
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyHash(string $password, string $hash): bool
    {
        return Passwords::verify($password, $hash);
    }


    /**
     * Exist login.
     *
     * @param string $login
     * @return int
     */
    public function existLogin(string $login): int
    {
        return (int) $this->connection->select(self::COLUMN_ID)
            ->from($this->tableIdentity)
            ->where(['login' => $login])
            ->fetchSingle();
    }


    /**
     * Exist email.
     *
     * @param string $email
     * @return int
     */
    public function existEmail(string $email): int
    {
        return (int) $this->connection->select(self::COLUMN_ID)
            ->from($this->tableIdentity)
            ->where(['email' => $email])
            ->fetchSingle();
    }


    /**
     * Clean user.
     *
     * @param string|null $validate
     * @return int
     * @throws \Dibi\Exception
     */
    public function cleanUser(string $validate = null): int
    {
        $result = 0;
        if ($validate) {
            $validateTo = new DateTime;
            $validateTo->modify($validate);

            $list = $this->getList()
                ->where([
                    'active' => false,
                    'added IS NOT NULL',
                    ['added<=%dt', $validateTo],
                ]);

            foreach ($list as $item) {
                if ($this->delete($item[self::COLUMN_ID])) {
                    $result++;
                }
            }
        }
        return $result;
    }


    /**
     * Get encode hash.
     *
     * @param int         $id
     * @param string      $slug
     * @param string|null $linkValidate
     * @return string
     */
    public function getEncodeHash(int $id, string $slug, string $linkValidate = null): string
    {
        return base64_encode(uniqid(($linkValidate ? strtotime($linkValidate) : self::NO_TIME) . '.', true) . '_' . $this->getHash($id . $slug) . '.' . $id);
    }


    /**
     * Get decode link.
     *
     * @param string $hash
     * @return array
     * @throws IdentityException
     */
    public function getDecodeHash(string $hash): array
    {
        $decode = base64_decode($hash, true);
        list($part1, $part2) = explode('_', $decode);

        $p1 = explode('.', $part1);
        list($linkValidate,) = $p1; // get validate int

        $id = null;
        $verifyHash = null;
        $dateNow = new DateTime();
        $dateValidate = $dateNow;
        if ($linkValidate == self::NO_TIME) {
            // parameter $linkValidate is null -> generate now
            $linkValidate = $dateNow->getTimestamp();
        }
        $dateValidate->setTimestamp((int) $linkValidate);  // convert validate int do datetime
        if ($dateValidate >= $dateNow) {   // check expiration
            $p2 = explode('.', $part2);
            $verifyHash = implode('.', array_slice($p2, 0, -1));  // regenerate hash
            $id = $p2[count($p2) - 1];  // get id from last part
        } else {
            throw new IdentityException('Activate link is expired!');
        }
        return ['id' => $id, 'verifyHash' => $verifyHash, 'expired' => (int) $linkValidate];
    }


    /**
     * Process approve.
     *
     * @param string $hash
     * @return bool
     * @throws IdentityException
     * @throws \Dibi\Exception
     */
    public function processApprove(string $hash): bool
    {
        $decode = $this->getDecodeHash($hash);

        $id = (int) $decode['id'];
        $verifyHash = $decode['verifyHash'];

        $item = $this->getById($id);  // load row from db
        if ($item && $id == $item['id']) { // not null result
            if (!$item['active']) {
                if ($this->verifyHash($item['id'] . $item['login'], $verifyHash)) {  // check hash and password
                    return $this->update($item['id'], ['active' => true]);    // final activate user
                } else {
                    throw new IdentityException('Invalid hash!');
                }
            } else {
                // basic check expire link (after click), next check is only active status not datetime! - execute update only if not active
                throw new IdentityException('User is already approve!');
            }
        } else {
            throw new IdentityException('User does not exist!');
        }
    }


    /**
     * Is valid forgotten.
     *
     * @param string $hash
     * @return bool
     * @throws IdentityException
     */
    public function isValidForgotten(string $hash): bool
    {
        $decode = $this->getDecodeHash($hash);

        $id = (int) $decode['id'];
        $verifyHash = $decode['verifyHash'];

        $item = $this->getById($id);  // load row from db
        if ($item && $id == $item['id']) { // not null result
            // check hash and password
            return $this->verifyHash($item['id'] . $item['login'], $verifyHash);
        }
        return false;
    }


    /**
     * Process forgotten.
     *
     * @param string $hash
     * @param string $password
     * @return bool
     * @throws IdentityException
     * @throws \Dibi\Exception
     */
    public function processForgotten(string $hash, string $password): bool
    {
        $decode = $this->getDecodeHash($hash);

        $id = (int) $decode['id'];
        $verifyHash = $decode['verifyHash'];

        $item = $this->getById($id);  // load row from db
        if ($item && $id == $item['id']) { // not null result
            $values['hash'] = $this->getHash($password);  // auto hash password
            if ($this->verifyHash($item['id'] . $item['login'], $verifyHash)) {  // check hash and password
                return $this->update($item['id'], $values);    // final save user
            } else {
                throw new IdentityException('Invalid hash!');
            }
        } else {
            throw new IdentityException('User does not exist!');
        }
    }
}
