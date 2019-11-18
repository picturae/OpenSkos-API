#!/bin/sh

#install apache2

# install solr:
mkdir -p /tmp/solr || exit $?
cd /tmp/solr || exit $?
# wget "https://archive.apache.org/dist/lucene/solr/6.3.0/solr-6.3.0.zip"  || exit $?
wget "http://apache.mirror.triple-it.nl/lucene/solr/5.5.4/solr-5.5.4.zip" || exit $?
unzip solr-5.5.4.zip  || exit $?
mkdir /opt/solr  || exit $?
cp -r /tmp/solr/solr-5.5.4/* /opt/solr  || exit $?
mkdir -p /opt/solr/server/solr/openskos/conf  || exit $?
touch /opt/solr/server/solr/openskos/core.properties || exit $?
cp ${TRAVIS_BUILD_DIR}/data/solr/solrconfig.xml /opt/solr/server/solr/openskos/conf/solrconfig.xml || exit $?
cp ${TRAVIS_BUILD_DIR}/data/solr/schema.xml /opt/solr/server/solr/openskos/conf/schema.xml || exit $?
chmod 755 ${TRAVIS_BUILD_DIR}/integrationtestsettings/start-solr.sh || exit $?

# install fuseki:
tar -zxvf ${TRAVIS_BUILD_DIR}/integrationtestsettings/apache-jena-fuseki-2.3.0.tar.gz -C /opt || exit $?
mv /opt/apache-jena-fuseki-2.3.0 /opt/apache-jena-fuseki || exit $?
chmod -R ugo+rw /opt/apache-jena-fuseki  || exit $?
chmod +x /opt/apache-jena-fuseki/fuseki-server /opt/apache-jena-fuseki/bin/*  || exit $?
mkdir -p /opt/apache-jena-fuseki/run  || exit $?
cp -r ${TRAVIS_BUILD_DIR}/data/travis/jena/configuration /opt/apache-jena-fuseki/run/configuration || exit $?
mkdir /opt/apache-jena-fuseki/logs || exit $?
chmod 755 ${TRAVIS_BUILD_DIR}/integrationtestsettings/start-fuseki.sh || exit $?

#mysql
chmod 755  ${TRAVIS_BUILD_DIR}/integrationtestsettings/openskos-create.sql || exit $?

# initialisation
chmod 755  ${TRAVIS_BUILD_DIR}/integrationtestsettings/openskos-init.sh || exit $?
