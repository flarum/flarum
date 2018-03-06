# 1.7.0.13
 - [All Changes](https://github.com/Asenar/less.php/compare/v1.7.0.12...v1.7.0.13)
 - Fix composer.json (PSR-4 was invalid)

# 1.7.0.12
 - [All Changes](https://github.com/Asenar/less.php/compare/v1.7.0.11...v1.7.0.12)
 - set bin/lessc bit executable
 - Add 'gettingVariables' method in Less_Parser

# 1.7.0.11
 - [All Changes](https://github.com/Asenar/less.php/compare/v1.7.0.10...v1.7.0.11)
 - Fix realpath issue (windows)
 - Set Less_Tree_Call property back to public ( Fix 258 266 267 issues from oyejorge/less.php)

# 1.7.0.10

 - [All Changes](https://github.com/oyejorge/less.php/compare/v1.7.0.9...v1.7.10)
 - Add indentation option
 - Add 'optional' modifier for @import
 - fix $color in Exception messages
 - don't use set_time_limit when running cli
 - take relative-url into account when building the cache filename
 - urlArgs should be string no array()
 - add bug-report fixtures [#6dc898f](https://github.com/oyejorge/less.php/commit/6dc898f5d75b447464906bdf19d79c2e19d95e33)
 - fix #269, missing on NameValue type [#a8dac63](https://github.com/oyejorge/less.php/commit/a8dac63d93fb941c54fb78b12588abf635747c1b)

# 1.7.0.9

 - [All Changes](https://github.com/oyejorge/less.php/compare/v1.7.0.8...v1.7.0.9)
 - Remove space at beginning of Version.php
 - Revert require() paths in test interface
