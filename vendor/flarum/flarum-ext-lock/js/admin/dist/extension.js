'use strict';

System.register('flarum/lock/main', ['flarum/extend', 'flarum/app', 'flarum/components/PermissionGrid'], function (_export, _context) {
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

      app.initializers.add('lock', function () {
        extend(PermissionGrid.prototype, 'moderateItems', function (items) {
          items.add('lock', {
            icon: 'lock',
            label: app.translator.trans('flarum-lock.admin.permissions.lock_discussions_label'),
            permission: 'discussion.lock'
          }, 95);
        });
      });
    }
  };
});