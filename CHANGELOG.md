# Release Notes for Craft Sendcloud Plugin

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
