laserCommerce
=============

LaserCommerce is a plugin that extends WooCommerce to allow for custom price “tiers” (eg. retail, wholesale, distributor) that are based on a user’s role. It allows for a “regular” price and a “special” price to be entered for each product, and for each price tier via spreadsheet import or via the product admin interface. It allows administrators to set up a hierarchy of price tiers via a drag and drop interface.

When a user is logged in to an appropriate role they can see all prices for roles that are above their role in the hierarchy. The plugin displays the prices for all of these roles in a table on the product page and allows the user to buy any product at the lowest available price. The plugin also allows admins to schedule when certain products should be displayed at their special price.

How it works
------------

Typically, in core Woocommerce, all users see the same price for a given product. Woocommerce allows for a regular price and a sale price to be associated with a product, and allows administrators to specify when the sale price is active, outside of this schedule, the user does not see the special price.

Lasercommerce is an extension of Woocommerce that allows for multiple prices to be associated with a given product such that the price displayed to a user depends on what roles the user belongs to.
This is implemented through the use of logical pricing levels or “Price Tiers”

A Lasercommerce price tier is associated with a single user role. It consists of a fixed regular price and a sale price that can be scheduled. Lasercommece allows its price tiers to be placed in a hierarchical tree structure so that tiers can have multiple child tiers. All Lasercommerce price tiers are logical descendants of the default price tier included with core Woocommerce. If a user belongs to a given price tier, it can see all prices associated with that tier and all of the tier’s ancestors. A given product is sold to a user at the lowest price that is visible to the user, where visible price tiers are sorted by regular the current scheduled price.

Development
===========

Status
------

Partially complete

Future
------

We intend to develop LaserCommerce further so that administrators can set the visibility of pages and products to be dependent on a user’s role in the same way the prices already are. For example if a posts visibility is set to “wholesale”, then wholesale and distributor users can see it but not retail.

We have recently developed part of the functionality that allows 'sale' prices for all roles that can be scheduled easily from the admin menu.

Issues
------

Currently pricing is stored in post metadata so that it is easy to import this data from a spreadsheet, however post metadata is slow, so this plugin will not scale well. We will attempt to address this issue in future versions.

Installation
------------

Zip the lasercommerce directory and upload it into wordpress as a plugin.

Prerequisites
-------------

Wordpress
Woocommerce

Integrations
------------

Lasercommerce does its best to integrate well with the following plugins:
 - Woocommerce Dynamic Pricing
 - Woocommerce Memberships
 - Groups
