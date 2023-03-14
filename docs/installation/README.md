# Installation

Installation can be daunting, so here are some notes.

## Installing via docker

If you have docker installed, you can bring up the Lute stack with a single command.  See [installing via docker](./install_docker.md).

Docker requires very little setup, and is a great way to get started.

## Installing your own Apache/PHP/MySQL stack

If you're more technical, and are setting up your own Apache/Php/MySQL stack instead of using a pre-packaged thing like MAMP/XAMPP/Whatever, I'll assume you can get that rolling, but here are some quick notes for [installing on plain Apache](./install_apache.md).

## Mac users

If you have a Mac, you can use MAMP, a pre-loaded package that has PHP, Apache, and MySQL.  See [installing on MAMP](./install_mamp.md)

## Windows

If you have Windows, you can use XAMPP, a pre-loaded package that has PHP, Apache, and MySQL.  See [installing on XAMPP](./install_xampp.md)

## Reference Apache config files

I find Apache configuration daunting as well, so here are some [actual apache config files](./sample_apache_config_files/README.md) as I use them on my personal machine.  These are for reference only, but maybe they will help someone.

## Errors

If you're having an issue with the app once it's up and running, you can get the docker logs with `docker logs symfony`.  If you're running your own stack, you can get the logs from the apache error log.