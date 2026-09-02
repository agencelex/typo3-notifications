<?php declare(strict_types=1);

namespace Lex\Notifications\Domain\Model;

use DateTime;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Message extends AbstractEntity
{
    protected int $level = 0;

    protected string $subject = '';

    protected string $message = '';

    protected string $link = '';

    protected ?DateTime $sentAt = null;

    protected string $receivers = '';

    protected string $excludedRecipients = '';

    protected string $channels = '';

    protected ?int $cruser = null;

    protected ?DateTime $crdate = null;

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getSentAt(): ?DateTime
    {
        return $this->sentAt;
    }

    public function getReceivers(): string
    {
        return $this->receivers;
    }

    public function getExcludedRecipients(): string
    {
        return $this->excludedRecipients;
    }

    public function getChannels(): string
    {
        return $this->channels;
    }

    public function getCruser(): ?int
    {
        return $this->cruser;
    }

    public function getCrdate(): ?DateTime
    {
        return $this->crdate;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function setLink(string $link): static
    {
        $this->link = $link;
        return $this;
    }

    public function setSentAt(?DateTime $readAt): static
    {
        $this->sentAt = $readAt;
        return $this;
    }

    public function setReceivers(string $receivers): static
    {
        $this->receivers = $receivers;
        return $this;
    }

    public function setExcludedRecipients(string $excludedRecipients): static
    {
        $this->excludedRecipients = $excludedRecipients;
        return $this;
    }

    public function setChannels(string $channels): static
    {
        $this->channels = $channels;
        return $this;
    }

    public function setCruser(?int $cruser): static
    {
        $this->cruser = $cruser;
        return $this;
    }

    public function setCrdate(?DateTime $crdate): static
    {
        $this->crdate = $crdate;
        return $this;
    }
}