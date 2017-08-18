if [ "$TRAVIS_SECURE_ENV_VARS" = "true" ]; then
  composer config -g github-oauth.github.com $GH_OAUTH
fi
