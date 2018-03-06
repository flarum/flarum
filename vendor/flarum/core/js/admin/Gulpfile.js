var gulp = require('flarum-gulp');

var bowerDir = '../bower_components';

gulp({
  includeHelpers: true,
  files: [
    bowerDir + '/es6-micro-loader/dist/system-polyfill.js',

    bowerDir + '/mithril/mithril.js',
    bowerDir + '/m.attrs.bidi/bidi.js',
    bowerDir + '/jquery/dist/jquery.js',
    bowerDir + '/moment/moment.js',

    bowerDir + '/bootstrap/js/affix.js',
    bowerDir + '/bootstrap/js/dropdown.js',
    bowerDir + '/bootstrap/js/modal.js',
    bowerDir + '/bootstrap/js/tooltip.js',
    bowerDir + '/bootstrap/js/transition.js',

    bowerDir + '/spin.js/spin.js',
    bowerDir + '/spin.js/jquery.spin.js'
  ],
  modules: {
    'flarum': [
      'src/**/*.js',
      '../lib/**/*.js'
    ]
  },
  outputFile: 'dist/app.js'
});
