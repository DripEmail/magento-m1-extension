#!/bin/bash
set -e

CONFIG_XML_PATH="app/code/community/Drip/Connect/etc/config.xml"
PACKAGE_XML_PATH="package.xml"

# Check for uncommitted changes.
if (! git diff-index --quiet HEAD --); then
  echo "There are uncommitted changes, please commit them. Exiting..."
  exit
fi

# Get release branch version.
current_branch=$(git branch --show-current)
if [[ "$current_branch" = 'master' ]]; then
  echo "You should release from a release branch, not master. Exiting..."
  exit
fi
branch_version=$(echo -n "$current_branch" | cut -d'-' -f2)
echo "Detected branch version is $branch_version"

# Check for XML versions
if (! grep "<version>$branch_version</version>" $CONFIG_XML_PATH > /dev/null); then
  echo "$CONFIG_XML_PATH does not contain the right version. Exiting..."
  exit
fi

if (! grep "<version>$branch_version</version>" $PACKAGE_XML_PATH > /dev/null); then
  echo "$PACKAGE_XML_PATH does not contain the right version. Exiting..."
  exit
fi

echo "All versions check out. Generating tarball..."

git archive --format=tar HEAD | gzip - > drip_m1connect-$(echo -n $branch_version | tr '.' '_')-$(date "+%Y-%m-%d").tgz

echo "Tarball generated. Don't forget to tag a release and push the tag."
