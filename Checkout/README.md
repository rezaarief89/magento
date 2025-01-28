# Mage2 Module KTech Order

    ``ktech/module-order``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Send Order to Proseller

## Installation

### Type 1: Zip file

 - Unzip the zip file in `app/code/KTech`
 - Enable the module by running `php bin/magento module:enable KTech_Order`
 - Apply database updates by running `php bin/magento setup:upgrade --keep-generated`
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require ktech/module-order`
 - enable the module by running `php bin/magento module:enable KTech_Order`
 - apply database updates by running `php bin/magento setup:upgrade --keep-generated`
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - Observer
	- CheckoutSuccess > KTech\Checkout\Observer\CheckoutSuccess

 - Observer
	- OrderSaveAfter > KTech\Checkout\Observer\OrderSaveAfter


## Attributes

 - Sales - Preseller Order Id (preseller_order_id)

