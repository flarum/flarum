'use strict';

System.register('flarum/akismet/components/AkismetSettingsModal', ['flarum/components/SettingsModal'], function (_export, _context) {
  "use strict";

  var SettingsModal, AkismetSettingsModal;
  return {
    setters: [function (_flarumComponentsSettingsModal) {
      SettingsModal = _flarumComponentsSettingsModal.default;
    }],
    execute: function () {
      AkismetSettingsModal = function (_SettingsModal) {
        babelHelpers.inherits(AkismetSettingsModal, _SettingsModal);

        function AkismetSettingsModal() {
          babelHelpers.classCallCheck(this, AkismetSettingsModal);
          return babelHelpers.possibleConstructorReturn(this, Object.getPrototypeOf(AkismetSettingsModal).apply(this, arguments));
        }

        babelHelpers.createClass(AkismetSettingsModal, [{
          key: 'className',
          value: function className() {
            return 'AkismetSettingsModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('flarum-akismet.admin.akismet_settings.title');
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
                app.translator.trans('flarum-akismet.admin.akismet_settings.api_key_label')
              ),
              m('input', { className: 'FormControl', bidi: this.setting('flarum-akismet.api_key') })
            )];
          }
        }]);
        return AkismetSettingsModal;
      }(SettingsModal);

      _export('default', AkismetSettingsModal);
    }
  };
});;
'use strict';

System.register('flarum/akismet/main', ['flarum/app', 'flarum/akismet/components/AkismetSettingsModal'], function (_export, _context) {
  "use strict";

  var app, AkismetSettingsModal;
  return {
    setters: [function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumAkismetComponentsAkismetSettingsModal) {
      AkismetSettingsModal = _flarumAkismetComponentsAkismetSettingsModal.default;
    }],
    execute: function () {

      app.initializers.add('flarum-akismet', function () {
        app.extensionSettings['flarum-akismet'] = function () {
          return app.modal.show(new AkismetSettingsModal());
        };
      });
    }
  };
});