#!/usr/bin/env bash

########
# fullbuild by Kulga
#
# This script should make creating a release much easier.
########

(
    # Extensions to be compiled. Must by located in https://github.com/flarum/$extension
    default_extensions='bbcode emoji likes lock markdown mentions pusher sticky subscriptions suspend tags akismet approval'


    update_repos() {
        ### Update Repo's
        cd $flarum_source
        git pull || { git clone https://github.com/flarum/flarum; flarum_source="$flarum_source"/flarum; }
        (
            # Ensure base directory is up to date
            cd $flarum_source
            echo latest commit $(git log -1 | head -n1 | cut -d\  -f2 | cut -b1-10) - flarum
            # Ensure Core is up to date - https://github.com/flarum/core
            git clone https://github.com/flarum/core 2> /dev/null || (cd flarum/core/ && git pull)
            echo latest commit $(git log -1 | head -n1 | cut -d\  -f2 | cut -b1-10) - flarum/core

            # Ensure Extensions are up to date
            for extension in $default_extensions;
            do
            (
                cd extensions/
                mkdir -p $extension
                git clone https://github.com/flarum/"$extension" || (cd $extension; git pull)
                (cd $extension; echo latest commit $(git log -1 | head -n1 | cut -d\  -f2 | cut -b1-10) - flarum/$extension)
            )
            done
        )
    }

    copytorelease() {
        ### Copy files to compiling area
        (
            cd $flarum_source
            # Primary flarum scripts
            git archive --format tar --worktree-attributes HEAD | tar -xC $compiled_flarum

            for extension in $default_extensions
            do
            (
                mkdir -p $compiled_flarum/extensions/$extension
                cd extensions/$extension
                git archive --format tar --worktree-attributes HEAD | tar -xC "$compiled_flarum/extensions/$extension"
            )
            done
        )
    }

    compilereleaseflarum() {
        ### Compile

        ## Compile Core
        (
            cd $compiled_flarum

            (
                cd flarum
                composer require flarum/core:dev-master@dev --prefer-dist --update-no-dev
                composer install --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-dev
                # Copy public files
                rsync -av $compiled_flarum/flarum/vendor/flarum/core/public/* $compiled_flarum/assets
            )

            (
                cd flarum/vendor/flarum/core/js
                bower install
            )

            (
                cd flarum/vendor/flarum/core/js
                for app in forum admin
                do
                (
                    cd $app
                    npm link gulp flarum-gulp babel-core
                    gulp --production
                    rm -rf "$app"/node_modules
                )
                done
            )

            rm -rf flarum/vendor/flarum/core/js/bower_components
        )

        ## | Compile Core


        ## Compile Extensions
        (
            cd $compiled_flarum
            for extension in $default_extensions;
            do
            (
                cd extensions/$extension
                composer install --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-dev
                if [ -f js/bower.json ]; then ( cd js && bower install ); fi

                for app in forum admin
                do

                    if [ -d js/$app ]; then
                    (
                        cd js/$app
                        if [ -f bower.json ]; then bower install; fi
                        npm link gulp flarum-gulp
                        gulp --production
                        rm -rf node_modules bower_components
                    )
                    fi

                done
            )

            rm -rf "$compiled_flarum/extensions/$extension/js/bower_components"
            done

            ## Extra extensions should go here

        )

        ## | Compile Extensions
    }

    removeextras() {
        ### Remove Extra Files
        (
            cd $compiled_flarum

            rm -rf build.sh
            rm -rf Vagrantfile
            rm -rf flarum/vagrant
            rm -rf flarum/core
            rm -rf flarum/studio.json
        )
    }

    wrapup() {
        ### Permissions
        (
            cd $compiled_flarum
            find ./ -type d -exec chmod 0750 {} \;
            find ./ -type f -exec chmod 0644 {} \;
            chmod 0775 ./
            chmod -R 0775 assets flarum/storage flarum/flarum

            # Create Checksum - run md5sum -c in extracted directory to check
            find . -type f -exec md5sum {} \; &> CHECKSUM

            zip ./ /"$export"flarum_$(date +\%m_\%d_\%Y).zip
            tar --preserve-permissions --preserve-order -zcf "$export"flarum_$(date +\%m_\%d_\%Y).tar.gz ./

            # Remove /tmp/tmp.* files if not designated by -s
            if [ ${removetmp:-no} == yes ]; then rm -fr $compiled_flarum; fi

            echo -e '\nmd5sums'
            md5sum "$export"flarum_$(date +\%m_\%d_\%Y).{zip,tar.gz}
        )
    }

    while getopts "cd:e:his:" opt; do
        case "$opt" in
            c)
                # Compiles the release flarum
                update_repos
                copytorelease
                compilereleaseflarum
                exit
                ;;
            d)
                if [ ! -d "$OPTARG" ]; then mkdir -p "$OPTARG"; fi
                compiled_flarum="$(cd -P "$OPTARG"; pwd)/"
                ;;
            e)
                # Where to export files
                if [ ! -d "$OPTARG" ]; then mkdir -p "$OPTARG"; fi
                export="$(cd -P $OPTARG; pwd)/"
                ;;
            h)
                echo ""
                echo -e "-c Compiles whatever files exist at -d\n"
                echo -e "-d Designate where to place temporary compile files - defaults to /tmp/tmp.nn directory\n"
                echo -e "-e Where to export .zip / tar of flarum\n"
                echo -e "-i Full update and creation of latest Flarum\n"
                echo -e "-h Display this help\n"
                echo -e "-s Designate source of flarum files\n"
                exit
                ;;
            i)
                #
                wrapup
                exit
                ;;
            s)
                if [ ! -d "$OPTARG" ]; then
                    mkdir -p "$OPTARG"
                    (
                        cd "$OPTARG"
                        git pull || git clone https://github.com/flarum/flarum .
                    )
                fi
                flarum_source="$(cd -P "$OPTARG"; pwd)/"

                ;;
            \?)
                echo "Invalid option: -$OPTARG" >&2
                echo "-h for help"
                exit
                ;;
        esac
    done


    # Set flarum_source if not already set - default to script location
    if [ -v $flarum_source ]; then flarum_source=$(cd `dirname "${BASH_SOURCE[0]}"` && pwd); fi
    # Set compiled_flarum location if not already set - default to random /tmp/tmp.* location
    if [ -v $compiled_flarum ]; then compiled_flarum=$(mktemp -d); removetmp=yes; fi
    if [ -v $export ]; then export=/tmp/; fi


    update_repos
    copytorelease
    compilereleaseflarum
    removeextras
    wrapup
)
