'use strict';

System.register('flarum/suspend/main', ['flarum/extend', 'flarum/app', 'flarum/components/PermissionGrid'], function (_export, _context) {
  "use strict";

  var extend, app, PermissionGrid;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumComponentsPermissionGrid) {
      PermissionGrid = _flarumComponentsPermissionGrid.default;
    }],
    execute: function () {

      app.initializers.add('suspend', function () {
        extend(PermissionGrid.prototype, 'moderateItems', function (items) {
          items.add('suspendUsers', {
            icon: 'ban',
            label: app.translator.trans('flarum-suspend.admin.permissions.suspend_users_label'),
            permission: 'user.suspend'
          });
        });
      });
    }
  };
});