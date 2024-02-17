# About
As a software engineer, you want to know how this template works so you can adapt it to your own needs and maintain your prototype. This page tells you how.

## Structure
Kustomize links all manifest files together.
There are three starting points:
1. `deployment/Kubernetes/overlays/specific/local`: meant for local development on your laptop.
2. `deployment/Kubernetes/overlays/specific/staging`: meant for testing prior to deployment to production.
3. `deployment/Kubernetes/overlays/specific/production`: meant for deployment to production.

The manifest files are layered to facilitate multiple deploymets, e.g. development, staging, and production.

The `base` layer contains the prototype proper and the certification manager.
Certificates are provided fully automated by `letsencrypt`, so your prototype will work with https out of the box.

## Things to adapt
This template uses the name `ampersand-prototype` in YAML files (`*.yml` and `*.yaml`) for:
1. the name of the docker image of your prototype
ampersand-prototype
2. the name of the container.
You may want to change these to names that are meaningful in your own context.

## Ingress
This template uses letsencrypt for automatic provisioning of certificates.

The ingress is shut off for the local deployment, but you can switch in on by uncommenting the appropriate lines in the `kustomization.yml` files