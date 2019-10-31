#!/bin/bash

set -ex

# Link dynamic configurations of www.conf to nginx
ln -rsf conf/www.d/ /etc/nginx/

# Process template file and replace default.conf of nginx
envsubst \
    '$PORT $DOMAIN $API_PREFIX
    $FPM_ROOT $FPM_ENTRYPOINT $FPM_URL' \
    < conf/www.tpl.nginx \
    > /etc/nginx/conf.d/default.conf

if [ "$APP_ENV" == "dev" ]; then
    echo "Applied configuration: "
    cat /etc/nginx/conf.d/default.conf
    echo "" #Print End Of Line for good formatting
    echo "End of Configuration."
fi

# Run nginx
exec nginx-debug -g 'daemon off;'