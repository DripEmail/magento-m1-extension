#!/usr/bin/env bash

cd "$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

host=648846177135.dkr.ecr.us-east-1.amazonaws.com
repo=eci
name=magento-m1-extension
tag=1.9.4.3

image=$host/$repo/$name:$tag

docker build --build-arg CONTINUOUS_INTEGRATION="true" -t $image .
