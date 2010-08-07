#!/bin/bash

# make distributable version of plugin
DOKUWIKI=~/Sites/dokuwiki

echo "Creating distribution"
git clone ~/dev/plugin-cli /tmp/plugin-cli
cd /tmp
tar cvfz plugin-cli.tar.gz ./plugin-cli
zip -r plugin-cli.zip ./plugin-cli

echo "Installing files to $DOKUWIKI"
cp /tmp/plugin-cli.{tar.gz,zip} $DOKUWIKI/lib/plugins
cp /tmp/plugin-cli/cli-plugin.txt $DOKUWIKI/data/pages/plugins/cli.txt
cp /tmp/plugin-cli/cli-examples.txt $DOKUWIKI/data/pages/test/cli.txt
dos2unix $DOKUWIKI/data/pages/{test/cli.txt,plugins/cli.txt}

echo "Cleaning up"
rm /tmp/plugin-cli.{tar.gz,zip}
rm -rf /tmp/plugin-cli



