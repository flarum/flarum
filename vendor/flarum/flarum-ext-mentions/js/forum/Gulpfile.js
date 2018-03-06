var gulp = require('flarum-gulp');

gulp({
  modules: {
    'flarum/mentions': 'src/**/*.js'
  },
  files: [
    'node_modules/textarea-caret/index.js'
  ]
});
