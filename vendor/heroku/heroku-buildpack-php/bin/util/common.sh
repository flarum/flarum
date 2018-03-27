error() {
  echo
  echo " !     ERROR: $*" | indent no_first_line_indent
  echo
  exit 1
}

warning() {
  echo
  echo " !     WARNING: $*" | indent no_first_line_indent
  echo
}

warning_inline() {
  echo " !     WARNING: $*" | indent no_first_line_indent
}

status() {
  echo "-----> $*"
}

notice() {
  echo
  echo "NOTICE: $*" | indent
  echo
}

notice_inline() {
  echo "NOTICE: $*" | indent
}

# sed -l basically makes sed replace and buffer through stdin to stdout
# so you get updates while the command runs and dont wait for the end
# e.g. npm install | indent
indent() {
  # if an arg is given it's a flag indicating we shouldn't indent the first line, so use :+ to tell SED accordingly if that parameter is set, otherwise null string for no range selector prefix (it selects from line 2 onwards and then every 1st line, meaning all lines)
  local c="${1:+"2,999"} s/^/       /"
  case $(uname) in
    Darwin) sed -l "$c";; # mac/bsd sed: -l buffers on line boundaries
    *)      sed -u "$c";; # unix/gnu sed: -u unbuffered (arbitrary) chunks of data
  esac
}

export_env_dir() {
  local env_dir=$1
  local whitelist_regex=${2:-''}
  local blacklist_regex=${3:-'^(PATH|GIT_DIR|CPATH|CPPATH|LD_PRELOAD|LIBRARY_PATH|IFS)$'}
  if [ -d "$env_dir" ]; then
    for e in $(ls $env_dir); do
      echo "$e" | grep -E "$whitelist_regex" | grep -qvE "$blacklist_regex" &&
      export "$e=$(cat $env_dir/$e)"
      :
    done
  fi
}

curl_retry_on_18() {
  local ec=18;
  local attempts=0;
  while [[ $ec -eq 18 && $attempts -lt 3 ]]; do
    ((attempts++))
    curl "$@" # -C - would return code 33 if unsupported by server
    ec=$?
  done
  return $ec
}
