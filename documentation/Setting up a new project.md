# Setting up a new project

This file shows the steps you have to follow in order to set up your own project in which you can generate a prototype from an ampersand script that you are developing.

We start with a very simple situation. and subsequent sections will add features that you might be needing.

## Setting up a simple project

The simplest projects consist of a single script that does not need any other stuff, such as a 'Hello World' application. Here is how you do that:

1. Create a project directory - we will refer to it as `pdir`
2. Create an ampersand script that contains your application - we will refer to it as `myapp.adl`
3. Download the project-template repo as a zip file, and extract the following items into `pdir`:
   - the `docker` directory
   - the `docker-compose.yml` file
4. Edit the `docker-compose.yml` file, and make the following changes (note that texts are case sensitive!):
   - change `SCRIPT=project.adl` into `SCRIPT=myapp.adl`
   - change `/usr/local/project` into `/usr/local/pdir`
5. Execute `docker-compose up -d` to generate the prototype and start running it.
