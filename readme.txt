=== LTL Freight Quotes - R+L Carriers Edition ===
Contributors: enituretechnology
Tags: eniture,R+L,,LTL freight rates,LTL freight quotes, shipping estimates
Requires at least: 6.4
Tested up to: 6.6.2
Stable tag: 3.3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Real-time LTL freight quotes from R+L carriers. Fifteen day free trial.

== Description ==

R+L Carriers is a well respected family owned business providing global transportation and logistics services. Headquartered in Wilmington, Ohio, it specializes in less-than-truckload freight.  This application retrieves your negotiated R+L LTL freight rates, takes action on them according to the application settings, and displays the result as shipping charges in the Shopify checkout process. If you don’t have an R+L Carriers account, contact them at 800-543-5589, or request that someone contact you by filling out form[http://www2.rlcarriers.com/contact/contactform].

**Key Features**

* Displays negotiated LTL shipping rates in the shopping cart.
* Provide quotes for shipments within the United States and to Canada.
* Custom label results displayed in the shopping cart.
* Display transit times with returned quotes.
* Product specific freight classes.
* Support for variable products.
* Define multiple warehouses.
* Identify which products drop ship from vendors.
* Product specific shipping parameters: weight, dimensions, freight class.
* Option to determine a product's class by using the built in density calculator.
* Option to include residential delivery fees.
* Option to include fees for lift gate service at the destination address.
* Option to mark up quoted rates by a set dollar amount or percentage.
* Works seamlessly with other quoting apps published by Eniture Technology.

**Requirements**

* WooCommerce 6.4 or newer.
* Your username and password to rlcarriers.com
* Your R+L Carriers web services authentication API key.
* A API key from Eniture Technology.

== Installation ==

**Installation Overview**

Before installing this plugin you should have the following information handy:

* Your R+L Carriers account number.
* Your username and password to rlcarriers.com
* Your R+L Carriers web services authentication key.

If you need assistance obtaining any of the above information, contact your local R+L Carriers office
or call the [R+L Carriers](rlcarriers.com) corporate headquarters at 800-543-5589.

A more comprehensive and graphically illustrated set of instructions can be found on the *Documentation* tab at
[eniture.com](https://eniture.com/woocommerce-r+l-carriers/).

**1. Install and
activate the plugin**
In your WordPress dashboard, go to Plugins => Add New. Search for "LTL Freight Quotes - R+L Carriers Edition", and click Install Now.
After the installation process completes, click the Activate Plugin link to activate the plugin.

**2. Get a API key from Eniture Technology**
Go to [Eniture Technology](https://eniture.com/woocommerce-r+l-ltl-freight/) and pick a
subscription package. When you complete the registration process you will receive an email containing your API key and
your login to eniture.com. Save your login information in a safe place. You will need it to access your customer dashboard
where you can manage your API keys and subscriptions. A credit card is not required for the free trial. If you opt for the free
trial you will need to login to your [Eniture Technology](http://eniture.com) dashboard before the trial period expires to purchase
a subscription to the API key. Without a paid subscription, the plugin will stop working once the trial period expires.

**3. Establish the connection**
Go to WooCommerce => Settings => R+L Freight. Use the *Connection* link to create a connection to your R+L account.

**5. Select the plugin settings**
Go to WooCommerce => Settings => R+L Freight. Use the *Quote Settings* link to enter the required information and choose
the optional settings.

**6. Enable the plugin**
Go to WooCommerce => Settings => Shipping. Click on the link for R+L Freight and enable the plugin.

**7. Configure your products**
Assign each of your products and product variations a weight, Shipping Class and freight classification. Products shipping LTL freight should have the Shipping Class set to “LTL Freight”. The Freight Classification should be chosen based upon how the product would be classified in the NMFC Freight Classification Directory. If you are unfamiliar with freight classes, contact the carrier and ask for assistance with properly identifying the freight classes for your  products. 

== Frequently Asked Questions ==

= What happens when my shopping cart contains products that ship LTL and products that would normally ship FedEx or UPS? =

If the shopping cart contains one or more products tagged to ship LTL freight, all of the products in the shopping cart 
are assumed to ship LTL freight. To ensure the most accurate quote possible, make sure that every product has a weight, dimensions and a freight classification recorded.

= What happens if I forget to identify a freight classification for a product? =

In the absence of a freight class, the plugin will determine the freight classification using the density calculation method. To do so the products weight and dimensions must be recorded. This is accurate in most cases, however identifying the proper freight class will be the most reliable method for ensuring accurate rate estimates.

= Why was the invoice I received from R+L Carriers more than what was quoted by the plugin? =

One of the shipment parameters (weight, dimensions, freight class) is different, or additional services (such as residential 
delivery, lift gate, delivery by appointment and others) were required. Compare the details of the invoice to the shipping 
settings on the products included in the shipment. Consider making changes as needed. Remember that the weight of the packaging 
materials, such as a pallet, is included by the carrier in the billable weight for the shipment.

= How do I find out what freight classification to use for my products? =

Contact your local R+L Carriers office for assistance. You might also consider getting a subscription to ClassIT offered 
by the National Motor Freight Traffic Association (NMFTA). Visit them online at classit.nmfta.org.

= How do I get a R+L Carriers account? =

Check your phone book for local listings or call 800-543-5589.

= Where do I find my R+L Carriers username and password? =

Usernames and passwords to R+L Carriers’s online shipping system are issued by R+L Carriers. If you have a R+L Carriers account number, go to [rlcarriers.com](http://rlcarriers.com) and click the login link at the top right of the page. You will be redirected to a page where you can register as a new user. If you don’t have a R+L Carriers Freight account, contact the R+L Carriers at 800-543-5589.

= How do I get a API key for my plugin? =

You must register your installation of the plugin, regardless of whether you are taking advantage of the trial period or 
purchased a API key outright. At the conclusion of the registration process an email will be sent to you that will include the 
API key. You can also login to eniture.com using the username and password you created during the registration process 
and retrieve the API key from the My API keys tab.

= How do I change my plugin API key from the trail version to one of the paid subscriptions? =

Login to eniture.com and navigate to the My API keys tab. There you will be able to manage the licensing of all of your 
Eniture Technology plugins.

= How do I install the plugin on another website? =

The plugin has a single site API key. To use it on another website you will need to purchase an additional API key. 
If you want to change the website with which the plugin is registered, login to eniture.com and navigate to the My API keys tab. 
There you will be able to change the domain name that is associated with the API key.

= Do I have to purchase a second API key for my staging or development site? =

No. Each API key allows you to identify one domain for your production environment and one domain for your staging or 
development environment. The rate estimates returned in the staging environment will have the word “Sandbox” appended to them.

= Why isn’t the plugin working on my other website? =

If you can successfully test your credentials from the Connection page (WooCommerce > Settings > R+L Freight > Connections) 
then you have one or more of the following licensing issues:

1) You are using the API key key on more than one domain. The API keys are for single sites. You will need to purchase an additional API key.
2) Your trial period has expired.
3) Your current API key has expired and we have been unable to process your form of payment to renew it. Login to eniture.com and go to the My API keys tab to resolve any of these issues.

== Screenshots ==

1. Quote settings page
2. Warehouses and Drop Ships page
3. Quotes displayed in cart


== Changelog ==

= 3.3.4 =
* Update: Introduced a shipping rule for the liftgate weight limit
* Update: Introduced backup rate feature
* Fix: Corrected the tab navigation order in the plugin


= 3.3.3 =
* Update: Updated connection tab according to wordpress requirements 

= 3.3.2 =
* Update: Fix an issue that caused the order detail page to crash. 

= 3.3.1 =
* Update: Introduced functionality for override and hide shipping rules.
* Update: Compatibility with WordPress version 6.5.2
* Update: Compatibility with PHP version 8.2.0

= 3.3.0 =
* Update: Upgrade the R+L API to the latest version.
* Update: Introduced an option in the quote settings to suppress rates upon reaching the weight threshold. 
* Update: Implemented R+L guaranteed services. 
* Fix: Variant Product ID and title in the metadata utilized for freightdesk.online.

= 3.2.4 =
* Update: Changed required plan from standard to basic for Show Delivery Estimates on the checkout. 

= 3.2.3 =
* Update: Changed required plan from standard to basic for Limited Access Delivery

= 3.2.2 =
* Update: Compatibility with WooCommerce HPOS(High-Performance Order Storage)

= 3.2.1 =
* Update: Added origin level markup. 
* Update: Added product level markup.  
* Update: Added shipping logs feature. 
* Update: Show Free Shipping in case of -100% markup fee

= 3.2.0 =
* Update: Introduced Limited Access Delivery feature.

= 3.1.12 =
* Update: Modified expected delivery message at front-end from “Estimated number of days until delivery” to “Expected delivery by”.
* Fix: Inherent Flat Rate value of parent to variations.
* Fix: Fixed space character issue in city name. 

= 3.1.11 =
* Update: Included quote ID on order detail widget

= 3.1.10 =
* Update: Added compatibility with "Address Type Disclosure" in Residential address detection 

= 3.1.9 =
* Update: Compatibility with WordPress version 6.1
* Update: Compatibility with WooCommerce version 7.0.1

= 3.1.8 =
* Fix: Fixed In-store pickup issue when R+L is at advanced plan and ABF on standard plan

= 3.1.7 =
* Fix: Fixed issue while adding warehouse if some Eniture Technology plugin exists with wrong API key.
* Update: Introduced handling unit weight and max weight per handling unit

= 3.1.6 =
* Fix: Spell correction in the release 3.1.5

= 3.1.5 =
* Update: Added pallet rates feature

= 3.1.4 =
* Update: Introduced connectivity from the plugin to FreightDesk.Online using Company ID

= 3.1.3 =
* Update: Compatibility with WordPress version 6.0.
* Update: Included tabs for freightdesk.online and validate-addresses.com

= 3.1.2 =
* Update: Compatibility with PHP version 8.1.
* Update: Compatibility with WordPress version 5.9. 

= 3.1.1 =
* Update: Relocation of NMFC Number field along with freight class.

= 3.1.0 =
* Update: Added features, Multiple Pallet Packaging and data analysis.

= 3.0.0 =
* Update: Compatibility with PHP version 8.0.
* Update: Compatibility with WordPress version 5.8.
* Fix: Corrected product page URL in connection settings tab.

= 2.3.1 =
* Update: Added feature "Weight threshold limit"
* Update: Added feature In-store pickup with terminal information

= 2.3.0 =
* Update: Cuttoff time.
* Update: CSV columns updated.
* Update: FDO images URL.
* Update: Virtual product at order widget.

= 2.2.1 =
* Update: Introduced new features, Compatibility with WordPress 5.7, Order detail widget for draft orders, improved order detail widget for Freightdesk.online, compatibly with Shippable add-on, compatibly with Account Details(ET) add-don(Capturing account number on checkout page).

= 2.2.0 =
* Update: Compatibility with WordPress 5.6

= 2.1.8 =
* Update: Introduced product nesting feature. 

= 2.1.7 =
* Update: Added index for handling unit. 

= 2.1.6 =
* Update: Compatibility with WordPress 5.5, Compatibility with shipping solution freightdesk.online and plans update feature.

= 2.1.5 =
* Update: Ignore items with given Shipping Class(es).

= 2.1.4 =
* Update: Compatibility with WordPress 5.4

= 2.1.3 =
* Fix: Fixed compatibility issue with Eniture Technology Small Package plugins.

= 2.1.2 =
* Fix: Removed repeated shipping option in case of Hold At Terminal  

= 2.1.1 =
* Update: Introduced Excessive Length Fee feature 

= 2.1.0 =
* Update: Introduced over length and hold at terminal features.

= 2.0.2 =
* Update: Compatibility with WordPress 5.1

= 2.0.1 =
* Fix: Identify one warehouse and multiple drop ship locations in basic plan.

= 2.0.0 =
* Update: Introduced new features and Basic, Standard and Advanced plans.

= 1.2.2 =
* Update: Compatibility with WordPress 5.0

= 1.2.1 =
* Update: Updated to incorporate structural changes to the R+L's API

= 1.2.0 =
* Update: Introduced compatibility with the Residential Address Detection plugin.

= 1.1.0 =
* Update: Compatibility with WordPress 4.9

= 1.0.1 =
* Fix: Product variant inherent parent freight class.

= 1.0 =
* Initial release.

== Upgrade Notice ==