'use strict';

System.register('flarum/auth/twitter/components/TwitterSettingsModal', ['flarum/components/SettingsModal'], function (_export, _context) {
  "use strict";

  var SettingsModal, TwitterSettingsModal;
  return {
    setters: [function (_flarumComponentsSettingsModal) {
      SettingsModal = _flarumComponentsSettingsModal.default;
    }],
    execute: function () {
      TwitterSettingsModal = function (_SettingsModal) {
        babelHelpers.inherits(TwitterSettingsModal, _SettingsModal);

        function TwitterSettingsModal() {
          babelHelpers.classCallCheck(this, TwitterSettingsModal);
          return babelHelpers.possibleConstructorReturn(this, Object.getPrototypeOf(TwitterSettingsModal).apply(this, arguments));
        }

        babelHelpers.createClass(TwitterSettingsModal, [{
          key: 'className',
          value: function className() {
            return 'TwitterSettingsModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('flarum-auth-twitter.admin.twitter_settings.title');
          }
        }, {
          key: 'form',
          value: function form() {
            return [m(
              'div',
              { className: 'Form-group' },
              m(
                'label',
                null,
                app.translator.trans('flarum-auth-twitter.admin.twitter_settings.api_key_label')
              ),
              m('input', { className: 'FormControl', bidi: this.setting('flarum-auth-twitter.api_key') })
            ), m(
              'div',
              { className: 'Form-group' },
              m(
                'label',
                null,
                app.translator.trans('flarum-auth-twitter.admin.twitter_settings.api_secret_label')
              ),
              m('input', { className: 'FormControl', bidi: this.setting('flarum-auth-twitter.api_secret') })
            )];
          }
        }]);
        return TwitterSettingsModal;
      }(SettingsModal);

      _export('default', TwitterSettingsModal);
    }
  };
});;
'use strict';

System.register('flarum/auth/twitter/main', ['flarum/app', 'flarum/auth/twitter/components/TwitterSettingsModal'], function (_export, _context) {
  "use strict";

  var app, TwitterSettingsModal;
  return {
    setters: [function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumAuthTwitterComponentsTwitterSettingsModal) {
      TwitterSettingsModal = _flarumAuthTwitterComponentsTwitterSettingsModal.default;
    }],
    execute: function () {

      app.initializers.add('flarum-auth-twitter', function () {
        app.extensionSettings['flarum-auth-twitter'] = function () {
          return app.modal.show(new TwitterSettingsModal());
        };
      });
    }
  };
});