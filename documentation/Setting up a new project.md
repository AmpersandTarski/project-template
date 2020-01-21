# Setting up a new project

This file shows the steps you have to follow in order to set up your own project in which you can generate a prototype from an ampersand script that you are developing. 

We start with a very simple situation. and subsequent sections will add features that you might be needing.

By default, we assume that you have 

- a directory on your computer, which we will refer to as `pdir`, in which you do your development. 
- within `pdir`, you have a file (which we will call `myapp.adl`) that will load or contain your ampersand application.

## Setting up a simple project

The simplest projects consist of a single script that does not need any other stuff, such as a 'Hello World' application. 

Here are the steps you walk through after you have fulfilled the default assumptions mentioned above:

1. Download the project-template repo as a zip file, and extract the following items into `pdir`:
   - the `docker` directory
   - the `docker-compose.yml` file
2. Edit the `docker-compose.yml` file, and make the following changes (note that texts are case sensitive!):
   - change `SCRIPT=project.adl` into `SCRIPT=myapp.adl`

That's it. Now you can use the appropriate docker command to build and run the prototype

## Adding support for SIAMv4

If your project requires SIAMv4 support, here is what you do:

1. edit the file `prototype.Dockerfile` located in  `pdir/docker`
2. add the line `COPY --from=docker.pkg.github.com/ampersandtarski/ampersand-models/siamv4:latest /usr/local/ampersand-models/SIAMv4 /usr/local/project/SIAMv4` e.g. right behind the line that says`ADD . /usr/local/project`
3. copy the file `SIAMv4_Module-example.adl` from the [SIAMv4 directory](https://github.com/AmpersandTarski/ampersand-models/tree/master/SIAMv4) in the ampersand-models repo, renaming it, e.g. into `SIAMv4_Module.adl` (or something else you find appropriate), and start to edit it.
4. change all occurrences of `%SIAMv4_Path%` into `./SIAMv4`
5. make any other adjustments that you think are necessary (as usual for SIAM)
6. add the line `INCLUDE "./SIAMv4_Module.adl"` to the `myapp.adl` script.

That's it. 