# Installing via Docker

## Get Docker

Docker is a containerization platform that allows you to run applications in a sandboxed environment. You can install Docker on your local machine or on a server. For more information, see the [Docker documentation](https://docs.docker.com/).

You can download docker from here: [Get Docker](https://docs.docker.com/get-docker/).

## Install Lute

### Get the code

You can get the code from the [GitHub repository](https://github.com/jzohrab/lute). Either git clone it or download the latest release ZIP file and unpack it.

### Build and run the stack

You can build and run the stack using the `docker-compose` command. The `docker-compose.yml` file is located in the root of the project.

```bash
docker-compose up -d
```

The Windows command line _will_ work for this, provided you have installed Docker Desktop, but it is **considerably** slower than either the Linux command line or Windows Subsystem for Linux (WSL).

### Helpful commands when using Docker

#### Rebuild the stack

`./docker/refresh`

#### Run a command in the container

`./docker/run <command>`

example: `./docker/run composer install`