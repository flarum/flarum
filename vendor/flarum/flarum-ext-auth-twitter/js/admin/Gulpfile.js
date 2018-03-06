var gulp = require('flarum-gulp');

gulp({
  modules: {
    'flarum/auth/twitter': 'src/**/*.js'
  }
});
