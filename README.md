# Ampersand project template

This template provides multiple ways of deploying your Ampersand prototype.
If you want to know how everything works, consult [this explanation](docs/About.md)

## Avoid deploying by running your prototype on the internet
The following link can also be used to create a codespace using this template:
https://github.com/codespaces/new/AmpersandTarski/project-template?quickstart=1

The remainder of this page lets you deploy on Kubernetes or on Docker, whichever suits you best. This repository contains configurations for both.

## Preparations
1. Git clone https://github.com/AmpersandTarski/project-template into an empty directory, so you have all the necessary files needed to deploy your application. We will call this the working directory.
2. Place all your scripts files in the `./project` folder. The [main.adl](./project/main.adl) file is your entry point. It refers to [script.adl](./project/scipt.adl) which is pre-filled with a Hello-world application so you can try things out before making your own Ampersand script.
3. To run the commands in this instruction, make sure your working directory corresponds to the root of your clone.
Verify this with the command `ls` (on linux) or `DIR` (on Windows):
```
project-template % ls
Dockerfile              SIAM                    apm-prototype.tar       db-init-scripts         docker-compose.yml      project
README.md               ampersand               customizations          deployment              docs
project-template % 
```
4. Rename `.env.example` to `.env` and fill in the missing environment variables to ensure that your prototype can connect to the database. Choose a root password for the database and an ampersand password for your application that accesses this database. We recommend you generate a random password here; there is no need to be able to remember it.

## Instructions for local deployment on Kubernetes
#### Prerequisites
Make sure you have docker and Kubernetes installed. Verify this with the commands `kubectl version` and `docker version`. (We have tried this out with the Kubernetes that comes pre-loaded with the docker desktop.)
Also, make sure Kubernetes is running.

Switch to the namespace in which you run your prototype to avoid typing the namespace as an extra option in every single `kubectl` command:
```
kubectl config set-context --current --namespace=prototype-local
```
#### Step: build a docker image
For building a docker image from the source files in directory `project`, run
```
docker build . -t ampersand-prototype:latest
```
The option `-t` tags the image with the label `ampersand-prototype:latest`, which Kubernetes will use to select the right image. To verify whether the image exists, run `docker images` to get a list of all docker images on your laptop.

Now deploy the image on your local kubernetes platform
```
kubectl apply -k deployment/Kubernetes/overlays/specific/local
```
The option `-k` uses `kustomization.yaml` files to collect the correct combination of manifests for a local deployment.
Check whether this works by inspecting the services with command `kubectl get services` and inspecting the pods with command `kubectl get pods`.
You should see 3 services running, but with no external IP-address attached to them.

Then attach the services to a port (8002 in this example) to make the service reachable from your browser:
```
kubectl port-forward service/apm-dev 8002:80
```
This command does not finish until you press `^C`. As long as it is running, you can access your prototype in your browser on `localhost:8002`.

## Instructions for local deployment on Docker
For Dutch speakers: the following video https://youtu.be/qPnaYkPclYE provides more info on running and installing a Ampersand prototype application with docker.

#### Prerequisites
Make sure you have Docker installed and it is running. Verify this with the command `docker ps`, which should return a list of (0 or more) running containers.

Make sure that port 80 on your laptop is available for docker. Verify this by browsing to `localhost:80` on your laptop. The browser should fail to connect to the server on localhost, reassuring you that this ports is indeed free.

#### Step: build a docker image
For building a docker image from the source files in directory `project`, run
```
docker build . -t ampersand-prototype:latest
```
The option `-t` tags the image with the label `ampersand-prototype:latest`, which docker will use to select the right image. To verify whether the image exists, run `docker images` to get a list of all docker images on your laptop.

Now deploy the image on your local docker platform
```
docker compose up -d
```
The prototype runs as long as the command `docker compose up` is running. The option `-d` runs this command in the background.
Check whether this works by inspecting the running containers with command `docker ps`.
You should see 3 containers running.

Now browse to [localhost](http://localhost) (or [localhost:80](http://localhost:80)) to run your prototype.
