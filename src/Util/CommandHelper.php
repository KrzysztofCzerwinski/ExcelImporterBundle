<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Util;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommandHelper
{
    /** @var SymfonyStyle */
    private $io;

    public function setInOut(InputInterface $input, OutputInterface $output): self
    {
        $this->io = new SymfonyStyle($input, $output);

        return $this;
    }

    public function addInfo(string $message): void
    {
        $this->io->block($message, 'info', 'fg=white;bg=blue');
    }

    public function addSuccess(string $message): void
    {
        $this->io->success($message);
    }

    public function addWarning(string $message): void
    {
        $this->io->warning($message);
    }

    /**
     * @param string $question
     * @param array $choices
     * @param mixed $default
     *
     * @return mixed
     */
    public function retrieveFromRange(string $question, array $choices, $default)
    {
        return $this->io->choice($question, $choices, $default);
    }

    public function retrieveString(string $question): ?string
    {
        return $this->io->ask($question, null, static function (?string $value): ?string {
            return $value;
        });
    }

    public function retrieveNonEmptyString(string $question, string $default = null): string
    {
        return $this->io->ask($question, $default, static function (?string $value): string {
            if (null !== $value) {

                return $value;
            }

            throw new RuntimeException('Non empty value required');
        });
    }

    public function retrieveBoolean(string $question, $default = true): bool
    {
        return $this->io->confirm($question, $default);
    }
}