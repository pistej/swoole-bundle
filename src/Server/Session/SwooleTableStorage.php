<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Session;

use Assert\Assertion;
use Assert\AssertionFailedException;
use Swoole\Table;

/**
 * Class SwooleTableStorage.
 *
 * @experimental
 */
final class SwooleTableStorage implements Storage
{
    public const MAX_KEY_BYTES = 63;
    private const TABLE_COLUMN_DATA = 'data';
    private const TABLE_COLUMN_EXPIRES_AT = 'expires_at';

    private $sharedMemory;
    private $maxSessionDataBytes;

    public function __construct(Table $sharedMemory, int $maxSessionDataBytes = 1024)
    {
        $sharedMemory->column(self::TABLE_COLUMN_DATA, Table::TYPE_STRING, $maxSessionDataBytes);
        $sharedMemory->column(self::TABLE_COLUMN_EXPIRES_AT, Table::TYPE_INT, 8);
        $sharedMemory->create();

        $this->sharedMemory = $sharedMemory;
        $this->maxSessionDataBytes = $maxSessionDataBytes;
    }

    public static function fromDefaults(
        int $maxActiveSessions = 1024,
        int $maxSessionDataBytes = 1024,
        float $tableConflictProportion = 0.2,
    ): self {
        return new self(
            new Table($maxActiveSessions, $tableConflictProportion),
            $maxSessionDataBytes
        );
    }

    /**
     * @throws AssertionFailedException
     */
    public function set(string $key, mixed $data, int $ttl): void
    {
        Assertion::greaterThan($ttl, 0, 'Provided TTL "%d" is not a positive number.');
        Assertion::string($data, 'Storage data expected to be string, type %$2s given.');
        Assertion::maxLength(
            $key,
            self::MAX_KEY_BYTES,
            'Storage key must not exceed %2$d bytes, has %3$d. Key value is "%1$s".',
            null,
            '8bit'
        );
        Assertion::maxLength(
            $key,
            $this->maxSessionDataBytes,
            'Storage data must not exceed %2$d bytes, has %3$d.',
            null,
            '8bit'
        );

        $this->sharedMemory->set($key, [
            self::TABLE_COLUMN_DATA => $data,
            self::TABLE_COLUMN_EXPIRES_AT => time() + $ttl,
        ]);
    }

    public function delete(string $key): void
    {
        $this->sharedMemory->del($key);
    }

    public function garbageCollect(): void
    {
        foreach ($this->sharedMemory as $key => $row) {
            /** @var int $expiresAt */
            $expiresAt = $row[self::TABLE_COLUMN_EXPIRES_AT];
            if (time() < $expiresAt) {
                continue;
            }

            $this->sharedMemory->del($key);
        }
    }

    /**
     * @return string|null Session storage data as string or null when data is not available or expired
     */
    public function get(string $key, ?callable $expired = null): ?string
    {
        if (!$this->sharedMemory->exists($key)) {
            return null;
        }

        /** @var array{expires_at: int, data: string}&Table\Row $row */
        $row = $this->sharedMemory->get($key);

        /** @var int $expiresAt */
        $expiresAt = $row[self::TABLE_COLUMN_EXPIRES_AT];

        /** @var string $data */
        $data = $row[self::TABLE_COLUMN_DATA];

        if (time() >= $expiresAt) {
            if ($expired !== null) {
                $expired($key, $data);
            }

            return null;
        }

        return $data;
    }
}
