#!/bin/sh
SOLR_VERSION=7.5.0
JENA_VERSION=2.3.0

set -e

#install apache2

# install solr:
mkdir -p /tmp/solr
cd /tmp/solr
wget "http://archive.apache.org/dist/lucene/solr/${SOLR_VERSION}/solr-${SOLR_VERSION}.zip"
# wget "http://apache.mirror.triple-it.nl/lucene/solr/${SOLR_VERSION}/solr-${SOLR_VERSION}.zip"
unzip solr-${SOLR_VERSION}.zip
mkdir /opt/solr
cp -r /tmp/solr/solr-${SOLR_VERSION}/* /opt/solr
mkdir -p /opt/solr/server/solr/openskos/conf
touch /opt/solr/server/solr/openskos/core.properties
cp ${TRAVIS_BUILD_DIR}/data/solr/solrconfig.xml /opt/solr/server/solr/openskos/conf/solrconfig.xml
cp ${TRAVIS_BUILD_DIR}/data/solr/schema.xml /opt/solr/server/solr/openskos/conf/schema.xml
chmod 755 ${TRAVIS_BUILD_DIR}/integrationtestsettings/start-solr.sh

# install fuseki:
tar -zxvf ${TRAVIS_BUILD_DIR}/integrationtestsettings/apache-jena-fuseki-${JENA_VERSION}.tar.gz -C /opt
mv /opt/apache-jena-fuseki-${JENA_VERSION} /opt/apache-jena-fuseki
chmod -R ugo+rw /opt/apache-jena-fuseki
chmod +x /opt/apache-jena-fuseki/fuseki-server /opt/apache-jena-fuseki/bin/*
mkdir -p /opt/apache-jena-fuseki/run
cp -r ${TRAVIS_BUILD_DIR}/data/travis/jena/configuration /opt/apache-jena-fuseki/run/configuration
mkdir /opt/apache-jena-fuseki/logs
sudo mkdir -p /fuseki/databases/openskos
chmod 755 ${TRAVIS_BUILD_DIR}/integrationtestsettings/start-fuseki.sh

#mysql
chmod 755  ${TRAVIS_BUILD_DIR}/integrationtestsettings/openskos-create.sql

# initialisation
chmod 755  ${TRAVIS_BUILD_DIR}/integrationtestsettings/openskos-init.sh
