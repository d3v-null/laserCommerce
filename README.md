laserCommerce
=============

LaserCommerce is a plugin that extends WooCommerce to allow for custom price “tiers” (eg. retail, wholesale, distributor) that are based on a user’s role. It allows for a “regular” price and a “special” price to be entered for each product, and for each price tier via spreadsheet import or via the product admin interface. It allows administrators to set up a hierarchy of price tiers via a drag and drop interface. 

When a user is logged in to an appropriate role they can see all prices for roles that are above their role in the hierarchy. The plugin displays the prices for all of these roles in a table on the product page and allows the user to buy any product at the lowest available price. The plugin also allows admins to schedule when certain products should be displayed at their special price.

We have recently developed functionality that allows 'sale' prices for all roles that can be scheduled easily from the admin menu.

Status
------

Partially complete

Development
-----------

We intend to develop LaserCommerce further so that administrators can set the visibility of pages and products to be dependent on a user’s role in the same way the prices already are. For example if a posts visibility is set to “wholesale”, then wholesale and distributor users can see it but not retail.

Installation
------------

Zip the lasercommerce directory and upload it into wordpress as a plugin.

Prerequisites
-------------

Wordpress
Woocommerce

Issues
------

Currently pricing is stored in post metadata so that it is easy to import this data from a spreadsheet, however post metadata is slow, so this plugin will not scale well. We will attempt to address this issue in future versions.
