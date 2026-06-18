<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Bridge\Symfony\Bundle\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use SwooleBundle\SwooleBundle\Bridge\Symfony\Bundle\Command\ServerExecutionCommand;
use SwooleBundle\SwooleBundle\Bridge\Symfony\Bundle\Command\ServerStartCommand;
use SwooleBundle\SwooleBundle\Server\Config\Socket;
use SwooleBundle\SwooleBundle\Server\Config\Sockets;
use SwooleBundle\SwooleBundle\Server\HttpServerConfiguration;

final class ServerExecutionCommandTest extends TestCase
{
    /**
     * The server start command must forward EVERY configured socket (main, optional API
     * and any additional listeners registered by third-party libraries via the Sockets service) to
     * the HttpServerFactory, so each one is actually bound as a listener.
     *
     * @param list<int> $expectedPorts
     */
    #[DataProvider('socketsProvider')]
    public function testAllConfiguredSocketsAreForwardedForBinding(Sockets $sockets, array $expectedPorts): void
    {
        $configuration = $this->createStub(HttpServerConfiguration::class);
        $configuration->method('getSockets')->willReturn($sockets);

        $command = (new ReflectionClass(ServerStartCommand::class))->newInstanceWithoutConstructor();
        $configurationProperty = new ReflectionProperty(ServerExecutionCommand::class, 'serverConfiguration');
        $configurationProperty->setValue($command, $configuration);

        $socketsToBind = new ReflectionMethod(ServerExecutionCommand::class, 'socketsToBind');
        /** @var list<Socket> $result */
        $result = $socketsToBind->invoke($command);

        $actualPorts = array_map(static fn(Socket $socket): int => $socket->port(), $result);
        self::assertSame($expectedPorts, $actualPorts);
    }

    /**
     * @return iterable<string, array{Sockets, list<int>}>
     */
    public static function socketsProvider(): iterable
    {
        yield 'server socket only' => [
            new Sockets(new Socket('0.0.0.0', 9501)),
            [9501],
        ];

        yield 'server and api socket' => [
            new Sockets(new Socket('0.0.0.0', 9501), new Socket('0.0.0.0', 9200)),
            [9501, 9200],
        ];

        // The case that exposed the bug: an additional (e.g. dedicated gRPC) listener with no API.
        yield 'server and additional socket without api' => [
            new Sockets(new Socket('0.0.0.0', 9501), null, new Socket('0.0.0.0', 9502)),
            [9501, 9502],
        ];

        yield 'server, api and additional socket' => [
            new Sockets(
                new Socket('0.0.0.0', 9501),
                new Socket('0.0.0.0', 9200),
                new Socket('0.0.0.0', 9502),
            ),
            [9501, 9200, 9502],
        ];
    }
}
