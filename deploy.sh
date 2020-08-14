#!/usr/bin/env bash
## Exit immediately if a command exits with a non-zero status.
set -e
#set distribution folder alias
dev="$HOME"/Sites/AYSO/_dev/volcerts
config="${dev}"/config

prod="$HOME"/Sites/AYSO/_services/vc

PHP=/usr/local/etc/php/7.3/conf.d

## clear the screen
printf "\033c"

echo ">>> Checkout master branch from Git repository..."
#git checkout master
echo

echo ">>> Disable xdebug..."
## Disable xdebug for composer performance
if [[ -e ${PHP}"/ext-xdebug.ini" ]]
then
    mv "$PHP"/ext-xdebug.ini "$PHP"/ext-xdebug.~ini
fi

echo ">>> Clear distribution folder..."
rm -f -r "${prod:?}"
mkdir "${prod}"
echo

echo ">>> Copying app to distribution..."
cp -f -r ./.env.dist "${prod}"/.env
cp -f ./*.json "${prod}"
cp -f ./*.lock "${prod}"

mkdir "${prod}"/bin
cp bin/console "${prod}"/bin

echo ">>> Copying config to distribution..."
mkdir "${prod}"/config
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

cd "${prod}"
#    cp -f -r "${src}"/assets .
#
#    rm -f -r ./assets
#    rm -f -r ./bin/doctrine*

    composer install --no-dev
    yarn install --production=true
    
    bin/console cache:clear --env=prod

cd "${dev}"

echo ">>> Removing development jetsam..."
find "${prod}"/src -type f -name '*Test.php' -delete
find "${prod}" -type f -name '.gitignore' -delete
echo

echo ">>> Re-enable xdebug..."
## Restore xdebug
if [[ -e ${PHP}"/ext-xdebug.~ini" ]]
then
    mv "${PHP}"/ext-xdebug.~ini "${PHP}"/ext-xdebug.ini
fi

echo "...distribution complete"

