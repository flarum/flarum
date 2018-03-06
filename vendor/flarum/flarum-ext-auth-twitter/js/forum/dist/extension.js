'use strict';

System.register('flarum/auth/twitter/main', ['flarum/extend', 'flarum/app', 'flarum/components/LogInButtons', 'flarum/components/LogInButton'], function (_export, _context) {
  "use strict";

  var extend, app, LogInButtons, LogInButton;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumComponentsLogInButtons) {
      LogInButtons = _flarumComponentsLogInButtons.default;
    }, function (_flarumComponentsLogInButton) {
      LogInButton = _flarumComponentsLogInButton.default;
    }],
    execute: function () {

      app.initializers.add('flarum-auth-twitter', function () {
        extend(LogInButtons.prototype, 'items', function (items) {
          items.add('twitter', m(
            LogInButton,
            {
              className: 'Button LogInButton--twitter',
              icon: 'twitter',
              path: '/auth/twitter' },
            app.translator.trans('flarum-auth-twitter.forum.log_in.with_twitter_button')
          ));
        });
      });
    }
  };
});