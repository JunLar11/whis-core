<?php

namespace Whis\Cli\Commands;

use Whis\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetAppUrl extends Command
{
    protected static $defaultName = 'env:ip';

    protected static $defaultDescription = 'Update APP_URL in .env using the current local IP address';

    protected function configure(): void
    {
        $this
            ->addOption(
                'host',
                null,
                InputOption::VALUE_OPTIONAL,
                'IP address to use manually. If omitted, Whis will detect it automatically.'
            )
            ->addOption(
                'scheme',
                null,
                InputOption::VALUE_OPTIONAL,
                'URL scheme',
                'http'
            )
            ->addOption(
                'port',
                null,
                InputOption::VALUE_OPTIONAL,
                'Optional port to append to APP_URL'
            )
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Environment file path relative to project root',
                '.env'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = $input->getOption('host') ?: $this->detectLocalIp();

        if ($host === null) {
            $output->writeln('<error>Could not detect local IP address.</error>');
            $output->writeln('<comment>Try passing it manually:</comment>');
            $output->writeln('<comment>php whis env:ip --host=192.168.68.63</comment>');

            return Command::FAILURE;
        }

        $scheme = trim((string) $input->getOption('scheme')) ?: 'http';
        $port = $input->getOption('port');
        $file = trim((string) $input->getOption('file')) ?: '.env';

        $url = $scheme . '://' . $host;

        if ($port !== null && trim((string) $port) !== '') {
            $url .= ':' . trim((string) $port);
        }

        $envPath = rtrim(App::$root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;

        if (!file_exists($envPath)) {
            $output->writeln("<error>.env file not found: {$envPath}</error>");

            return Command::FAILURE;
        }

        $content = file_get_contents($envPath);

        if ($content === false) {
            $output->writeln("<error>Could not read .env file.</error>");

            return Command::FAILURE;
        }

        $newContent = $this->setEnvValue($content, 'APP_URL', $url);

        if (file_put_contents($envPath, $newContent) === false) {
            $output->writeln("<error>Could not write .env file.</error>");

            return Command::FAILURE;
        }

        $output->writeln("<info>APP_URL updated successfully:</info>");
        $output->writeln("<comment>APP_URL={$url}</comment>");

        return Command::SUCCESS;
    }

    private function setEnvValue(string $content, string $key, string $value): string
    {
        $line = $key . '=' . $value;

        if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $content)) {
            return preg_replace(
                '/^' . preg_quote($key, '/') . '=.*/m',
                $line,
                $content
            ) ?? $content;
        }

        $content = rtrim($content);

        return $content . PHP_EOL . $line . PHP_EOL;
    }

    private function detectLocalIp(): ?string
    {
        return $this->detectUsingUdpSocket()
            ?? $this->detectUsingHostname()
            ?? $this->detectUsingSystemCommand();
    }

    private function detectUsingUdpSocket(): ?string
    {
        $socket = @stream_socket_client(
            'udp://8.8.8.8:80',
            $errno,
            $errstr,
            1
        );

        if (!$socket) {
            return null;
        }

        $name = stream_socket_get_name($socket, false);

        fclose($socket);

        if (!is_string($name) || $name === '') {
            return null;
        }

        $parts = explode(':', $name);

        $ip = $parts[0] ?? null;

        return $this->isValidLocalIp($ip) ? $ip : null;
    }

    private function detectUsingHostname(): ?string
    {
        $ip = gethostbyname(gethostname());

        return $this->isValidLocalIp($ip) ? $ip : null;
    }

    private function detectUsingSystemCommand(): ?string
    {
        $command = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? 'ipconfig'
            : 'hostname -I';

        $output = shell_exec($command);

        if (!is_string($output) || $output === '') {
            return null;
        }

        preg_match_all(
            '/\b(?:192\.168|10\.|172\.(?:1[6-9]|2\d|3[0-1]))\.\d{1,3}\.\d{1,3}\b/',
            $output,
            $matches
        );

        foreach ($matches[0] ?? [] as $ip) {
            if ($this->isValidLocalIp($ip)) {
                return $ip;
            }
        }

        return null;
    }

    private function isValidLocalIp(?string $ip): bool
    {
        if (!is_string($ip)) {
            return false;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        if (str_starts_with($ip, '127.')) {
            return false;
        }

        if (str_starts_with($ip, '169.254.')) {
            return false;
        }

        return str_starts_with($ip, '192.168.')
            || str_starts_with($ip, '10.')
            || preg_match('/^172\.(1[6-9]|2\d|3[0-1])\./', $ip) === 1;
    }
}