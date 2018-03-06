var gulp = require('flarum-gulp');

gulp({
  modules: {
    'flarum/auth/facebook': 'src/**/*.js'
  }
});
