<?php declare(strict_types=1);

namespace Identity;

use DateTime;
use dibi;
use Dibi\Connection;
use Dibi\Exception as DibiException;
use Dibi\IDataSource;
use Dibi\Row;
use Exception;
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
        ID_SEPARATOR = '.|.',
        TIME_SEPARATOR = '.X|.',
        PART_SEPARATOR = '_|_';

    const
        COLUMN_ID = 'id',
        REQUIRE_COLUMNS = [self::COLUMN_ID, 'login', 'hash', 'role', 'active',];    //id, login, hash, role, active

    /** @var Connection */
    private $connection;

    /** @var string */
    private $tableName, $tableIdentity;

    /** @var array */
    private $columns = ['id', 'login', 'hash', 'username', 'email', 'role', 'active', 'added'];


    /**
     * IdentityModel constructor.
     *
     * @param string     $prefix
     * @param string     $tableName
     * @param Connection $connection
     */
    public function __construct(string $prefix, string $tableName = self::TABLE_NAME, Connection $connection)
    {
        $this->tableName = $tableName;
        $this->connection = $connection;
        // define table names
        $this->tableIdentity = $prefix . $tableName;
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
        $columns = array_map(function ($item) {
            return $this->tableName[0] . '.' . $item;
        }, $this->columns);
        return $this->connection->select($columns)->from($this->tableIdentity)->as($this->tableName[0]);
    }


    /**
     * Get by id.
     *
     * @param int $id
     * @return Row|false
     */
    public function getById(int $id)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->getList()
            ->where([$this->tableName[0] . '.' . self::COLUMN_ID => $id])
            ->fetch();
    }


    /**
     * Get by email.
     *
     * @param string $email
     * @return Row|false
     */
    public function getByEmail(string $email)
    {
        // get by id and active must by true
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->getList()
            ->where([$this->tableName[0] . '.email' => $email, $this->tableName[0] . '.active' => true])
            ->fetch();
    }


    /**
     * Get insertId.
     *
     * @return int
     * @throws Exception
     */
    public function getInsertId(): int
    {
        return $this->connection->getInsertId();
    }


    /**
     * Insert.
     *
     * @param array $values
     * @return int
     * @throws DibiException
     */
    public function insert(array $values): int
    {
        $values['added%sql'] = 'NOW()';
        if (isset($values['password'])) {
            if ($values['password']) {
                $values['hash'] = $this->getHash($values['password']);  // auto hash password
            }
            unset($values['password']);
        }

        $res = $this->connection->insert($this->tableIdentity, $values)->execute(Dibi::IDENTIFIER);
        return $res;
    }


    /**
     * Update.
     *
     * @param int   $id
     * @param array $values
     * @return bool
     * @throws DibiException
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
     * @throws DibiException
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
     * @throws DibiException
     */
    public function cleanUser(string $validate = null): int
    {
        $result = 0;
        if ($validate) {
            $validateTo = new DateTime;
            $validateTo->modify($validate);

            /** @noinspection PhpUndefinedMethodInspection */
            $list = $this->getList()
                ->where([
                    $this->tableName[0] . '.active' => false,
                    $this->tableName[0] . '.added IS NOT NULL',
                    [$this->tableName[0] . '.added<=%dt', $validateTo],
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
        return base64_encode(uniqid(($linkValidate ? strtotime($linkValidate) : self::NO_TIME) . self::TIME_SEPARATOR, true) . self::PART_SEPARATOR . $this->getHash($id . $slug) . self::ID_SEPARATOR . $id);
    }


    /**
     * Get decode link.
     *
     * @param string $hash
     * @return array
     * @throws IdentityException
     * @throws Exception
     */
    public function getDecodeHash(string $hash): array
    {
        $decode = base64_decode($hash);
        list($part1, $part2) = explode(self::PART_SEPARATOR, $decode);

        $p1 = explode(self::TIME_SEPARATOR, $part1);
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
            $p2 = explode(self::ID_SEPARATOR, $part2);
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
     * @throws DibiException
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
     * @throws DibiException
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
