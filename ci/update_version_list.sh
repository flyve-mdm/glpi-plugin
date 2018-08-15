#!/usr/bin/env bash

# only update list when coverage is updated
if [[ $TRAVIS_COMMIT_MESSAGE == *"docs: update test reports"* ]]; then

# check if support folder exists
if [ -d "development/coverage/support" ]; then

# remove list to create a new one and not duplicate folders
rm ./_data/whitelist_version.yml

# create fresh list
touch ./_data/whitelist_version.yml

# set path to directory where the versions folders are
FOLDER_PATH="development/coverage/support"

# get folders in release directory
DIRS=`ls $FOLDER_PATH`

# add version folders to list
for DIR in $DIRS
do
echo  - ${DIR} >> ./_data/whitelist_version.yml
done
# if the list has changed commit and push changes
  if [ -n "$(git status --porcelain _data/whitelist_version.yml)" ]; then

    echo "Updating version list"

    # configure git
    git config --global user.email "apps@teclib.com"
    git config --global user.name "Teclib' bot"

    # add new remote to push changes
    git remote remove origin
    git remote add origin https://$GH_TOKEN@github.com/$TRAVIS_REPO_SLUG.git

    git checkout $TRAVIS_BRANCH
    git add _data/whitelist_version.yml && git commit -m "ci(list): add new version to list"
    git push origin gh-pages
  fi
fi

fi