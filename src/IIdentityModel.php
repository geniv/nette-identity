<?php declare(strict_types=1);

namespace Identity;

use Dibi\Fluent;


/**
 * Interface IIdentityModel
 *
 * @author  geniv
 * @package Identity
 */
interface IIdentityModel
{

    /**
     * Get columns.
     *
     * @return array
     */
    public function getColumns(): array;


    /**
     * Add column.
     *
     * @param string $name
     * @return $this
     */
    public function addColumn(string $name);


    /**
     * Set columns.
     *
     * @param array $columns
     * @return $this
     * @throws IdentityException
     */
    public function setColumns(array $columns);


    /**
     * Get list.
     *
     * @return Fluent
     */
    public function getList(): Fluent;


    /**
     * Get by id.
     *
     * @param $id
     * @return \Dibi\Row|false
     */
    public function getById(int $id);


    /**
     * Get by email.
     *
     * @param string $email
     * @return \Dibi\Row|false
     */
    public function getByEmail(string $email);


    /**
     * Insert.
     *
     * @param array $values
     * @return int
     */
    public function insert(array $values): int;


    /**
     * Update.
     *
     * @param int   $id
     * @param array $values
     * @return bool
     */
    public function update(int $id, array $values): bool;


    /**
     * Delete.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;


    /**
     * Get hash.
     *
     * @param string $password
     * @return string
     */
    public function getHash(string $password): string;


    /**
     * Verify hash.
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyHash(string $password, string $hash): bool;


    /**
     * Exist login.
     *
     * @param string $login
     * @return int
     */
    public function existLogin(string $login): int;


    /**
     * Exist email.
     *
     * @param string $email
     * @return int
     */
    public function existEmail(string $email): int;


    /**
     * Clean user.
     *
     * @param string|null $validate
     * @return int
     */
    public function cleanUser(string $validate = null): int;


    /**
     * Get encode hash.
     *
     * @param int         $id
     * @param string      $login
     * @param string|null $linkValidate
     * @return string
     */
    public function getEncodeHash(int $id, string $login, string $linkValidate = null): string;


    /**
     * Get decode link.
     *
     * @param string $hash
     * @return array
     * @throws IdentityException
     */
    public function getDecodeHash(string $hash): array;


    /**
     * Process approve.
     *
     * @param string $hash
     * @return bool
     * @throws IdentityException
     * @throws \Dibi\Exception
     */
    public function processApprove(string $hash): bool;


    /**
     * Is valid forgotten.
     *
     * @param string $hash
     * @return bool
     * @throws IdentityException
     */
    public function isValidForgotten(string $hash): bool;


    /**
     * Process forgotten.
     *
     * @param string $hash
     * @param string $password
     * @return bool
     * @throws IdentityException
     * @throws \Dibi\Exception
     */
    public function processForgotten(string $hash, string $password): bool;
}
