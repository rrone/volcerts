#!/usr/bin/env bash
## Exit immediately if a command exits with a non-zero status.
set -e
#set distribution folder alias
src="$HOME"/Sites/AYSO/_dev/volcerts
dist="$HOME"/Sites/AYSO/_services/vc
config="$HOME"/Sites/AYSO/_dev/volcerts/config
PHP=/usr/local/etc/php/7.3/conf.d

## clear the screen
printf "\033c"

echo ">>> Checkout master branch from Git repository..."
#git checkout master
echo

echo ">>> Purge development items..."
## Disable xdebug for composer performance
if [[ -e ${PHP}"/ext-xdebug.ini" ]]
then
    mv "$PHP"/ext-xdebug.ini "$PHP"/ext-xdebug.~ini
fi
yarn prod

echo ">>> Clear distribution folder..."
rm -f -r "${dist:?}"
mkdir "${dist}"
echo

echo ">>> Copying app to distribution..."
cp -f -r ./.env.dist "${dist}"/.env
cp -f ./*.json "${dist}"
cp -f ./*.lock "${dist}"

mkdir "${dist}"/bin
cp bin/console "${dist}"/bin

echo ">>> Copying config to distribution..."
cp -f -r "${config}" "${dist}"
echo ">>> Clear distribution config..."
rm -f -r "${dist}"/config/packages/dev
rm -f -r "${dist}"/config/packages/test
rm -f -r "${dist}"/config/routes/dev

cp -f -r public "${dist}"

cp -f -r src "${dist}"

cp -f -r templates "${dist}"

mkdir "${dist}"/var
echo

echo ">>> Removing OSX jetsam..."
find "${dist}" -type f -name '.DS_Store' -delete
echo

echo ">>> Removing development jetsam..."
find "${dist}"/src -type f -name '*Test.php' -delete
echo

cd "${dist}"
    cp -f -r "${src}"/assets .

    rm -f -r ./assets
    rm -f -r ./bin/doctrine*

    composer install --no-dev

    bin/console cache:clear
echo "here"

cd "${src}"

echo ">>> Restore composer development items..."
## Restore xdebug
if [[ -e ${PHP}"/ext-xdebug.~ini" ]]
then
    mv "${PHP}"/ext-xdebug.~ini "${PHP}"/ext-xdebug.ini
fi
yarn dev

echo "...distribution complete"

