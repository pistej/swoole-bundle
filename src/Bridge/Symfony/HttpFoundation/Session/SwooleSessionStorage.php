<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Bridge\Symfony\HttpFoundation\Session;

use Assert\Assertion;
use Assert\AssertionFailedException;
use Exception;
use InvalidArgumentException;
use LogicException as CoreLogicException;
use SwooleBundle\SwooleBundle\Server\Session\Exception\LogicException;
use SwooleBundle\SwooleBundle\Server\Session\Exception\RuntimeException;
use SwooleBundle\SwooleBundle\Server\Session\Storage;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

final class SwooleSessionStorage implements SessionStorageInterface
{
    public const DEFAULT_SESSION_NAME = 'SWOOLESSID';

    private string $currentId = '';

    /**
     * @var array<SessionBagInterface>
     */
    private array $bags = [];

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    private MetadataBag $metadataBag;

    private bool $started = false;

    private int $sessionLifetimeSeconds;

    public function __construct(
        private readonly Storage $storage,
        private string $name = self::DEFAULT_SESSION_NAME,
        int $lifetimeSeconds = 86400,
        ?MetadataBag $metadataBag = null,
    ) {
        $this->setLifetimeSeconds($lifetimeSeconds);
        $this->setMetadataBag($metadataBag);
    }

    /**
     * @throws AssertionFailedException
     * @throws Exception
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        if (empty($this->currentId)) {
            $this->currentId = $this->generateId();
        }

        $this->data = $this->obtainSessionData($this->currentId);
        $this->bindBagsToData($this->data);

        $this->started = true;

        return true;
    }

    /**
     * @throws Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function regenerate(bool $destroy = false, ?int $lifetime = null): bool
    {
        if ($destroy) {
            $this->storage->delete($this->currentId);
        }

        if (!headers_sent() && $lifetime !== null && $lifetime !== (int) ini_get('session.cookie_lifetime')) {
            ini_set('session.cookie_lifetime', (string) $lifetime);
        }

        $this->getMetadataBag()->stampNew($lifetime ?? $this->sessionLifetimeSeconds);
        $this->currentId = $this->generateId();

        return true;
    }

    public function save(): void
    {
        if (!$this->started) {
            throw new RuntimeException('Trying to save a session that was not started yet or was already closed');
        }

        $this->storage->set(
            $this->currentId,
            json_encode($this->data, JSON_THROW_ON_ERROR),
            $this->sessionLifetimeSeconds
        );
    }

    public function reset(): void
    {
        foreach ($this->allBags() as $bag) {
            $bag->clear();
        }

        $this->started = false;
        $this->currentId = '';
        $this->data = [];
    }

    public function clear(): void
    {
        $this->storage->delete($this->currentId);
        $this->reset();
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function getId(): string
    {
        return $this->isStarted() ? $this->currentId : '';
    }

    /**
     * @throws Exception
     */
    public function setId(string $id): void
    {
        if ($this->started) {
            throw new LogicException('Cannot set session ID after the session has started.');
        }

        $this->currentId = preg_match('/^[a-f0-9]{63}$/', $id) ? $id : $this->generateId();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @throws AssertionFailedException
     */
    public function getBag(string $name): SessionBagInterface
    {
        if (!isset($this->bags[$name])) {
            throw new InvalidArgumentException(sprintf('The SessionBagInterface `%s` is not registered.', $name));
        }

        if (!$this->started) {
            $this->start();
        }

        return $this->bags[$name];
    }

    public function registerBag(SessionBagInterface $bag): void
    {
        if ($this->started) {
            throw new CoreLogicException('Cannot register a bag when the session is already started.');
        }

        $this->bags[$bag->getName()] = $bag;
    }

    public function getMetadataBag(): MetadataBag
    {
        return $this->metadataBag;
    }

    private function setLifetimeSeconds(int $lifetimeSeconds): void
    {
        $this->sessionLifetimeSeconds = $lifetimeSeconds;

        if (
            headers_sent()
            || !is_string(ini_get('session.cookie_lifetime'))
            || $lifetimeSeconds === (int) ini_get('session.cookie_lifetime')
        ) {
            return;
        }

        ini_set('session.cookie_lifetime', (string) $lifetimeSeconds);
    }

    /**
     * @return array<string, mixed>
     * @throws AssertionFailedException
     */
    private function obtainSessionData(string $sessionId): array
    {
        $sessionData = $this->storage->get($sessionId, function (): void {
            $this->regenerate(true);
        });

        if ($sessionData === null) {
            return [];
        }

        Assertion::string($sessionData);
        /** @var array<string, mixed> $toReturn */
        $toReturn = json_decode($sessionData, true, 512, JSON_THROW_ON_ERROR);

        return $toReturn;
    }

    /**
     * @return iterable<SessionBagInterface>
     */
    private function allBags(): iterable
    {
        yield from $this->bags;
        yield $this->metadataBag;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function bindBagsToData(array &$data): void // phpcs:ignore
    {
        foreach ($this->allBags() as $bag) {
            $key = $bag->getStorageKey();
            $data[$key] ??= [];
            Assertion::isArray($data[$key]);
            $bag->initialize($data[$key]);
        }
    }

    /**
     * Generates a session ID.
     *
     * @throws Exception
     */
    private function generateId(): string
    {
        return mb_substr(bin2hex(random_bytes(32)), random_int(0, 1), 63);
    }

    private function setMetadataBag(?MetadataBag $metadataBag = null): void
    {
        if (!$metadataBag instanceof MetadataBag) {
            $metadataBag = new MetadataBag();
        }

        $this->metadataBag = $metadataBag;
    }
}
