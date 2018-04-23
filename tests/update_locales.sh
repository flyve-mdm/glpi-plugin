#!/bin/sh
# please set $TX_USER and $TX_TOKEN in your travis dashboard

if [ "$TRAVIS_BRANCH" = "develop" ] && [ "$TRAVIS_PULL_REQUEST" = false ]; then
    # check if gh-pages exist in remote
    echo "updating source language"
    sudo apt install transifex-client
    echo "[https://www.transifex.com]" > ~/.transifexrc
    echo "api_hostname = https://api.transifex.com" >> ~/.transifexrc
    echo "hostname = https://www.transifex.com" >> ~/.transifexrc
    echo "token = ${TX_TOKEN}" >> ~/.transifexrc
    echo "password = ${TX_TOKEN}" >> ~/.transifexrc
    echo "username = ${TX_USER}" >> ~/.transifexrc
    php vendor/bin/robo locales:send
else
    echo "skipping source language update"
fi
