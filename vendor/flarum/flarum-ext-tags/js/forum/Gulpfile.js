var gulp = require('flarum-gulp');

gulp({
  modules: {
    'flarum/tags': [
      '../lib/**/*.js',
      'src/**/*.js'
    ]
  }
});
