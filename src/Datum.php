<?php

namespace TusPhp;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final readonly class Datum
{
    private function __construct(private DateTimeImmutable $dateTime)
    {
    }

    public static function fromRfc7231(string $rfc7231String): self
    {
        $dt = DateTimeImmutable::createFromFormat(
            DATE_RFC7231,
            $rfc7231String,
            new DateTimeZone('UTC')
        );

        if ($dt === false) {
            throw new RuntimeException(
                sprintf('Unable to parse RFC‑7231 date: %s', $rfc7231String)
            );
        }

        return new self($dt);
    }

    public static function now(): self
    {
        return new self(new DateTimeImmutable('now', new DateTimeZone('UTC')));
    }

    public function addSeconds(int $seconds): self
    {
        $newDt = $this->dateTime->modify("+$seconds seconds");
        return new self($newDt);
    }

    public function formatRfc7231(): string
    {
        return $this->dateTime->format(DATE_RFC7231);
    }

    public function isExpired(): bool
    {
        return $this->dateTime < (new DateTimeImmutable('now', new DateTimeZone('UTC')));
    }

    public function format(string $format): string
    {
        return $this->dateTime->format($format);
    }
}