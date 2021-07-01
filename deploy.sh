#!/usr/bin/env bash
## Exit immediately if a command exits with a non-zero status.
set -e
#set folder aliases
ayso="$HOME"/Sites/AYSO

dev="${ayso}"/_dev/volcerts
config="${dev}"/config

prod="$HOME"/Sites/AYSO/_services/vc.ayso1ref.com/vc

PHP=/usr/local/etc/php/8.0/conf.d

## clear the screen
printf "\033c"

echo ">>> Checkout master branch from Git repository..."
#git checkout master
echo

echo ">>> Build production assets..."
yarn encore production --progress
echo

echo ">>> Disable xdebug..."
## Disable xdebug for composer performance
if [[ -e ${PHP}"/ext-xdebug.ini" ]]; then
  mv "$PHP"/ext-xdebug.ini "$PHP"/ext-xdebug.~ini
fi

echo ">>> Clear distribution folder..."
rm -f -r "${prod:?}"
mkdir "${prod}"
echo

echo ">>> Copying app to distribution..."
cp -f ./.env.dist "${prod}"/.env
cp -f ./*.json "${prod}"
cp -f ./*.lock "${prod}"
cp -f .yarnrc.yml "${prod}"
cp -rf .yarn "${prod}"

mkdir "${prod}"/bin
cp bin/console "${prod}"/bin

echo ">>> Copying config to distribution..."
cp -f -r "${config}" "${prod}"

echo ">>> Clear distribution config..."
rm -f -r "${prod}"/config/packages/dev
rm -f -r "${prod}"/config/packages/test
rm -f -r "${prod}"/config/routes/dev

cp -f -r public "${prod}"
cp -f -r src "${prod}"
cp -f -r templates "${prod}"
mkdir "${prod}"/var
echo

echo ">>> Removing OSX jetsam..."
find "${prod}" -type f -name '.DS_Store' -delete
echo

echo ">>> Removing development jetsam..."
find "${prod}"/src -type f -name '*Test.php' -delete
find "${prod}" -type f -name '.gitignore' -delete
echo

cd "${prod}"
  yarn workspaces focus --production
  composer install --no-dev

  rm -r ./assets
  rm -r ./migrations
  rm webpack.config.js
  rm -rf .yarn
  rm -rf .yarnrc.yml

  bin/console cache:clear
  
  ln -s public ../public_html
  
cd "${dev}"

echo ">>> Re-enable xdebug..."
## Restore xdebug
if [[ -e ${PHP}"/ext-xdebug.~ini" ]]; then
  mv "${PHP}"/ext-xdebug.~ini "${PHP}"/ext-xdebug.ini
fi

echo "...distribution complete"
