<?php declare(strict_types=1);

namespace Lex\Notifications\Domain\Model;

use Carbon\Carbon;
use DateTime;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class DatabaseNotification extends AbstractEntity
{
    protected string $type = '';
    protected string $notifiableType = '';
    protected int $level = 0;
    protected int $notifiableId = 0;
    protected ?string $data = null;
    protected ?DateTime $readAt = null;

    protected ?DateTime $createdAt = null;

    public function getType(): string
    {
        return $this->type;
    }

    public function getNotifiableType(): string
    {
        return $this->notifiableType;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getNotifiableId(): int
    {
        return $this->notifiableId;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getReadAt(): ?DateTime
    {
        return $this->readAt;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function setNotifiableType(string $notifiableType): static
    {
        $this->notifiableType = $notifiableType;
        return $this;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function setNotifiableId(int $notifiableId): static
    {
        $this->notifiableId = $notifiableId;
        return $this;
    }

    public function setData(?string $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function setReadAt(?DateTime $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function setCreatedAt(?DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getDataAsArray(): array
    {
        return $this->data? json_decode($this->data, true) : [];
    }

    public function setDataFromArray(array $data): static
    {
        $this->data = json_encode($data);
        return $this;
    }

    public function markAsRead(): static
    {
        $this->readAt = new DateTime();
        return $this;
    }

    public function getDiffCreatedAtForHumans(): ?string
    {
        return $this->createdAt? Carbon::instance($this->createdAt)->locale('fr')->diffForHumans() : null;
    }
}