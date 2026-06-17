#!/bin/sh

# LEGACY SCRIPT - may not fully work or be required for dev setup to function correctly

# find the HTTPDUSER - this represents the web server user, e.g. Nginx or Apache
HTTPDUSER=$(ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1)

if [ -z "$HTTPDUSER" ] ; then
    echo "Using Docker specific UID of 82 for HTTPDUSER"
    HTTPDUSER=82
fi

# set permissions for future files and folders
echo "Setting permissions for future files and folders in var/"
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var
echo "Setting permissions for future files and folders in tests/Support/Data/uploads/"
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX tests/Support/Data/uploads

# set permissions for existing files and folders
echo "Setting permissions for existing files and folders in var/"
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var
echo "Setting permissions for existing files and folders in tests/Support/Data/uploads/"
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX tests/Support/Data/uploads