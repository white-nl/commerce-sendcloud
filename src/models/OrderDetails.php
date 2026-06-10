<?php

namespace white\commerce\sendcloud\models;

use DateTime;
use yii\base\Arrayable;
use yii\base\ArrayableTrait;

class OrderDetails implements Arrayable
{
    use ArrayableTrait {
        toArray as traitToArray;
    }

    private int $integrationId;

    private array $status;

    private DateTime $createdAt;

    private ?DateTime $updatedAt;

    /** @var OrderItem[] $orderItems */
    private array $orderItems;

    private ?string $notes;

    /** @var string[]|null  */
    private ?array $tags;

    public function getIntegrationId(): int
    {
        return $this->integrationId;
    }

    public function setIntegrationId(int $integrationId): void
    {
        $this->integrationId = $integrationId;
    }

    public function getStatus(): array
    {
        return $this->status;
    }

    public function setStatus(array $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return OrderItem[]
     */
    public function getOrderItems(): array
    {
        return $this->orderItems;
    }

    /**
     * @param OrderItem[] $orderItems
     * @return void
     */
    public function setOrderItems(array $orderItems): void
    {
        $this->orderItems = $orderItems;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): void
    {
        $this->tags = $tags;
    }

    public function fields(): array
    {
        return [
            'integration' => fn() => ['id' => $this->integrationId],
            'status',
            'order_created_at' => fn() => $this->createdAt->format(DATE_ATOM),
            'order_items' => 'orderItems',
            'order_updated_at' => fn() => $this->updatedAt?->format(DATE_ATOM),
            'notes',
            'tags',
        ];
    }
}
