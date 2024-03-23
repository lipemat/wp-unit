#!/bin/sh

##################################################################
#
# `yoast/phpunit-polyfills` adds a bunch of phpunit
# related requirements which are not used when this package
# is used as a standalone.
#
# Instead of letting the superfluous dependencies clutter up
# our IDE map, we remove them via the `post-*-cmd` composer scripts.
#
###################################################################

# shellcheck disable=SC2039
array=("vendor/yoast/" "vendor/composer/" "vendor/dg/" )

echo "Cleaning unused directories...".

for directory in vendor/*/ ; do
    # shellcheck disable=SC2039
    if [[ ! "${array[*]}" =~ $directory ]]; then
        rm -rf "$directory"
    fi
done;

# Regenerate the autoloader with files removed.
composer dump-autoload
