# Some misc Apache notes.

These are scratchy, but if you're here, maybe you already have the basics down.

* Apache: enable virtual hosts and URL rewrites.
* Apache: create a Virtual Host to redirect requests to the Lute "front controller"
* Edit your `etc/hosts` to use the new Lute URL in your browser

## Apache virtual host

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

## Edit your /etc/hosts

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
