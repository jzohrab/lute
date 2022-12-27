# Mac Apache/PHP/MySQL

On my mac, I have vhosts set up so I can go to the following:

* `http://localhost:8080/` - defaults to my lute installation
* `http://lute.local:8080/` - my lute install as well
* `http://lute_release.local:8080/` - for local testing of releases

My files are as follows:

* [/usr/local/etc/httpd/httpd.conf](./httpd.conf)
* [/usr/local/etc/httpd/extra/httpd-vhosts.conf](./httpd-vhosts.conf)
* [/etc/hosts](./hosts)