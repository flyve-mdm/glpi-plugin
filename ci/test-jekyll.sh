#!/usr/bin/env bash
set -e # halt script on error

bundle exec jekyll build
rm -rf _site/reports
rm -rf _site/screenshots
bundle exec htmlproofer ./_site --allow-hash-href --url-ignore "/#.*/" --assume-extension --disable-external --file-ignore ./_site/CHANGELOG.html