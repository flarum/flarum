'use strict';

System.register('flarum/flags/components/FlagsSettingsModal', ['flarum/components/SettingsModal'], function (_export, _context) {
  "use strict";

  var SettingsModal, FlagsSettingsModal;
  return {
    setters: [function (_flarumComponentsSettingsModal) {
      SettingsModal = _flarumComponentsSettingsModal.default;
    }],
    execute: function () {
      FlagsSettingsModal = function (_SettingsModal) {
        babelHelpers.inherits(FlagsSettingsModal, _SettingsModal);

        function FlagsSettingsModal() {
          babelHelpers.classCallCheck(this, FlagsSettingsModal);
          return babelHelpers.possibleConstructorReturn(this, (FlagsSettingsModal.__proto__ || Object.getPrototypeOf(FlagsSettingsModal)).apply(this, arguments));
        }

        babelHelpers.createClass(FlagsSettingsModal, [{
          key: 'className',
          value: function className() {
            return 'FlagsSettingsModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('flarum-flags.admin.settings.title');
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
                app.translator.trans('flarum-flags.admin.settings.guidelines_url_label')
              ),
              m('input', { className: 'FormControl', bidi: this.setting('flarum-flags.guidelines_url') })
            )];
          }
        }]);
        return FlagsSettingsModal;
      }(SettingsModal);

      _export('default', FlagsSettingsModal);
    }
  };
});;
'use strict';

System.register('flarum/flags/main', ['flarum/extend', 'flarum/app', 'flarum/components/PermissionGrid', 'flarum/flags/components/FlagsSettingsModal'], function (_export, _context) {
  "use strict";

  var extend, app, PermissionGrid, FlagsSettingsModal;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumComponentsPermissionGrid) {
      PermissionGrid = _flarumComponentsPermissionGrid.default;
    }, function (_flarumFlagsComponentsFlagsSettingsModal) {
      FlagsSettingsModal = _flarumFlagsComponentsFlagsSettingsModal.default;
    }],
    execute: function () {

      app.initializers.add('flarum-flags', function () {
        app.extensionSettings['flarum-flags'] = function () {
          return app.modal.show(new FlagsSettingsModal());
        };

        extend(PermissionGrid.prototype, 'moderateItems', function (items) {
          items.add('viewFlags', {
            icon: 'flag',
            label: app.translator.trans('flarum-flags.admin.permissions.view_flags_label'),
            permission: 'discussion.viewFlags'
          }, 65);
        });

        extend(PermissionGrid.prototype, 'replyItems', function (items) {
          items.add('flagPosts', {
            icon: 'flag',
            label: app.translator.trans('flarum-flags.admin.permissions.flag_posts_label'),
            permission: 'discussion.flagPosts'
          }, 70);
        });
      });
    }
  };
});