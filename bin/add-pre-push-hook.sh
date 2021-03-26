#!/bin/sh

# Symlink the git pre-push hook to its destination.
if [ ! -h ".git/hooks/pre-push" ] ; then
  echo Symlinking the pre-push hook from "vendor/pfrenssen/phpcs-pre-push"
  ln -s "../../vendor/pfrenssen/phpcs-pre-push/pre-push" ".git/hooks/pre-push"
fi
