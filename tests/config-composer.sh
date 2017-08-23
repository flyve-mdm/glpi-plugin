if [ "$TRAVIS_SECURE_ENV_VARS" = "true" ]; then
  mkdir ~/.composer -p
  touch ~/.composer/composer.json
  composer config -g github-oauth.github.com $GH_OAUTH
fi
