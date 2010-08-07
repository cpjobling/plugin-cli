#!/bin/sh

# make distributable version of plugin
DOKUWIKI=/cygdrive/c/xampp/htdocs/dokuwiki

echo "Creating distribution"
svn export . /tmp/cli
cd /tmp
tar cvfz plugin-cli.tar.gz ./cli
zip -r plugin-cli.zip ./cli

echo "Installing files to $DOKUWIKI"
cp /tmp/plugin-cli.{tar.gz,zip} $DOKUWIKI/lib/plugins
cp /tmp/cli/cli-plugin.txt $DOKUWIKI/data/pages/plugins/cli.txt
cp /tmp/cli/cli-examples.txt $DOKUWIKI/data/pages/test/cli.txt
dos2unix $DOKUWIKI/data/pages/{test/cli.txt,plugins/cli.txt}

echo "Cleaning up"
rm /tmp/plugin-cli.{tar.gz,zip}
rm -rf /tmp/cli



