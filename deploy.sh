#!/bin/bash
set -e

# deploy to Quay public repository
docker login -u="$QUAY_USERNAME" -p="$QUAY_PASSWORD" quay.io
docker tag keboola/aws-parameter-filler quay.io/keboola/aws-parameter-filler:${TRAVIS_TAG}
docker tag keboola/aws-parameter-filler quay.io/keboola/aws-parameter-filler:latest
docker images
docker push quay.io/keboola/aws-parameter-filler:${TRAVIS_TAG}
docker push quay.io/keboola/aws-parameter-filler:latest
