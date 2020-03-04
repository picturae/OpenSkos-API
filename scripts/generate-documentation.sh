#!/usr/bin/env bash

# Fail hard
set -e

# chdir into approot
bindir=$(cd $(dirname $0) ; pwd)
cd "${bindir}/.."
approot=$(pwd)

# Generate ontology & documentation
php "${approot}/bin/console" ontology:generate
php "${approot}/bin/console" swagger:generate
php "${approot}/bin/console" exception:errorlist

# Add the updates to the commit
git add config/ontology
git add public/swagger.yaml
git add src/Exception/list.json
