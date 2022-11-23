#!/bin/bash

echo Removing unused vendor files to reduce space
rm vendor/symfony/validator/Resources/translations/*.xlf || true
