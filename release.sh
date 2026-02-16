#!/bin/bash

# ShortcodeGlut Release Script
# Usage: ./release.sh 1.2.0

VERSION=$1

if [ -z "$VERSION" ]; then
    echo "Usage: ./release.sh VERSION"
    echo "Example: ./release.sh 1.2.0"
    exit 1
fi

# Update version in main plugin file
sed -i "s/Version: .*/Version: $VERSION/" shortcodeglut.php

# Commit the version change
git add shortcodeglut.php
git commit -m "Version $VERSION"

# Create and push tag
git tag v$VERSION
git push origin main
git push origin v$VERSION

echo "✅ ShortcodeGlut v$VERSION released successfully!"
