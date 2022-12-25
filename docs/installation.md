# Installation

> TODO:docs - the docs for installation need work, and perhaps some things can be simplified, such as removing vhosts.

This project does not have "GitHub releases" yet, so just clone the repo to your machine, or get the latest [zipfile from GitHub](https://github.com/jzohrab/lute/archive/refs/heads/master.zip), and unpack it in a directory in your "Public" folder.

The setup for this project is much the same as [LWT](https://github.com/HugoFara/lwt):

* get an Apache server with PHP and MySQL _(note: setting up "load local infile" is not required)_
* create an ".env.local" file (and ".env.test.local" if you're running tests)

Unlike LWT, which just uses plain php files, Lute uses the [symfony](https://symfony.com/) framework, and so has more requirements:

* You'll need [composer](https://getcomposer.org/download/) to install the dependencies
* PHP version least 8.1
* Apache: enable virtual hosts and URL rewrites.
* Apache: create a Virtual Host to redirect requests to the Lute "front controller"
* Edit your `etc/hosts` to use the new Lute URL in your browser

My personal Lute is running Apache/2.4.54, with PHP version 8.1.13.

### PHP version

For Mac installing later versions of PHP on MAMP, see [this link](https://gist.github.com/codeadamca/09efb674f54172cbee887f04f700fe7c).

### Apache virtual host

Ref https://davescripts.com/set-up-a-virtual-host-on-mamp-on-mac-os-x

For me on my mac, the virtual hosts file was at `/usr/local/etc/httpd/extra/httpd-vhosts.conf`, and I added the following, specifying my particular path:

```
<VirtualHost *:8080>
    DocumentRoot "/Users/jeff/Public/lute/public"
    ServerName lute.local
    <Directory "/Users/jeff/Public/lute/public">
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

After saving the file, restart Apache, and check the configuration:

```
sudo apachectl restart    # Restart :-)
apachectl -S              # Check your vhost config:
```

### Edit your /etc/hosts

Note that the `ServerName` above is `lute.local` ... so then you need to edit your `/etc/hosts` file so that you can enter "http://lute.local:8080/" in your browser.

For a mac, do the following:

```
sudo vi /etc/hosts
```

Add the line

```
127.0.0.1       lute.local
```

Then in a browser window, go to http://lute.local:8080/ - if it pops up, your basic mappings are fine.

### .env, .env.test, .env.local, and .env.test.local

Lute uses "environment files" in the root folder for basic configuration.

The default file, `.env`, may be sufficient if your mysql setup matches the various DB_ values in that file.

If those values aren't good for you, then make a copy of `.env.local.example` named `.env.local`, and edit that file for your setup.

For developers, you can do the same with `.env.test`: copy `.env.test.local.example` to `.env.test.local` and edit that.

> Don't touch `.env` or `.env.test`; instead, make your own `...local` files and edit those.
