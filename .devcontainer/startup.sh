set -e

minikube start

kubectl apply -k ./.devcontainer/backend

ampersand daemon