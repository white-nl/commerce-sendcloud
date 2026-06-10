<?php

namespace white\commerce\sendcloud\models;

use yii\base\Arrayable;
use yii\base\ArrayableTrait;

class Address implements Arrayable
{
    use ArrayableTrait {
        toArray as traitToArray;
    }

    public static function fromParcelData(array $data): self
    {
        return new self(
            $data['name'],
            $data['address'],
            $data['postal_code'],
            $data['city'],
            $data['country']['iso_2'],
            $data['company_name'] ?? null,
            $data['address_divided']['house_number'] ?? null,
            $data['address_2'] ?? null,
            $data['po_box'] ?? null,
            $data['country_state'] ?? null,
            $data['email'] ?? null,
            $data['telephone'] ?? null,
        );
    }

    /**
     * @param string $name Name of the person associated with the address
     * @param string $addressLine1 First line of the address
     * @param string $postalCode Zip code of the address
     * @param string $city City of the recipient
     * @param string $countryCode The country code of the customer represented as ISO 3166-1 alpha-2
     * @param string|null $companyName Name of the company associated with the address
     * @param string|null $houseNumber House number of the recipient
     * @param string|null $addressLine2 Additional address information, e.g. 2nd level
     * @param string|null $poBox Code required in case of PO Box or post locker delivery
     * @param string|null $stateProvinceCode The character state code of the customer represented as ISO 3166-2 code
     * @param string|null $email Email address of the person associated with the address
     * @param string|null $phoneNumber Phone number of the person associated with the address
     */
    public function __construct(
        protected string  $name,
        protected string  $addressLine1,
        protected string  $postalCode,
        protected string  $city,
        protected string  $countryCode,
        protected ?string $companyName = null,
        protected ?string $houseNumber = null,
        protected ?string $addressLine2 = null,
        protected ?string $poBox = null,
        protected ?string $stateProvinceCode = null,
        protected ?string $email = null,
        protected ?string $phoneNumber = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1): void
    {
        $this->addressLine1 = $addressLine1;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    public function setHouseNumber(?string $houseNumber): void
    {
        $this->houseNumber = $houseNumber;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $addressLine2): void
    {
        $this->addressLine2 = $addressLine2;
    }

    public function getPoBox(): ?string
    {
        return $this->poBox;
    }

    public function setPoBox(?string $poBox): void
    {
        $this->poBox = $poBox;
    }

    public function getStateProvinceCode(): ?string
    {
        return $this->stateProvinceCode;
    }

    public function setStateProvinceCode(?string $stateProvinceCode): void
    {
        $this->stateProvinceCode = $stateProvinceCode;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function fields(): array
    {
        return [
            'name',
            'address_line_1' => 'addressLine1',
            'postal_code' => 'postalCode',
            'city',
            'country_code' => 'countryCode',
            'company_name' => 'companyName',
            'house_number' => 'houseNumber',
            'address_line_2' => 'addressLine2',
            'po_box' => 'poBox',
            'state_province_code' => 'stateProvinceCode',
            'email',
            'phone_number' => 'phoneNumber',
        ];
    }
}
