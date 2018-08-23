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

# find if we are in a valid branch to build docs
if echo "$TRAVIS_BRANCH" | grep -q -P '^(master|develop|support/|release/)'; then
    REGEX_BRANCH=true
else
    REGEX_BRANCH=false
fi

if [ "$REGEX_BRANCH" = true ] && [ "$TRAVIS_PULL_REQUEST" = false ]; then
    #wget http://get.sensiolabs.org/sami.phar -O "$HOME/bin/sami.phar"
    # setup_git only for the main repo and not forks
    echo "Configuring git user"
    git config --global user.email "apps@teclib.com"
    git config --global user.name "Teclib' bot"
    echo "adding a new remote"
    # please set the $GH_TOKEN in your travis dashboard
    git remote add origin-pages https://"$GH_TOKEN"@github.com/"$TRAVIS_REPO_SLUG".git > /dev/null 2>&1
    echo "fetching from the new remote"
    git fetch origin-pages

    # check if gh-pages exist in remote
    if [ "git branch -r --list origin-pages/gh-pages" ]; then
        echo "generating the docs"
        # clean the repo and generate the docs
        git checkout .
#        echo "code documentation"
#        wget -O apigen.phar https://github.com/ApiGen/ApiGen/releases/download/v4.1.0/apigen-4.1.0.phar
#        php apigen.phar generate -s inc -d development/code-documentation/"$TRAVIS_BRANCH"/
        echo "code coverage"
        find development/coverage/"$TRAVIS_BRANCH"/ -type f -name "*.html" -exec sed -i "1s/^/---\\nlayout: coverage\\n---\\n/" "{}" \;
        find development/coverage/"$TRAVIS_BRANCH"/ -type f -name "*.html" -exec sed -i "/bootstrap.min.css/d" "{}" \;
        find development/coverage/"$TRAVIS_BRANCH"/ -type f -name "*.html" -exec sed -i "/report.css/d" "{}" \;

        # commit_website_files
        echo "adding the code documentation report"
        git add development/code-documentation/"$TRAVIS_BRANCH"/*
        echo "adding the coverage report"
        git add development/coverage/"$TRAVIS_BRANCH"/*
        echo "creating a branch for the new documents"
        git checkout -b localCi
        git commit -m "changes to be merged"
        git checkout -b gh-pages origin-pages/gh-pages
        git rm -r development/code-documentation/"$TRAVIS_BRANCH"/*
        git rm -r development/coverage/"$TRAVIS_BRANCH"/*
        git checkout localCi development/code-documentation/"$TRAVIS_BRANCH"/
        git checkout localCi development/coverage/"$TRAVIS_BRANCH"/

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
