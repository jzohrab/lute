# Installing on MAMP

## Get MAMP

- download MAMP https://www.mamp.info/en/downloads/
- run the installer (it's both MAMP and MAMP PRO, but you'll only use MAMP)

## Add PHP v.8.1 to MAMP

- Lute requires at least PHP 8.1, but as of now MAMP comes with max version 8.0.8.  I *think* there are a few ways to get v 8.1, but the clearest set of instruction I found was from https://gist.github.com/codeadamca/09efb674f54172cbee887f04f700fe7c.  Copied below:
  - if you don't have `homebrew` already, get it: https://treehouse.github.io/installation-guides/mac/homebrew
  - install PHP v.8.1 on your system, _outside of MAMP_, using homebrew (https://formulae.brew.sh/formula/php):
`brew install php@8.1`
  - using Finder, Menu > Go > Go to Folder `/usr/local/Cellar/php`, and copy the right folder (for me, it was 8.1.13).
  - using Finder, Go to Folder `/Applications/MAMP/bin/php`.  There will be a bunch of php folders in there; paste the folder you just copied.  Add `php` to the start of the folder name (e.g. `php8.1.13`) so it follows the naming convention of the others.

## Add the httpd modules

  - Go to Folder `/usr/local/lib/httpd`.  Copy the `modules` folder.
  - Go to Folder `/Applications/MAMP/bin/php/`, and paste the `modules` folder _inside_ the `phpXXX` folder you just created (e.g. `php8.1.13`)
  - Close and restart MAMP, and you should be able to pick version `php8.1.13` in the dropdown.

## Add libphp.so

  - Go to Folder `/usr/local/lib/httpd/modules`.  That folder contains `libphp.so`, so you might think you've copied that file to MAMP in the prior step ... but unfortunately, that's not the case!  That file is potentially just a _shortcut_ to another file, and Apache doesn't like that.  _Cue single tear_.
  - Right click `libphp.so`, and click "Show original."  Copy that file.
  - Go to Folder `/Applications/MAMP/bin/php/php8.1.13/modules`, and paste the good `libphp.so` file you just copied, replacing the old `libphp.so`.

## Get Lute!

You can get Lute into the `htdocs` folder in MAMP in one of two ways:

- If you're a software person: Clone the git repo inside the `htdocs` folder in MAMP (Applications/MAMP/htdocs), use `composer install --dev` to install all the dependencies, and then create an `.env.local` file, using `.env.local.example` as a template.
- If you're a regular human :-) get the latest lute_release.zip, and unzip it inside of `htdocs`.  When you unzip the file, your `htdocs` folder should contain a `lute_release` folder.

The structure should be as follows:

```
- Applications
  - MAMP
    - htdocs
      - lute (or lute_release)
        .env
        .env.local
        ...
        + bin
        + config
        + db
        ... etc.
```

## Change the server Document Root

Next, we need to change MAMP to that it's serving LWT from the root folder.

In Menu > MAMP > Preferences > Server tab, for the Document Root, click "Choose", and select "Applications/MAMP/htdocs/lute/public" as the document root.

> Note: if you're already running other sites on MAMP, you can add Lute as a Virtual Host.  I'll assume you already know how to do this, and how to update your `/etc/hosts`.

## A few edits to MAMP's httpd.conf file

In your editor, open the file `/Applications/MAMP/conf/apache/httpd.conf`.  This is the main Apache configuration file.

### Verify/add LoadModule for libphp.so

In this file, there will be a long list of lines starting with "LoadModule".  Ensure that the `libphp.so` file is referenced in this list, it will be a line like the following:

```
LoadModule php_module        /Applications/MAMP/bin/php/php8.1.13/modules/libphp.so
```

### Change the User

Update the User to your computer username.  You can get that via terminal with `id -un`.  For me, that returned `jeff`, so I have:

```
User jeff
```

### fyi, check the DocumentRoot

A few lines down from `User`, you'll see this:

```
DocumentRoot "/Applications/MAMP/htdocs/lute_release/public"
```

That was the change you made earlier in MAMP preferences.  fyi only.

### Change <Directory /> settings

Change

```
<Directory />
    Options Indexes FollowSymLinks
    AllowOverride None
</Directory>
```

to:

```
<Directory />
    AllowOverride none
    Require all denied
</Directory>
```

### Change <Directory "/Applications/MAMP/htdocs/lute_release/public"> settings

Change `Options All` to `Options Indexes FollowSymLinks MultiViews`

## Edit your .env.local in the root Lute folder

In the file `/Applications/MAMP/htdocs/lute_release/.env.local`, set the DB_HOSTNAME **exactly** as follows:

DB_HOSTNAME=127.0.0.1:8889

**THE LINE MUST BE EXACTLY AS ABOVE.**  If it is anything else (localhost, localhost:8889, 127.0.0.1), it won't work!  Unfortunately, I haven't had time to figure out why ... and I'm not sure I care, really.  127.0.0.1:8889 works and is stable!

Leave the rest of the settings as-is. (MAMP uses username=root, password=root as the defaults, so the values set in the .env.local are fine, and Lute will set up the lute_demo database when it starts up).

## Phew, take a break!

All of the above might have felt pretty hairy -- hopefully not.

## start MAMP

(if it bombs with an "Apache can't start" error, try starting it from the command line:

```
/Applications/MAMP/Library/bin/apachectl start
```

The above might give you more information on what is wrong.

## go to localhost:8888

And **hopefully** everything is hunky-dory.  :-)