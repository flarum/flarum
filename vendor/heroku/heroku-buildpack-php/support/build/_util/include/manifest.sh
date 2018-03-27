#!/bin/bash

print_or_export_manifest_cmd() {
    if [[ "${MANIFEST_CMD:-}" ]]; then
        echo "$1" > $MANIFEST_CMD
    else
        echo "-----> Done. Run '$1' to upload manifest."
    fi
}

generate_manifest_cmd() {
    echo "s3cmd --ssl${AWS_ACCESS_KEY_ID+" --access_key=\$AWS_ACCESS_KEY_ID"}${AWS_SECRET_ACCESS_KEY+" --secret_key=\$AWS_SECRET_ACCESS_KEY"} --acl-public -m application/json put $(pwd)/${1} s3://${S3_BUCKET}/${S3_PREFIX}${1}"
}

soname_version() {
    soname=$(objdump -p $1 | grep SONAME | awk '{ printf $2; }')
    file=$(basename $1)
    echo "${soname#${file}.}"
}