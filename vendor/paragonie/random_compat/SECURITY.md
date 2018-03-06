# An Invitation to Security Researchers

Every company says they take security "very seriously." Rather than bore anyone 
with banal boilerplate, here are some quick answers followed by detailed
elaboration. If you have any questions about our policies, please email them to
`scott@paragonie.com`.

## Quick Answers

* There is no compulsion to disclose vulnerabilities privately, but we 
  appreciate a head's up.
* `security@paragonie.com` will get your reports to the right person. Our GPG 
  fingerprint, should you decide to encrypt your report, is 
  `7F52 D5C6 1D12 55C7 3136  2E82 6B97 A1C2 8264 04DA`.

* **YES**, we will reward security researchers who disclose vulnerabilities in
  our software.
* In most cases, **No Proof-of-Concept Required.**

## How to Report a Security Bug to Paragon Initiative Enterprises

### There is no compulsion to disclose privately.

We believe vulnerability disclosure style is a personal choice and enjoy working
with a diverse community. We understand and appreciate the importance of Full 
Disclosure in the history and practice of security research.

We would *like* to know about high-severity bugs before they become public
knowledge, so we can fix them in a timely manner, but **we do not believe in 
threatening researchers or trying to enforce vulnerability embargoes**.

Ultimately, if you discover a security-affecting vulnerability, what you do with
it is your choice. We would like to work with people, and to celebrate and 
reward their skill, experience, and dedication. We appreciate being informed of
our mistakes so we can learn from them and build a better product. Our goal is
to empower the community.

### Where to Send Security Vulnerabilities

Our security email address is `security@paragonie.com`. Also feel free to open a
new issue on Github if you want to disclose publicly.

```
-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: GnuPG

mQENBFUgwRUBCADcIpqNwyYc5UmY/tpx1sF/rQ3knR1YNXYZThzFV+Gmqhp1fDH5
qBs9foh1xwI6O7knWmQngnf/nBumI3x6xj7PuOdEZUh2FwCG/VWnglW8rKmoHzHA
ivjiu9SLnPIPAgHSHeh2XD7q3Ndm3nenbjAiRFNl2iXcwA2cTQp9Mmfw9vVcw0G0
z1o0G3s8cC8ZS6flFySIervvfSRWj7A1acI5eE3+AH/qXJRdEJ+9J8OB65p1JMfk
6+fWgOB1XZxMpz70S0rW6IX38WDSRhEK2fXyZJAJjyt+YGuzjZySNSoQR/V6vNYn
syrNPCJ2i5CgZQxAkyBBcr7koV9RIhPRzct/ABEBAAG0IVNlY3VyaXR5IDxzZWN1
cml0eUBwYXJhZ29uaWUuY29tPokBOQQTAQIAIwUCVSDBFQIbAwcLCQgHAwIBBhUI
AgkKCwQWAgMBAh4BAheAAAoJEGuXocKCZATat2YIAIoejNFEQ2c1iaOEtSuB7Pn/
WLbsDsHNLDKOV+UnfaCjv/vL7D+5NMChFCi2frde/NQb2TsjqmIH+V+XbnJtlrXD
Vj7yvMVal+Jqjwj7v4eOEWcKVcFZk+9cfUgh7t92T2BMX58RpgZF0IQZ6Z1R3FfC
9Ub4X6ykW+te1q0/4CoRycniwmlQi6iGSr99LQ5pfJq2Qlmz/luTZ0UX0h575T7d
cp2T1sX/zFRk/fHeANWSksipdDBjAXR7NMnYZgw2HghEdFk/xRDY7K1NRWNZBf05
WrMHmh6AIVJiWZvI175URxEe268hh+wThBhXQHMhFNJM1qPIuzb4WogxM3UUD7m5
AQ0EVSDBFQEIALNkpzSuJsHAHh79sc0AYWztdUe2MzyofQbbOnOCpWZebYsC3EXU
335fIg59k0m6f+O7GmEZzzIv5v0i99GS1R8CJm6FvhGqtH8ZqmOGbc71WdJSiNVE
0kpQoJlVzRbig6ZyyjzrggbM1eh5OXOk5pw4+23FFEdw7JWU0HJS2o71r1hwp05Z
vy21kcUEobz/WWQQyGS0Neo7PJn+9KS6wOxXul/UE0jct/5f7KLMdWMJ1VgniQmm
hjvkHLPSICteqCI04RfcmMseW9gueHQXeUu1SNIvsWa2MhxjeBej3pDnrZWszKwy
gF45GO9/v4tkIXNMy5J1AtOyRgQ3IUMqp8EAEQEAAYkBHwQYAQIACQUCVSDBFQIb
DAAKCRBrl6HCgmQE2jnIB/4/xFz8InpM7eybnBOAir3uGcYfs3DOmaKn7qWVtGzv
rKpQPYnVtlU2i6Z5UO4c4jDLT/8Xm1UDz3Lxvqt4xCaDwJvBZexU5BMK8l5DvOzH
6o6P2L1UDu6BvmPXpVZz7/qUhOnyf8VQg/dAtYF4/ax19giNUpI5j5o5mX5w80Rx
qSXV9NdSL4fdjeG1g/xXv2luhoV53T1bsycI3wjk/x5tV+M2KVhZBvvuOm/zhJje
oLWp0saaESkGXIXqurj6gZoujJvSvzl0n9F9VwqMEizDUfrXgtD1siQGhP0sVC6q
ha+F/SAEJ0jEquM4TfKWWU2S5V5vgPPpIQSYRnhQW4b1
=xJPW
-----END PGP PUBLIC KEY BLOCK-----
```

### We Will Reward Security Researchers

**This process has not been formalized; nor have dollar amounts been 
discussed.**

However, if you report a valid security-affecting bug, we will compensate you
for the time spent finding the vulnerability and reward you for being a good
neighbor.

#### What does a "valid" bug mean?

There are two sides to this:

1. Some have spammed projects with invalid bug reports hoping to collect
   bounties for pressing a button and running an automated analysis tool. This
   is not cool.
2. There is a potential for the developers of a project to declare all security
   bug reports as invalid to save money.

Our team members have an established history of reporting vulnerabilities to
large open source projects. **We aren't in the business of ripping people off.**
When in doubt, our policy is to err on the side of generosity.

### No Proof-of-Concept Required

We might ask for one if we feel we do not understand some of the details 
pertaining to a specific vulnerability. We certainly appreciate them if you 
include them in your report, but we believe **the burden lies with the developer
to prove their software *is* secure** rather than with the researcher to prove
that it isn't.

In our experience, most bugs are simpler to fix than they are to exploit.

