<?php

namespace white\commerce\sendcloud\models;

final class Address extends \JouwWeb\Sendcloud\Model\Address implements \Stringable
{
    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string|null $companyName
     * @return void
     */
    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    /**
     * @param string|null $addressLine1
     * @return void
     */
    public function setAddressLine1(?string $addressLine1): void
    {
        $this->addressLine1 = $addressLine1;
    }

    /**
     * @param string $street
     * @return void
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    /**
     * @param ?string $houseNumber
     * @return void
     */
    public function setHouseNumber(?string $houseNumber = null): void
    {
        $this->houseNumber = $houseNumber;
    }

    /**
     * @param string $city
     * @return void
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * @param string $postalCode
     * @return void
     */
    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    /**
     * @param string $countryCode
     * @return void
     */
    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    /**
     * @param string $emailAddress
     * @return void
     */
    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @param null|string $phoneNumber
     * @return void
     */
    public function setPhoneNumber(?string $phoneNumber = null): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @param null|string $addressLine2
     * @Return void
     */
    public function setAddressLine2(?string $addressLine2 = null): void
    {
        $this->addressLine2 = $addressLine2;
    }

    /**
     * @param null|string $countryStateCode
     * @return void
     */
    public function setCountryStateCode(?string $countryStateCode): void
    {
        $this->countryStateCode = $countryStateCode;
    }
}
