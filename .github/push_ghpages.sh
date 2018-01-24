#!/bin/sh
# please set the $GH_TOKEN in your travis dashboard

if [ "$TRAVIS_BRANCH" = "master" ] || [ "$TRAVIS_BRANCH" = "develop" ] && [ "$TRAVIS_PULL_REQUEST" = false ]; then
    #wget http://get.sensiolabs.org/sami.phar -O "$HOME/bin/sami.phar"
    # setup_git only for the main repo and not forks
    openssl aes-256-cbc -k $encrypted_3f03f06b7880_key -iv $encrypted_3f03f06b7880_iv -in github_deploy_key.enc -out /tmp/github_deploy_key -d
    git config --global user.email "apps@teclib.com"
    git config --global user.name "Teclib' bot"
    git remote add origin-pages git@github.com:"$TRAVIS_REPO_SLUG".git > /dev/null 2>&1
    git fetch origin-pages

    # check if gh-pages exist in remote
    if [ "git branch -r --list origin-pages/gh-pages" ]; then
        # clean the repo and generate the docs
        git checkout composer.lock
        #php $HOME/bin/sami.phar update "$TRAVIS_BUILD_DIR"/.github/samiConfig.php --force
        find build/ -type f -name "*.html" -exec sed -i "1s/^/---\\nlayout: container\\n---\\n/" "{}" \;

        # commit_website_files
        if [ "$TRAVIS_BRANCH" = "develop" ]; then
            git add build/tests/coverage/*
        fi
        #git add build/docs/*
        git checkout -b localCi
        git commit -m "changes to be merged"
        git checkout -b gh-pages origin-pages/gh-pages
        git checkout localCi build/

        # upload_files
        git commit --message "docs: update test reports"
        git rebase origin-pages/gh-pages
        git push --quiet --set-upstream origin-pages gh-pages --force
    fi
fi
