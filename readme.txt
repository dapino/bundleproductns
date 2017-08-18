=== Custom Product Boxes ===
Current Version: 2.0.0
Author:  WisdmLabs
Author URI: http://wisdmlabs.com/
Plugin URI: http://wisdmlabs.com/assorted-bundles-woocommerce-custom-product-boxes-plugin/
Tags: WooCommerce Assorted Products, WooCommerce Create your Own, WooCommerce user assorted product bundles
Requires at least: 4.4
Tested up to: 4.5.2
License: GNU General Public License v2 or later

Tested with WooCommerce version: 2.6.4

== Description ==


The Custom Product Boxes is a WooCommerce extension, using which customers select various products and purchase them as customized product boxes. 

== Installation Guide ==
1. Upon purchasing the Custom Product Boxes plugin, an email will be sent to the registered email id, with the download link for the plugin and a purchase receipt id. Download the plugin using the download link.
2. Go to Plugin-> Add New menu in your dashboard and click on the ‘Upload’ tab. Choose the ‘Custom-Product-Boxes.zip’ file to be uploaded and click on ‘Install Now’.
3. After the plugin has been installed successfully, click on the Activate Plugin link or activate the Custom Product Boxes plugin from your Plugins page.
4. A Custom Product Boxes License sub-menu will be created under Plugins menu in your dashboard. Click on this menu and enter your purchased product's license key. Click on Activate License. If license in valid, an 'Active' status message will be displayed, else 'Inactive' will be displayed. 
5. Upon entering a valid license key, and activating the license, a new product type called as ‘Custom Product Boxes’ will be created and added on product page. A new set of fields is displayed to the user when the product type selected is ‘Custom Product Boxes’.
6. In WooCommerce-> Settings -> General Settings: Uncheck the ‘Enable Lightbox’ option and save the changes made.


== User Guide ==
Once you purchase and install the Custom Product Boxes Plugin, a product type called as ‘Custom Product Box’ is created for every WooCommerce Product. 

= How to Create a User Assorted Product Bundle or a Custom Product Box? =
To create a user assorted bundle, you will have to set the product type as ‘Custom Product Box’. Add product details in the - ‘Custom Box Settings’ - tab.
Product Box Settings
Box Quantity: In this field, enter the capacity of the box (the number of items/product pieces which can be added to the box). For example, for an assorted box of chocolates, say a user can add 12 pieces, then set ‘Box Quantity’ to 12
Columns in Grid: To represent the box on the product page a grid is show. Select the number of columns (2 or 3) you want in the grid.
Height of each Row: Set the height of each row in the grid. This height will be taken in pixels. For example if you set height as 100, row height in grid will be 100px.
Columns in Product Layout: On the product page, there will be a layout for add-on products. Select the number of columns (2 or 3) for add-on products as well.
Add-On Products
Using the product selector field, select the Simple Products which can be added to the Custom Product Box. The product selector makes it simple to select the products and add them to the list. 

= How can Customers create their Assorted Products box? =
Once you create and publish a ‘Custom Product Box’ product, it will be added as usual to the WooCommerce shop page. When a user selects the product, he will be shown a grid layout which represents the box and a products layout which contains the add-on products (which can be added to the box).

- For every add-on product, the quantity available is shown below the product. For example, ‘5 in Stock’ is shown if only 5 items are in stock. Or ‘Out of Stock’ is shown, if the product is not available.
- There will be an add-option for every product add-on, using which a customer can add the product to the box. A single click of the add option will add a single item to the box. To add multiple items of the same add-on, the customer will have to click the add option multiple times as required.
- Once the customer adds the product to the box, a position in the box will be occupied by the selected add-on.
- There will be a remove option associated with every added item in the box
- The customer can add the created assorted box to the cart once the box has been completely filled.
- The customer can set the quantity of the box before adding it to cart. But the quantity can be increased only if there are enough add-on items in stock.


== Features ==
1. Create custom product boxes as products and set price, shipping details and box size.
2. Link other simple products that can be added to the custom product box.
3. Set Box layout and product layout on Single Product page
4. Create unlimited product boxes from the dashboard.
5. Customer can add and remove products from the custom product box on the shop page.
6. Inventory management of products that are added to the box based on available stock.


== Changelog ==

= 2.0.0 =
* Feature - Multiple layouts for desktop view on per product level (Horizontal and Vertical Layout).
* Feature - Templates that can be overriden by third party themes or plugins.
* Feature - List Layout for mobile view.
* Feature - Gift Message (Allows customer to send a message with the box).
* Fix - Gift box sizes.
* Tweak - Compatibility with woocommerce 3.0.1.
* Tweak - Integrated updated licensing code.
* Fix - Product type in product listing page.


= 1.2.0 =
* Fix - Stock message for products in CPB Product.
* Feature - Setting to enable or disable scroll locking of the gift box.
* Feature - Settings to allows pre-filled products in gift box which can be removable or mandatory.

= 1.1.7 =
* Fix - Handled fatal error occuring when any of the product associated with custom product box is deleted.
* Fix - Conflict when we add more than one 'custom product boxes' products in the cart.
* Fix - Handled Fatal error on 'Undo CPB removal'.
* Feature - Replaced the Add and remove icon.
* Fix - Made plugin compatible with woocommerce 2.6.1.
* Fix - Scroll lock issue on mobile devices.

= 1.1.6 =
* Fix - Round off issue when the decimal seperators and thousand seperators are other than dot and comma.
* Fix - The gift box layout was seen in dotted line.
* Fix - Made plugin compatible with woocommerce 2.5.5 and wordpress 4.5.2.
* Fix - Inventory Management.
* Fix - Update Cart Quantity.
* Tweak - Removed Variable product from the Add-On products Field.
* Tweak - Hide out of stock product if woocommerce setting is enabled.
* Feature - Clipped long product titles and displayed title upto 2 lines.
* Feature - Added a setting for sorting the products by date.
* Feature - Fixed the height of box for all products to avoid inconsistency in bundle products layout.
* Fetaure - Scrolls the gift box layout on scrolling the page.


= 1.1.5 = 
* Updated for hungarian translation
* Minor UI changes

= 1.1.4 =
* Minor Fixes 
* Added the feature to allow purchase of partially filled boxes
* Made psr2 compatible

= 1.1.3 =
* Improved plugin load performance
* Compatible with PHP version less than 5.4
* Compatible with WooCommerce 2.4.8 and WordPress 4.3.1

= 1.1.2 =
*Product prices fixed
*Compatible with WooCommerce 2.4.6 and WordPress 4.3.1

= 1.1.1 =
* Updated the plugin for warnings with session start
* Updated plugin for the layout on the front end
* Compatible with WooCommerce 2.3.13 and WordPress 4.2.4

= 1.1.0 =
* Updated plugin licensing
* Compatible with WooCommerce 2.3.9

= 1.0.10 =
* Updated plugin licensing
* Compatible with WooCommerce 2.3.9

= 1.0.9 =
* Bug related to LightBox fixed
* Compatible with WooCommerce 2.3.5

= 1.0.8 =
* Compatible with WooCommerce 2.3.3
* Changed multiple "Add-On Products" layout and functionality.
* Added regular price and sale price fields with float value functionality.

= 1.0.7 =
* Bug and error fixes
* Added missing text domains in some files
* Added validations where required

= 1.0.6 =
* Fixed 'Out of Stock' error

= 1.0.5 =
* Fixed JS error

= 1.0.4 =
* bug and error fixes.

= 1.0.2 =
* Validation added for add-on products
* Added maybe_unserialize while fetching data from database.
* Code updated to check whether the session is empty or not.
* Bug fix which redirected to blank page after checkout.
* Removed unneccessary add action hooks.
* Added 200px as default value to grid height.

= 1.0.0 =
* Plugin Released.


== FAQ ==

Which version of WordPress and WooCommerce does the Custom Product Boxes extension work with?
The current version of Custom Product Boxes is compatible with WordPress 4.4 and WooCommerce 2.5.0.

How to change the Product Image dimensions in the Product Layout?
To change the image dimensions, you will have to change Image Thumbnail settings in WooCommerce and regenerate the image thumbnails.

I want to make a few changes in the UI. Do you provide theme customization services?
Yes.

For how long is a license valid?
Every license is valid for a year from the date of purchase. During this year you will receive free support. After the license expires you can renew the license for a discounted price.

What will happen if my license expires?
If your license expires, you will still be able to use the plugin, but you will not receive any support or updates. To continue receiving support for the plugin, you will need to purchase a new license key.

Is the license valid for more than one site?
Every purchased license is valid for one site. For multiple site, you will have to purchase additional license keys.

Help! I lost my license key!
In case you have misplaced your purchased product license key, kindly go back and retrieve your purchase receipt id from your mailbox. Use this receipt id to make a support request to retrieve your license key.

How do I contact you for support?
You can direct your support request to us, using the Support form on our website.

Do you have a refund policy?
Yes. Refunds will be provided under the following conditions:
-Refunds will be granted only if CPB does not work on your site and has integration issues, which we are unable to fix, even after support requests have been made.
-Refunds will not be granted if you have no valid reason to discontinue using the plugin. CPB only guarantees compatibility with the WooCommerce plugin.
-Refund requests will be rejected for reasons of incompatibility with third party plugins.
 

