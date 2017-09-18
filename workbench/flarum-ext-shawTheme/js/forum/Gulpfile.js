var flarum = require('flarum-gulp');

flarum({
    modules: {
        'romanzpolski/shawTheme': [
            'src/**/*.js'
        ]
    }
});