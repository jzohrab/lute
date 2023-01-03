# Installing on XAMPP

## 1. Get XAMPP

- download XAMPP at https://www.apachefriends.org/es/download.html
- run the installer

## 2. Get Lute

Lute goes into the `c:/xampp/htdocs/` folder.

You can get Lute into the `htdocs` folder in XAMPP in one of two ways:

- If you're a software person: Clone the git repo inside the `htdocs` folder, use `composer install --dev` to install all the dependencies, and then create an `.env.local` file, using `.env.local.example` as a template.
- If you're a regular human :-) get the latest lute_release.zip, and unzip it inside of `htdocs`.  When you unzip the file, your `htdocs` folder should contain a `lute_release` folder.

The structure should be as follows:

```
- C:
  - xampp
    - htdocs
      - lute (or lute_release)
        .env
        .env.local
        ...
        + bin
        ... etc.
```

## 3. Configure XAMPP Apache

In the XAMPP control panel, next to "Apache", click "Config", and choose "Apache (httpd.conf)".  Notepad will open the Apache config file.  There are just a few things to verify, and some edits.

### Check the `<Directory />`

The file should contain this snippet:

```
<Directory />
    AllowOverride none
    Require all denied
</Directory>
```

### Change `DocumentRoot`

from

```
DocumentRoot "C:/xampp/htdocs"
```

to

```
DocumentRoot "C:/xampp/htdocs/lute_release/public"
```

### change `<Directory "C:/xampp/htdocs">`

from

```
<Directory "C:/xampp/htdocs">
```

to

```
<Directory "C:/xampp/htdocs/lute_release/public">
```

### change `<Directory "C:/xampp/htdocs/lute_release/public">` Options:

Change

```
Options Indexes FollowSymLinks Includes ExecCGI
```

to

```
Options Indexes FollowSymLinks Includes ExecCGI MultiViews
```

### Save your changes!  File > Save in Notepad.

## 4. Verify the MySQL port

Back in the XAMPP control panel, next to "MySql", click "Config", and choose "my.ini".  Notepad will open the file.

Find the line that says "port=3306" ... there's nothing to change here, we're just double-checking the port.

## Edit your .env.local in the root Lute folder

Open `c:/xampp/htdocs/lute_release/.env.local` with notepad, and change a few lines:

Change

```
DB_HOSTNAME=localhost
```

to this: (note the port number)

```
DB_HOSTNAME=localhost:3306
```

And change

```
DB_PASSWORD=root
```

to this: (XAMPP uses a blank password by default)

```
DB_PASSWORD=
```

Save the .env.local file.

## Phew, take a break!

All of the above might have felt pretty hairy -- hopefully not.

## start XAMPP Apache and MySQL

Back in the XAMPP control panel, next to "Apache", click "start", and next to "MySQL", click "start".

## go to http://localhost

And **hopefully** everything is hunky-dory.  :-)  If it is, congratulations, the hard part is over.