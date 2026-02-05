#!/usr/bin/env bash
set -euo pipefail

# Install JS packages via AssetMapper importmap
bin/console importmap:require chosen-js
bin/console importmap:require compare-versions
bin/console importmap:require jquery
bin/console importmap:require jstree
bin/console importmap:require lightgallery
bin/console importmap:require "lightgallery/css/lightgallery.css"
bin/console importmap:require "lightgallery/css/lg-thumbnail.css"
bin/console importmap:require "lightgallery/css/lg-zoom.css"
bin/console importmap:require mirador
bin/console importmap:require openseadragon
bin/console importmap:require sortablejs
bin/console importmap:require tablesaw

mkdir -p assets/vendor/lightgallery/css assets/vendor/lightgallery/fonts assets/vendor/lightgallery/images
cp -f public/application/asset/vendor/lightgallery/css/*.css assets/vendor/lightgallery/css/
cp -f public/application/asset/vendor/lightgallery/fonts/* assets/vendor/lightgallery/fonts/
cp -f public/application/asset/vendor/lightgallery/images/* assets/vendor/lightgallery/images/
