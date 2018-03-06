var gulp = require('flarum-gulp');

gulp({
  modules: {
    'flarum/auth/github': 'src/**/*.js'
  }
});
