#!/bin/sh

ZIP_FILE_NAME=shopgate-oxid-integration-${TAG_NAME}-${PHP_VERSION}.zip

rm -rf src/modules/shopgate/vendor release/package $ZIP_FILE_NAME

mkdir release/package
mkdir release/package/Ab_eShop_4.7
mkdir release/package/Ab_eShop_4.7/copy_this
composer install -vvv --no-dev
rsync -av --exclude-from './release/exclude-filelist.txt' ./src/ release/package/Ab_eShop_4.7/copy_this
rsync -av ./README.md release/package/Ab_eShop_4.7/copy_this/modules/shopgate
rsync -av ./LICENSE.md release/package/Ab_eShop_4.7/copy_this/modules/shopgate
rsync -av ./CONTRIBUTING.md release/package/Ab_eShop_4.7/copy_this/modules/shopgate
rsync -av ./CHANGELOG.md release/package/Ab_eShop_4.7/copy_this/modules/shopgate

mkdir release/package/Bis_eShop_4.6
mkdir release/package/Bis_eShop_4.6/copy_this
rsync -av --exclude-from './release/exclude-filelist.txt' --exclude 'views/' --exclude 'out/' --exclude 'core/' --exclude 'admin/' ./src/ release/package/Bis_eShop_4.6/copy_this
rsync -av ./src/modules/shopgate/views release/package/Bis_eShop_4.6/copy_this
rsync -av ./src/modules/shopgate/out release/package/Bis_eShop_4.6/copy_this
rsync -av ./src/modules/shopgate/core release/package/Bis_eShop_4.6/copy_this
rsync -av ./src/modules/shopgate/admin release/package/Bis_eShop_4.6/copy_this
rsync -av ./README.md release/package/Bis_eShop_4.6/copy_this/modules/shopgate
rsync -av ./LICENSE.md release/package/Bis_eShop_4.6/copy_this/modules/shopgate
rsync -av ./CONTRIBUTING.md release/package/Bis_eShop_4.6/copy_this/modules/shopgate
rsync -av ./CHANGELOG.md release/package/Bis_eShop_4.6/copy_this/modules/shopgate

cd release/package
zip -r ../../$ZIP_FILE_NAME .
