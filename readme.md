# Flarum

**Delightfully simple forums.** [Flarum](http://flarum.org) is the next-generation forum software that makes online discussion fun again.

[Live Demo](http://demo.flarum.org) -
[Development Forum](http://discuss.flarum.org) -
[Twitter](http://twitter.com/flarum) -
[Contact](mailto:toby@flarum.org) -
[Donate](http://flarum.org/donate)

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/flarum/flarum)

## Goals

- **Fast and simple.** No clutter, no bloat, no complex dependencies. Flarum is built with PHP so it's quick and easy to deploy. The interface is powered by Mithril, a highly performant JavaScript framework with a tiny footprint.
- **Beautiful and responsive.** This is forum software for humans. Flarum is carefully designed to be consistent and intuitive across platforms, out-of-the-box. It's backed by LESS, so themeing is a cinch.
- **Powerful and extensible.** Customize, extend, and integrate Flarum to suit your community. Flarum's architecture is amazingly flexible, prioritizing comprehensive APIs and great documentation.
- **Free and open.** Flarum is released under the [MIT license](https://github.com/flarum/flarum/blob/master/LICENSE.txt).

## Installation

**Flarum is currently in development and will be ready to use later this year.** ([Roadmap](http://tobyzerner.com/flarum/)) If you want to give the development version a spin or are interested in contributing, for now you can install Flarum's Vagrant image. An easier installation process will become a priority once Flarum is more stable.

1. Install [Vagrant](https://www.vagrantup.com) and [VirtualBox](https://www.virtualbox.org).
2. Clone this repository and set up the Vagrant box:

  ```sh
  git clone --recursive https://github.com/flarum/flarum.git
  cd flarum
  vagrant up
  ```

3. Add an entry to your /etc/hosts file:

  ```192.168.29.29 flarum.dev```

4. Visit flarum.dev in a browser.

## Contributing

Interested in contributing to Flarum? Read the [Contribution Guide](https://github.com/flarum/flarum/blob/master/CONTRIBUTING.md)!

Please note that bug reports should go in [flarum/core](https://github.com/flarum/core/issues) or the [relevant extension repository](https://github.com/flarum).

### Core Team

- Toby Zerner ([esoTalk](http://esotalk.org), [@tobscure](http://twitter.com/tobscure))
- Franz Liedke ([FluxBB](http://fluxbb.org), [@franzliedke](http://twitter.com/franzliedke))
