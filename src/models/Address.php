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
            $data['city'],
            $data['postal_code'],
            $data['country']['iso_2'],
        $data['company_name'] ?? null,
            $data['address_2'],
        $data['address_divided']['house_number'] ?? null,
        $data['telephone'] ?? null,
            $data['country_state'] ?? null,
        );
    }

    /**
     * @param string $name Name of the recipient
     * @param string $address Address of the recipient
     * @param string $city City of the recipient
     * @param string $postalCode Zip code of the recipient
     * @param string $country Country of the recipient
     * @param string|null $companyName Company name of the recipient the parcel will be shipped to
     * @param string|null $address2 Additional address information, e.g. 2nd level
     * @param string|null $houseNumber House number of the recipient
     * @param string|null $telephone Phone number of the recipient
     * @param string|null $countryState Code of the state (e.g. NY for New York) or province (e.g. RM for Rome). Destinations that require this field are USA, Canada, Italy and Australia. Errors related to this field will mention the to_state field.
     */
    public function __construct(
        protected string $name,
        protected string $address,
        protected string $city,
        protected string $postalCode,
        protected string $country,
        protected ?string $companyName = null,
        protected ?string $address2 = null,
        protected ?string $houseNumber = null,
        protected ?string $telephone = null,
        protected ?string $countryState = null,
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

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    public function setAddress2(?string $address2): void
    {
        $this->address2 = $address2;
    }

    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    public function setHouseNumber(?string $houseNumber): void
    {
        $this->houseNumber = $houseNumber;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getCountryState(): ?string
    {
        return $this->countryState;
    }

    public function setCountryState(?string $countryState): void
    {
        $this->countryState = $countryState;
    }

    public function fields()
    {
        return [
            'name',
            'address',
            'address_2' => 'address2',
            'city',
            'company_name' => 'companyName',
            'country' => 'country',
            'postal_code' => 'postalCode',
            'telephone' => 'telephone',
            'country_state' => 'countryState',
        ];
    }
}
