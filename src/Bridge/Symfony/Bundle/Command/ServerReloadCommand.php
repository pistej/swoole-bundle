<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Bridge\Symfony\Bundle\Command;

use Assert\Assertion;
use Assert\AssertionFailedException;
use SwooleBundle\SwooleBundle\Server\HttpServer;
use SwooleBundle\SwooleBundle\Server\HttpServerConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;

final class ServerReloadCommand extends Command
{
    use ParametersHelper;

    public function __construct(
        private readonly HttpServer $server,
        private readonly HttpServerConfiguration $serverConfiguration,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(
            "Reload Swoole HTTP server's workers running in the background. "
            . "It will reload only classes not loaded before server initialization."
        )
            ->addOption(
                'pid-file',
                null,
                InputOption::VALUE_REQUIRED,
                'Pid file',
                $this->getProjectDirectory() . '/var/swoole.pid'
            );
    }

    /**
     * @throws AssertionFailedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $pidFile = $input->getOption('pid-file');
        Assertion::nullOrString($pidFile);

        $this->serverConfiguration->daemonize($pidFile);

        try {
            $this->server->reload();
        } catch (Throwable $ex) {
            $io->error($ex->getMessage());
            exit(1);
        }

        $io->success('Swoole HTTP Server\'s workers reloaded successfully');

        return 0;
    }
}
