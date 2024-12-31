# Deployment Setup
  The first time you deploy your prototype, you need to set up your environment. This document describes how to do that.

## Cookbook recipe for first time deployment
If some of these steps fail, read the [Prerequisites](#prerequisites) or [Troubleshooting](#troubleshooting) section at the end of this document.

1. Navigate to the deploy directory.
   ```shell
   cd deploy
   ```

2. Create a cluster in which to deploy your prototype.
   ```shell
   k3d --config k3d.yaml cluster create
   ```
   Expected output:
   ```
   INFO[0000] Using config file k3d.yaml (k3d.io/   v1alpha5#simple) 
   INFO[0000] portmapping '8080:80' targets the    loadbalancer: defaulting to [servers:*:proxy    agents:*:proxy] 
   INFO[0000] Prep:    Network                                
   ... <several lines of output omitted for    brevity> ... 
   INFO[0013] Cluster 'my-prototype-cluster' created    successfully!          
   INFO[0013] You can now use it like    this:                
   kubectl cluster-info
   ```
3. Import the image of your prototype into the cluster.
   ```shell
   k3d image import --cluster my-prototype-cluster    ampersand-prototype:latest
   ```
4. Create a namespace in which to run the application.
   ```shell
   # create namespace
   kubectl create ns my-prototype-ns
   ```
5. Deploy the stack.
   ```shell
   helmfile --environment local apply
   ```
Expected output:
```
...  <multiple lines of output omitted for brevity> ...
UPDATED RELEASES:
NAME         NAMESPACE      CHART                      VERSION   DURATION
mariadb      my-prototype-ns   bitnamicharts/mariadb      19.0.7         52s
prototype    my-prototype-ns   ./charts/prototype         0.1.0           2s
phpmyadmin   my-prototype-ns   bitnamicharts/phpmyadmin   17.0.7         52s
```

## Prerequisites
For the cookbook recipe to work, you need to have the following tools installed on your local machine:
### Installing software
#### Docker
  Docker is a platform for developing, shipping, and running applications in containers. Check if Docker is installed:
```shell
docker --version
```
Docker is included in many container desktop applications, such as Docker Desktop, Rancher Desktop, or Colima.

#### Kubernetes
  Kubernetes is a platform for deploying and managing your application in containerized form. It is widely available.
  Check if Kubernetes is installed:
```shell
kubectl cluster-info
```
  Expect the system to say that the Kubernetes control plane is running.
  If not, install Kubernetes. On macOS, you can install Kubernetes with Homebrew.

  Check if kubectl is installed:
```shell
kubectl version --client
```
  The expected output is something like:
```
Client Version: v1.30.2
Kustomize Version: v5.0.4-0.20230601165947-6ce0bf390ce3
```
  If not, install Kubernetes:
```shell
brew install kubectl
brew install minikube
minikube start
```

#### Helm
Check if Helm is installed:
```shell
helm version
```
  If not, install Helm:
```shell
brew install helm
```

#### Helmfile
  Use helmfile to manage Helm charts. Helmfile is a declarative spec for deploying Helm charts. It lets you keep a directory of chart value files and maintain a state of releases in your cluster.

  Check if Helmfile is installed:
```shell
helmfile --version
```
  If not, install helmfile:
```shell
 brew install helmfile
```

#### Helm Secrets
  Helm Secrets is a Helm plugin that allows you to encrypt and decrypt secrets in your Helm charts.

  Check if Helm Secrets is installed:
```shell
helm secrets --version
```
  If not, install Helm Secrets:
```shell
helm plugin install https://github.com/jkroepke/helm-secrets
```

#### k3d
  Use k3d instead of kubectl to manage Kubernetes for development and testing purposes. Novices will find it easier than doing the same things with kubectl.

  Check if k3d is installed:
```shell
k3d --version
```
  On macOS, you can install k3d with Homebrew:
```shell
brew install k3d
```

### monitoring your Kubernetes environment
  Try k9s to monitor the cluster and to troubleshoot problems. k9s saves you from remembering lots of kubectl commands. Alternatively, use the Kubernetes extension for Visual Studio Code.

  Check if k9s is installed:
```shell
k9s --version
```
  On macOS, you can install k9s with Homebrew:
```shell
brew install k9s
```
  Run k9s in a terminal window to inspect the cluster in real-time.

### Investigating your Kubernetes environment
A Kubernetes cluster is a set of compute and storage resources, on which you can run containerized applications. Each node in your cluster is a virtual machine or a physical computer. The nodes are managed by the Kubernetes control plane. The control plane is a set of processes that control the nodes and the containers running on them.

#### List All Accessible Clusters
  To list all clusters you have access to, use the following command:
```shell
kubectl config get-clusters
```
The expected output is something like:
```
NAME
ampersand-rap-aks-prd
docker-desktop
k3d-my-prototype-cluster
minikube
```
Either pick a cluster or create a new one.
#### Create your local Kubernetes cluster
```
# create your cluster (within the deploy directory!!!)
```shell
k3d --config k3d.yaml cluster create
```

#### Delete this cluster
Do this either when you goof-up and want to make a fresh start, or when you are done with your prototype and want to clean up your environment.
```shell
k3d --config k3d.yaml cluster delete
```

#### Switch to an existing cluster
```shell
kubectl config use-context <context-name>
```

### Encrypt secrets

```
helm secrets encrypt -i values/<component>/<environment>-secrets.yaml
```
e.g.:
```bash
helm secrets encrypt -i values/mariadb/staging-secrets.yaml
```
Run this each time you change a secret anywhere in the project.

## Running locally
This is useful for testing and development purposes on your local machine, to ensure that you feed the build pipeline correct code only.
This helps to iterate faster.

### Requirements
1. You need to have a local Kubernetes cluster (k3d-my-prototype-cluster) up and running, on which you can deploy your prototype.
2. You need to have a local Docker registry running from which the Kubernetes cluster can pull the images.
3. Inside the cluster, you need to have a namespace (my-prototype-ns) in which to deploy your prototype. By using a namespace, you keep your prototype from interfering with other applications in the same cluster.
4. Inside the namespace, you need to define the services mariadb, phpmyadmin, and prototype. The service prototype is configured in the values/prototype/local.yaml file.
5. The ingress controller, traefik, can only route traffic to the prototype service if that service has endpoints. The prototype service has endpoints only if the prototype pod is running and it is ready.
6. The Readiness Probe ensures that the pod is ready to accept traffic. If the readiness probe fails, the pod will not be included in the service endpoints.
7. The prototype pod is running only if the prototype deployment is running.
8. The prototype deployment is running only if the prototype container is running.
9. The prototype container is running only if the prototype image is available. The prototype image is available only if the prototype image is built and pushed to the local Docker registry. The prototype image is built and pushed to the local Docker registry only if the Dockerfile is correct and the build pipeline is successful. The Dockerfile is correct and the build pipeline is successful only if the code is correct and the tests are successful. The code is correct and the tests are successful

### Use custom image
By default, the kubelet in the Kubernetes cluster pulls images from docker hub. So, you need to import the image of your prototype into your cluster by hand.
```
# build image from the 'Ampersand' folder
# eg. 'docker build --tag ampersand-prototype:latest .'

# import the image in the Kubernetes cluster
k3d image import --cluster my-prototype-cluster ampersand-prototype:latest

# specify the correct tag in 'values/prototype/local.yaml'
```

### Deploy stack
Required: the namespace in which to run the application.
```shell
# create namespace
kubectl create ns my-prototype-ns
```
The actual command to deploy your prototype (must be run from the deploy directory):
```shell
helmfile --environment local apply
```

After deployment the frontend can be accessed at: http://prototype.127-0-0-1.nip.io:8080
## Troubleshooting
The following commands need to be run in the deploy directory, so they can find the necessary .yaml files.

### probe the differences between a new and an existing (running) deployment
```
helmfile -e local diff
```

  Note: this requires the `helm-diff` plugin to beinstalled:
  ```
  helm plugin install https://github.com/databus23helm-diff
  ```
### wrong source of docker images
Images are obtained from hub.docker.io/bitnami/mariadb:11.2.3-debian-12-r4.
In a previous version, this image was obtained from a proxy, so there may be errors here.

### check the status of the deployment
```shell
❯ kubectl -n my-prototype-ns get deployments
NAME         READY   UP-TO-DATE   AVAILABLE   AGE
phpmyadmin   1/1     1            1           68d
prototype    0/1     1            0           68d
```
The deployment “prototype” should show READY 1/1 and AVAILABLE 1. The next step is to check the pods to find out why the deployment is not running.

```shell
❯ kubectl -n my-prototype-ns get po
NAME                          READY   STATUS              RESTARTS   AGE
mariadb-0                     1/1     Running             0          20h
phpmyadmin-699649bcdc-s2fwc   1/1     Running             0          15h
prototype-7c5d894d96-llbr8    0/1     ContainerCreating   0          12h
prototype-fd57bd787-kzxkt     0/1     ContainerCreating   0          15h
```
The pods apparently have trouble starting. A 'describe' command povides more information.

```shell
❯ kubectl -n my-prototype-ns describe pod/prototype-7c5d894d96-llbr8
Name:             prototype-7c5d894d96-llbr8
Namespace:        my-prototype-ns
...
Events:
  Type     Reason       Age                    From     Message
  ----     ------       ----                   ----     -------
  Warning  FailedMount  9m20s (x362 over 12h)  kubelet  MountVolume.SetUp failed for volume "php-config-volume" : configmap "prototype" not found
  Warning  FailedMount  3m55s (x320 over 12h)  kubelet  Unable to attach or mount volumes: unmounted volumes=[php-config-volume], unattached volumes=[], failed to process volumes=[]: timed out waiting for the condition
```
Apparently, kubectl cannot find the ConfigMap "prototype", which was added for `php.ini`.
```shell
❯ kubectl -n my-prototype-ns get cm
NAME                   DATA   AGE
kube-root-ca.crt       1      68d
mariadb                1      68d
mariadb-init-scripts   1      68d