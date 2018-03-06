# define a short-hand to our fcgi proxy, for convenience
# Define heroku-fcgi fcgi://127.0.0.1:4999
Define heroku-fcgi unix:/tmp/heroku.fcgi.${PORT}.sock|fcgi://heroku-fcgi

# make sure the proxy is registered with the unix socket; we can then use just "fcgi://heroku-fcgi" in proxy and rewrites directives
# we have to do this because we can't rewrite to a UDS location and because PHP doesn't support the unix:...|fcgi://... syntax
# this is also a lot more convenient for users
# http://thread.gmane.org/gmane.comp.apache.devel/52892
<Proxy "${heroku-fcgi}">
    # we must declare a parameter in here or it'll not register the proxy ahead of time
    # min=0 is an obvious candidate since that's the default value already and sensible
    ProxySet min=0
</Proxy>

Listen ${PORT}

<VirtualHost *:${PORT}>

    ServerName localhost

    ErrorLog /tmp/heroku.apache2_error.${PORT}.log
    # redefine "combined" log format so includes can overwrite it
    LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" heroku
    CustomLog /tmp/heroku.apache2_access.${PORT}.log heroku

    TraceEnable off

    <Directory "${HEROKU_APP_DIR}">
        # lock it down fully by default
        # if it's also the docroot, it'll be opened up again further below
        Require all denied
        <FilesMatch "^(\.|composer\.(json|lock|phar)$|Procfile$)">
            # explicitly deny these again, merged with the docroot later
            Require all denied
        </FilesMatch>
    </Directory>
    # handle these separately; who knows where they are and whether they're accessible
    <Directory "${HEROKU_APP_DIR}/${COMPOSER_VENDOR_DIR}">
        Require all denied
    </Directory>
    <Directory "${HEROKU_APP_DIR}/${COMPOSER_BIN_DIR}">
        Require all denied
    </Directory>

    DocumentRoot "${DOCUMENT_ROOT}"

    <Directory "${DOCUMENT_ROOT}">
        Options FollowSymLinks

        # allow .htaccess to do everything
        AllowOverride All

        # no limits
        Require all granted
    </Directory>

    # mod_proxy doesn't forward the Authorization header, must fix that ourselves
    SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1

    # pass requests to .php files to mod_proxy_fcgi
    # this requires Apache 2.4.10+ and PHP 5.5.15+ to work properly
    <FilesMatch \.php$>
        <If "-f %{REQUEST_FILENAME}"> # make sure the file exists so that if not, Apache will show its 404 page and not FPM
            SetHandler proxy:fcgi://heroku-fcgi
        </If>
    </FilesMatch>

    Include "${HEROKU_PHP_HTTPD_CONFIG_INCLUDE}"

</VirtualHost>
