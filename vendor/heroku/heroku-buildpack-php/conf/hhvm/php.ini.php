; php options
date.timezone = UTC
expose_php = off
 
; hhvm specific 
hhvm.log.level = Warning
hhvm.log.always_log_unhandled_exceptions = true
hhvm.log.runtime_error_reporting_level = 8191
hhvm.mysql.typed_results = false

memory_limit = 128M

; hhvm fcgi
hhvm.server.type = fastcgi
hhvm.server.file_socket = /tmp/heroku.fcgi.<?=getenv('PORT')?>.sock
hhvm.server.thread_count = <?=getenv('WEB_CONCURRENCY')?:8?>
