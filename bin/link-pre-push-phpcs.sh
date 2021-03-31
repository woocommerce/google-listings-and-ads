#!/bin/sh

# Symlink the git pre-push hook to its destination.
if [ ! -h ".git/hooks/pre-push" ] ; then
  echo Linking pre-push hook script
  ln -s "bin/pre-push" ".git/hooks/pre-push"
fi
