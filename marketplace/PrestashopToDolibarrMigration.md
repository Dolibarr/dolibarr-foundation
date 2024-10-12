# PrestaShop to Dolibarr Migration Guide

## 1. Create Admin User
- Create Admin User (If does not exist) and assign all permissions.
## 2. Activate the Website module and all dependencies modules
- dependencies modules : Modules Third Parties, Vendors, Product, Service, Categorie, Sales Orders, Invoices, Banque, Mailing, Website
## 3. Add the following Dolibarr constant:
  - WEBSITE_PHP_ALLOW_WRITE = 1

## 4. Activate Marketplace Module

## 5. Run Import Scripts (In this order)

- Import Categories
    php marketplace/scripts/import-cats.php db_host db_name db_user db_password db_port clean_all_before_import(0|1)

- Import Third-Parties
    php marketplace/scripts/import-third-parties.php db_host db_user db_password db_port limit ref_website clean_all_before_import(0|1)

- Import Products
    php marketplace/scripts/import-products.php db_host db_user db_password db_port limit clean_all_before_import(0|1)

- Import Attached Files
    php marketplace/scripts/import-attached-files.php db_host db_user db_password db_port source_dir

- Import Orders
    php marketplace/scripts/import-orders.php db_host db_user db_password db_port limit clean_all_before_import(0|1)

## 6. Add this rules in Apache virtual host configuration 

    <Directory "/_YOUR_PATH_/dolibarr/documents/produit">
    AllowOverride FileInfo Options
    Options -Indexes -MultiViews +FollowSymLinks
    Require all granted
    </Directory>

    # Enable RewriteEngine
    RewriteEngine on

    # Products rewrite rules
    RewriteCond %{REQUEST_URI} ^/([a-z]{2})/([a-z\-]+)/([0-9]+)-([a-z0-9\-]+)\.html$ [NC]
    RewriteRule ^ /product.php?extid=%3 [L,R=301]

    # Categories rewrite rules
    RewriteCond %{REQUEST_URI} ^/([a-z]{2})/([0-9]{1,2})-([a-z\-]+)$ [NC]
    RewriteRule ^ /index.php?extcat=%2 [L,R=301]

## 7. Add this rules in Dolibarr Apache Configurations

    Header always unset X-Frame-Options
    Header always set Content-Security-Policy "frame-ancestors *"

## 8. Set File Permissions

Ensure that all files in the product documents directory have the correct permissions by running the following command:
sudo chmod -R 755 /_YOUR_PATH_/dolibarr/documents/produit/
