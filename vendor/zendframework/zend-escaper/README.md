# zend-escaper

[![Build Status](https://secure.travis-ci.org/zendframework/zend-escaper.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-escaper)
[![Coverage Status](https://coveralls.io/repos/zendframework/zend-escaper/badge.svg?branch=master)](https://coveralls.io/r/zendframework/zend-escaper?branch=master)

The OWASP Top 10 web security risks study lists Cross-Site Scripting (XSS) in
second place. PHP’s sole functionality against XSS is limited to two functions
of which one is commonly misapplied. Thus, the zend-escaper component was written.
It offers developers a way to escape output and defend from XSS and related
vulnerabilities by introducing contextual escaping based on peer-reviewed rules.

- File issues at https://github.com/zendframework/zend-escaper/issues
- Documentation is at https://zendframework.github.io/zend-escaper/
