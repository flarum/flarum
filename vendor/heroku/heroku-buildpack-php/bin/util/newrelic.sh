#!/usr/bin/env bash

install_newrelic_ext() {
    # special treatment for New Relic; we enable it if we detect a license key for it
    # otherwise users would have to have it in their require section, which is annoying in development environments
    NEW_RELIC_LICENSE_KEY=${NEW_RELIC_LICENSE_KEY:-}
    if [[ "$engine" == "php" && -n "$NEW_RELIC_LICENSE_KEY" ]] && ! $engine -n $(which composer) show -d "$build_dir/.heroku/php" --installed --quiet heroku-sys/ext-newrelic 2>/dev/null; then
        if $engine -n $(which composer) require --update-no-dev -d "$build_dir/.heroku/php" -- "heroku-sys/ext-newrelic:*" >> $build_dir/.heroku/php/install.log 2>&1; then
            echo "- New Relic detected, installed ext-newrelic" | indent
        else
            warning_inline "New Relic detected, but no suitable extension available"
        fi
    fi
}

install_newrelic_userini() {
    if [[ "$engine" == "php" && -n "${NEW_RELIC_CONFIG_FILE:-}" ]]; then
        if [[ ! -f "${NEW_RELIC_CONFIG_FILE}" ]]; then
            error "Config var 'NEW_RELIC_CONFIG_FILE' points to non existing file
'${NEW_RELIC_CONFIG_FILE}'"
        fi
        notice_inline "Using custom New Relic config '${NEW_RELIC_CONFIG_FILE}'"
        ( cd $build_dir/.heroku/php/etc/php/conf.d; ln -s "../../../../../${NEW_RELIC_CONFIG_FILE}" "ext-newrelic.user.ini" )
    fi
}
