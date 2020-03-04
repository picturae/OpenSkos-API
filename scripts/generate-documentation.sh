#!/usr/bin/env bash

# Ensure bash
[ -z "${BASH}" ] && exec bash "$0" "$@"

# Fail hard
set -e

# chdir into approot
bindir=$(cd $(dirname $0) ; pwd)
cd "${bindir}/.."
approot=$(pwd)

# Error if the ontology changed
echo " --> Generating ontology classes"
php "${approot}/bin/console" ontology:generate
difflines=$(git diff config/ontology | wc -l)
if (( $difflines > 0 )); then
  echo "Changes were made to the ontology" >&2
  echo "Please commit check and stage these first" >&2
  exit 1
fi

# Error if the documentation changed
echo " --> Generating swagger documentation"
php "${approot}/bin/console" swagger:generate
difflines=$(git diff public/swagger.yaml | wc -l)
if (( $difflines > 0 )); then
  echo "Changes were made to public/swagger.yaml" >&2
  echo "Please commit check and stage these first" >&2
  exit 1
fi

# Error if the errors changed
echo " --> Generating error documentation"
php "${approot}/bin/console" exception:errorlist
difflines=$(git diff src/Exception/list.json | wc -l)
if (( $difflines > 0 )); then
  echo "Changes were made to src/Exception/list.json" >&2
  echo "Please commit check and stage these first" >&2
  exit 1
fi
