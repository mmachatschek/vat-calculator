VatCalculator
================

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://github.com/mmachatschek/vat-calculator/workflows/tests/badge.svg)](https://github.com/mmachatschek/vat-calculator/actions)

Handle all the hard stuff related to EU MOSS tax/vat regulations, the way it should be.
Can be used with **Laravel 6/7/8 / Cashier** &mdash; or **standalone**.

```php
// Easy to use!
$countryCode = VatCalculator::getIPBasedCountry();
VatCalculator::calculate( 24.00, $countryCode );
VatCalculator::calculate( 24.00, $countryCode, $postalCode );
VatCalculator::calculate( 71.00, 'DE', '41352', $isCompany = true );
VatCalculator::getTaxRateForLocation( 'NL' );
// Check validity of a VAT number
VatCalculator::isValidVATNumber('NL123456789B01');
```
## Contents

- [Installation](#installation)
	- [Standalone](#installation-standalone)
- [Usage](#usage)
	- [Calculate the gross price](#calculate-the-gross-price)
	- [Receive more information](#receive-more-information)
	- [Validate EU VAT numbers](#validate-eu-vat-numbers)
		- [Laravel Validator extension](#laravel-validator-extension)
	- [Get EU VAT number details](#vat-number-details)
	- [Cashier integration](#cashier-integration)
	- [Get the IP based country of your user](#get-ip-based-country)
- [Configuration (optional)](#configuration)
- [Changelog](#changelog)
- [License](#license)

<a name="installation"></a>
## Installation

In order to install the VAT Calculator, just run

```bash
$ composer require machatschek/vat-calculator
```
	
<a name="installation-standalone"></a>
### Standalone

You can also use this package without Laravel. Simply create a new instance of the VAT calculator and use it.
All documentation examples use the Laravel 5 facade code, so make sure not to call the methods as if they were static methods.

Example:

```php
use Mpociot\VatCalculator\VatCalculator;

$vatCalculator = new VatCalculator();
$vatCalculator->setBusinessCountryCode('DE');
$countryCode = $vatCalculator->getIPBasedCountry();
$grossPrice = $vatCalculator->calculate( 49.99, 'LU' );
```

<a name="usage"></a>
## Usage
<a name="calculate-the-gross-price"></a>
### Calculate the gross price
To calculate the gross price use the `calculate` method with a net price and a country code as paremeters.

```php
$grossPrice = VatCalculator::calculate( 24.00, 'DE' );
```
The third parameter is the postal code of the customer.

As a fourth parameter, you can pass in a boolean indicating whether the customer is a company or a private person. If the customer is a company, which you should check by <a href="#validate-eu-vat-numbers">validating the VAT number</a>, the net price gets returned.


```php
$grossPrice = VatCalculator::calculate( 24.00, 'DE', '12345', $isCompany = true );
```
<a name="receive-more-information"></a>
### Receive more information
After calculating the gross price you can extract more information from the VatCalculator.

```php
$grossPrice = VatCalculator::calculate( 24.00, 'DE' ); // 28.56
$taxRate    = VatCalculator::getTaxRate(); // 0.19
$netPrice   = VatCalculator::getNetPrice(); // 24.00
$taxValue   = VatCalculator::getTaxValue(); // 4.56
```

<a name="validate-eu-vat-numbers"></a>
### Validate EU VAT numbers

Prior to validating your customers VAT numbers, you can use the `shouldCollectVAT` method to check if the country code requires you to collect VAT
in the first place.

```php
if (VatCalculator::shouldCollectVAT('DE')) {

}
```

To validate your customers VAT numbers, you can use the `isValidVATNumber` method.
The VAT number should be in a format specified by the [VIES](http://ec.europa.eu/taxation_customs/vies/faqvies.do#item_11).
The given VAT numbers will be truncated and non relevant characters / whitespace will automatically be removed.

This service relies on a third party SOAP API provided by the EU. If, for whatever reason, this API is unavailable a `VATCheckUnavailableException` will be thrown.

```php
try {
	$validVAT = VatCalculator::isValidVATNumber('NL 123456789 B01');
} catch( VATCheckUnavailableException $e ){
	// Please handle me
}
```

<a name="vat-number-details"></a>
### Get EU VAT number details

To get the details of a VAT number, you can use the `getVATDetails` method.
The VAT number should be in a format specified by the [VIES](http://ec.europa.eu/taxation_customs/vies/faqvies.do#item_11).
The given VAT numbers will be truncated and non relevant characters / whitespace will automatically be removed.

This service relies on a third party SOAP API provided by the EU. If, for whatever reason, this API is unavailable a `VATCheckUnavailableException` will be thrown.

```php
try {
	$vat_details = VatCalculator::getVATDetails('NL 123456789 B01');
	print_r($vat_details);
	/* Outputs
	stdClass Object
	(
		[countryCode] => NL
		[vatNumber] => 123456789B01
		[requestDate] => 2017-04-06+02:00
		[valid] => false
		[name] => Name of the company
		[address] => Address of the company
	)
	*/
} catch( VATCheckUnavailableException $e ){
	// Please handle me
}
```

<a name="cashier-integration"></a>
### Cashier integration
If you want to use this package in combination with [Laravel Cashier](https://github.com/laravel/cashier-stripe/) you can let your billable model use the `BillableWithinTheEU` trait. Because this trait overrides the `getTaxPercent` method of the `Billable` trait, we have to explicitly tell our model to do so.

```php
use Laravel\Cashier\Billable;
use Laravel\Cashier\Contracts\Billable as BillableContract;
use Machatschek\VatCalculator\Traits\BillableWithinTheEU;

class User extends Model implements BillableContract
{
    use Billable, BillableWithinTheEU {
        BillableWithinTheEU::taxPercentage insteadof Billable;
    }

    protected $dates = ['trial_ends_at', 'subscription_ends_at'];
}
```

By using the `BillableWithinTheEU` trait, your billable model has new methods to set the tax rate for the billable model.

Set everything in one command:

- `setTaxForCountry($countryCode, $company = false)`

Or use the more readable, chainable approach:

- `useTaxFrom($countryCode)` &mdash; Use the given countries tax rate
- `asIndividual()` &mdash; The billable model is not a company (default) 
- `asBusiness()` &mdash; The billable model is a valid company

So in order to set the correct tax percentage prior to subscribing your customer, consider the following workflow:

```php
$user = User::find(1);

// For individuals use:
$user->useTaxFrom('NL');

// For business customers with a valid VAT ID, use:
$user->useTaxFrom('NL')->asBusiness();

$user->subscription('monthly')->create($creditCardToken);
```

<a name="get-ip-based-country"></a>
## Get the IP based Country of your user(s)
Right now you'll need to show your users a way to select their country - probably a drop down - to use this country for the VAT calculation.

This package has a small helper function, that tries to lookup the Country of the user, based on the IP they have.

```php
$countryCode = VatCalculator::getIPBasedCountry();
```

The `$countryCode` will either be `false`, if the service is unavailable, or the country couldn't be looked up. Otherwise the variable contains the two-letter country code, which can be used to prefill the user selection.

<a name="configuration"></a>
## Configuration

By default, the VAT Calculator has all EU VAT rules predefined, so that it can easily be updated, if it changes for a specific country.

If you need to define other VAT rates, you can do so by publishing the configuration and add more rules.

The configuration file also determines wether you want to use the VAT Calculator JS routes or not.

**Important:** Be sure to set your business country code in the configuration file, to get correct VAT calculation when selling to business customers in your own country.

To publish the configuration files, run the `vendor:publish` command

```bash
$ php artisan vendor:publish --provider="Machatschek\VatCalculator\VatCalculatorServiceProvider"
```

This will create a `vat_calculator.php` in your config directory.

<a name="changelog"></a>
## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information.


<a name="license"></a>
## License
This library is licensed under the MIT license. Please see [License file](LICENSE.md) for more information.
