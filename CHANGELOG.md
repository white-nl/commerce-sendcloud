# Release Notes for Craft Sendcloud Plugin

## 1.1.0 - 2022-06-08

### Changed
- Moved the creation of Sendcloud parcels to a queue job

## 1.0.6 - 2022-05-31

### Added
- Added caching for retrieving the shipping methods from Sendcloud

## 1.0.5 - 2022-02-23

### Changed
- Total order value added to the parcel data.
- Address line 3 now gets appended to the house number.

## 1.0.4 - 2021-11-09

### Fixed
- Total order weight calculation fixed.

## 1.0.3 - 2021-09-13

### Fixed
- Remove service point information if the shipping method doesn't match any Sendcloud method

## 1.0.2 - 2021-08-13

### Fixed
- Proper weight unit conversion depending on your Craft settings.

## 1.0.1 - 2021-07-12

### Added
- Customs invoice number and shipment type support.

### Changed
- Dependencies updated to improve Guzzle version compatibility.
- Plugin settings is now using order status handles instead of IDs to provide better inter-environment compatibility. **Please double-check your plugin settings after upgrading.**

### Fixed
- Race condition in the webhook handler.

## 1.0.0 - 2021-07-05

- Initial release.
