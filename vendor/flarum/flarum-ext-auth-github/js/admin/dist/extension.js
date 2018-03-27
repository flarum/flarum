'use strict';

System.register('flarum/auth/github/components/GithubSettingsModal', ['flarum/components/SettingsModal'], function (_export, _context) {
  "use strict";

  var SettingsModal, GithubSettingsModal;
  return {
    setters: [function (_flarumComponentsSettingsModal) {
      SettingsModal = _flarumComponentsSettingsModal.default;
    }],
    execute: function () {
      GithubSettingsModal = function (_SettingsModal) {
        babelHelpers.inherits(GithubSettingsModal, _SettingsModal);

        function GithubSettingsModal() {
          babelHelpers.classCallCheck(this, GithubSettingsModal);
          return babelHelpers.possibleConstructorReturn(this, Object.getPrototypeOf(GithubSettingsModal).apply(this, arguments));
        }

        babelHelpers.createClass(GithubSettingsModal, [{
          key: 'className',
          value: function className() {
            return 'GithubSettingsModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('flarum-auth-github.admin.github_settings.title');
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
                app.translator.trans('flarum-auth-github.admin.github_settings.client_id_label')
              ),
              m('input', { className: 'FormControl', bidi: this.setting('flarum-auth-github.client_id') })
            ), m(
              'div',
              { className: 'Form-group' },
              m(
                'label',
                null,
                app.translator.trans('flarum-auth-github.admin.github_settings.client_secret_label')
              ),
              m('input', { className: 'FormControl', bidi: this.setting('flarum-auth-github.client_secret') })
            )];
          }
        }]);
        return GithubSettingsModal;
      }(SettingsModal);

      _export('default', GithubSettingsModal);
    }
  };
});;
'use strict';

System.register('flarum/auth/github/main', ['flarum/app', 'flarum/auth/github/components/GithubSettingsModal'], function (_export, _context) {
  "use strict";

  var app, GithubSettingsModal;
  return {
    setters: [function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumAuthGithubComponentsGithubSettingsModal) {
      GithubSettingsModal = _flarumAuthGithubComponentsGithubSettingsModal.default;
    }],
    execute: function () {

      app.initializers.add('flarum-auth-github', function () {
        app.extensionSettings['flarum-auth-github'] = function () {
          return app.modal.show(new GithubSettingsModal());
        };
      });
    }
  };
});