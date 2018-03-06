#!/usr/bin/env bash

# fail hard
set -o pipefail
# fail harder
set -eu

publish=true

# process flags
optstring=":-:"
while getopts "$optstring" opt; do
	case $opt in
		-)
			case "$OPTARG" in
				no-publish)
					publish=false
					;;
				*)
					echo "Invalid option: --$OPTARG" >&2
					exit 2
					;;
			esac
	esac
done
# clear processed arguments
shift $((OPTIND-1))

if [[ $# -lt "1" ]]; then
	echo "Usage: $(basename $0) [--no-publish] MANIFEST..." >&2
	echo "  MANIFEST: name of manifest file, e.g. 'ext-event-2.0.0_php-5.6.composer.json'" >&2
	echo "  If --no-publish is given, mkrepo.sh will NOT be invoked after removal to" >&2
	echo "  re-generate the repo." >&2
	echo "  CAUTION: re-generating the repo will cause all manifests in the bucket" >&2
	echo "  to be included in the repo, including potentially currently unpublished ones." >&2
	echo "  CAUTION: using --no-publish means the repo will point to non-existing packages" >&2
	echo "  until 'mkrepo.sh --upload' is run!" >&2
	echo " Bucket name and prefix will be read from '\$S3_BUCKET' and '\$S3_PREFIX'." >&2
	exit 2
fi

S3_PREFIX=${S3_PREFIX:-}

here=$(cd $(dirname $0); pwd)

manifests=("$@")

indices="${!manifests[@]}"
for index in $indices; do
	manifests[$index]="s3://${S3_BUCKET}/${S3_PREFIX}${manifests[$index]%.composer.json}.composer.json"
done

manifests_tmp=$(mktemp -d -t "dst-repo.XXXXX")
trap 'rm -rf $manifests_tmp;' EXIT
echo "-----> Fetching manifests..." >&2
(
	cd $manifests_tmp
	s3cmd --ssl get "${manifests[@]}" 1>&2
)

echo "
WARNING: POTENTIALLY DESTRUCTIVE ACTION!

The following packages will be REMOVED
 from s3://${S3_BUCKET}/${S3_PREFIX}:
$(IFS=$'\n'; echo "${manifests[*]:-(none)}" | xargs -n1 basename | sed -e 's/^/  - /' -e 's/.composer.json$//')
" >&2

if $publish; then
	echo "NOTICE: You have selected to publish the repo after removal of packages." >&2
	echo "This means the repo will be re-generated based on the current bucket contents!" >&2
	regenmsg="& regenerate packages.json"
else
	regenmsg="without updating the repo"
	echo "WARNING: You have selected to NOT publish the repo after removal of packages." >&2
	echo "This means the repo will point to non-existing packages until mkrepo.sh is run!" >&2
fi
echo "" >&2

read -p "Are you sure you want to remove the packages $regenmsg? [yN] " proceed

[[ ! $proceed =~ [yY](es)* ]] && exit

echo "" >&2

remove_files=()
for manifest in "${manifests[@]}"; do
	echo "Removing $(basename $manifest ".composer.json"):" >&2
	if filename=$(cat $manifests_tmp/$(basename $manifest) | python <(cat <<-'PYTHON' # beware of single quotes in body
		import sys, json, re;
		manifest=json.load(sys.stdin)
		url=manifest.get("dist",{}).get("url","").partition("https://"+sys.argv[1]+"."+sys.argv[2]+".amazonaws.com/"+sys.argv[3])
		if url[0]:
		    # dist URL does not match https://${dst_bucket}.${dst_region}.amazonaws.com/${dst_prefix}
		    print(url[0])
		    sys.exit(1)
		else:
		    print(url[2])
		PYTHON
	) $S3_BUCKET ${S3_REGION:-s3} ${S3_PREFIX})
	then
		echo "  - queued '$filename' for removal." >&2
		remove_files+=("$filename")
	else
		# the dist URL points somewhere else, so we are not touching that
		echo "  - WARNING: not removing '$filename' (in manifest 'dist.url')!" >&2
	fi
	echo -n "  - removing manifest file '$(basename $manifest)'... " >&2
	out=$(s3cmd rm ${AWS_ACCESS_KEY_ID+"--access_key=$AWS_ACCESS_KEY_ID"} ${AWS_SECRET_ACCESS_KEY+"--secret_key=$AWS_SECRET_ACCESS_KEY"} --ssl "$manifest" 2>&1) || { echo -e "failed! Error:\n$out" >&2; exit 1; }
	rm $manifests_tmp/$(basename $manifest)
	echo "done." >&2
done

echo "" >&2

if $publish; then
	echo -n "Generating and uploading packages.json... " >&2
	out=$(cd $manifests_tmp; $here/mkrepo.sh --upload 2>&1) || { echo -e "failed! Error:\n$out" >&2; exit 1; }
	cat >&2 <<-EOF
		done!
		$(echo "$out" | grep -E '^Public URL' | sed 's/^Public URL of the object is: http:/Public URL of the repository is: https:/')
		
	EOF
fi

if [[ "${#remove_files[@]}" != "0" ]]; then
	echo "Removing files queued for deletion from bucket:" >&2
	for filename in "${remove_files[@]}"; do
		echo -n "  - removing '$filename'... " >&2
		out=$(s3cmd rm ${AWS_ACCESS_KEY_ID+"--access_key=$AWS_ACCESS_KEY_ID"} ${AWS_SECRET_ACCESS_KEY+"--secret_key=$AWS_SECRET_ACCESS_KEY"} --ssl s3://${S3_BUCKET}/${S3_PREFIX}${filename} 2>&1) && echo "done." >&2 || echo -e "failed! Error:\n$out" >&2
	done
	echo "" >&2
fi

echo "Removal complete.
" >&2

if ! $publish; then
	cat >&2 <<-EOF
		WARNING: repo has not been re-generated. It may currently be in a broken state.
		There may be packages still listed in the repo that have just been removed.
		Run 'mkrepo.sh --upload' at once to return repository into a consistent state.
		
	EOF
fi
