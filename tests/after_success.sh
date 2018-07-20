#!/bin/sh
#
# After success script for Travis CI
#

# please keep tasks strongly separated,
# no matter they have the same if block

# please set $TX_USER and $TX_TOKEN in your travis dashboard
if [ "$TRAVIS_BRANCH" = "develop" ] && [ "$TRAVIS_PULL_REQUEST" = false ]; then
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

# please set the $GH_TOKEN in your travis dashboard
if [ "$TRAVIS_BRANCH" = "develop" ] && [ "$TRAVIS_PULL_REQUEST" = false ]; then
    #wget http://get.sensiolabs.org/sami.phar -O "$HOME/bin/sami.phar"
    # setup_git only for the main repo and not forks
    echo "Configuring git user"
    git config --global user.email "apps@teclib.com"
    git config --global user.name "Teclib' bot"
    echo "adding a new remote"
    git remote add origin-pages https://"$GH_TOKEN"@github.com/"$TRAVIS_REPO_SLUG".git > /dev/null 2>&1
    echo "fetching from the new remote"
    git fetch origin-pages

    # check if gh-pages exist in remote
    if [ "git branch -r --list origin-pages/gh-pages" ]; then
        echo "generating the docs"
        # clean the repo and generate the docs
        git checkout .
        #php $HOME/bin/sami.phar update "$TRAVIS_BUILD_DIR"/.github/samiConfig.php --force
        find build/tests/coverage/ -type f -name "*.html" -exec sed -i "1s/^/---\\nlayout: coverage\\n---\\n/" "{}" \;
        find build/tests/coverage/ -type f -name "*.html" -exec sed -i "/bootstrap.min.css/d" "{}" \;
        find build/tests/coverage/ -type f -name "*.html" -exec sed -i "/report.css/d" "{}" \;

        # commit_website_files
        echo "adding the coverage report"
        git add build/tests/coverage/*
        echo "creating a branch for the new documents"
        git checkout -b localCi
        git commit -m "changes to be merged"
        git checkout -b gh-pages origin-pages/gh-pages
        git rm -r build/tests/coverage/*
        git checkout localCi build/tests/coverage/

        # upload_files
        echo "pushing the up to date documents"
        git commit --message "docs: update test reports"
        git fetch origin-pages
        git rebase origin-pages/gh-pages
        git push --quiet --set-upstream origin-pages gh-pages --force
    fi
else
    echo "skipping documents update"
fi
