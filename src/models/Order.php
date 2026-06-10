<?php

namespace white\commerce\sendcloud\models;

use yii\base\Arrayable;
use yii\base\ArrayableTrait;

class Order implements Arrayable
{
    use ArrayableTrait {
        toArray as traitToArray;
    }

    private ?int $id;

    private string $orderId;

    private ?string $createdAt;

    private ?string $modifiedAt;

    private string $orderNumber;

    private OrderDetails $orderDetails;

    private array $paymentDetails;

    private ?array $customsDetails;

    private ?array $customerDetails;

    private ?Address $billingAddress;

    private ?Address $shippingAddress;

    private ?array $shippingDetails;

    private ?array $servicePointDetails;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getModifiedAt(): ?string
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?string $modifiedAt): void
    {
        $this->modifiedAt = $modifiedAt;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getOrderDetails(): OrderDetails
    {
        return $this->orderDetails;
    }

    public function setOrderDetails(OrderDetails $orderDetails): void
    {
        $this->orderDetails = $orderDetails;
    }

    public function getPaymentDetails(): array
    {
        return $this->paymentDetails;
    }

    public function setPaymentDetails(array $paymentDetails): void
    {
        $this->paymentDetails = $paymentDetails;
    }

    public function getCustomsDetails(): ?array
    {
        return $this->customsDetails;
    }

    public function setCustomsDetails(?array $customsDetails): void
    {
        $this->customsDetails = $customsDetails;
    }

    public function getCustomerDetails(): ?array
    {
        return $this->customerDetails;
    }

    public function setCustomerDetails(?array $customerDetails): void
    {
        $this->customerDetails = $customerDetails;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Address $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?Address $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function getShippingDetails(): ?array
    {
        return $this->shippingDetails;
    }

    public function setShippingDetails(?array $shippingDetails): void
    {
        $this->shippingDetails = $shippingDetails;
    }

    public function getServicePointDetails(): ?array
    {
        return $this->servicePointDetails;
    }

    public function setServicePointDetails(?array $servicePointDetails): void
    {
        $this->servicePointDetails = $servicePointDetails;
    }

    public function fields(): array
    {
        return [
            'id',
            'order_id' => 'orderId',
            'order_number' => 'orderNumber',
            'order_details' => 'orderDetails',
            'payment_details' => 'paymentDetails',
            'customs_details' => 'customsDetails',
            'customer_details' => 'customerDetails',
            'billing_address' => 'billingAddress',
            'shipping_address' => 'shippingAddress',
            'shipping_details' => 'shippingDetails',
            'service_point_details' => 'servicePointDetails',
        ];
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = $this->traitToArray($fields, $expand, $recursive);
        return array_filter($data, fn($value) => !is_null($value));
    }
}
