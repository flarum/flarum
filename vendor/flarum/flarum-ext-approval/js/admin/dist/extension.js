'use strict';

System.register('flarum/approval/main', ['flarum/extend', 'flarum/app', 'flarum/components/PermissionGrid'], function (_export, _context) {
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

      app.initializers.add('approval', function () {
        extend(app, 'getRequiredPermissions', function (required, permission) {
          if (permission === 'discussion.startWithoutApproval') {
            required.push('startDiscussion');
          }
          if (permission === 'discussion.replyWithoutApproval') {
            required.push('discussion.reply');
          }
        });

        extend(PermissionGrid.prototype, 'startItems', function (items) {
          items.add('startDiscussionsWithoutApproval', {
            icon: 'check',
            label: app.translator.trans('flarum-approval.admin.permissions.start_discussions_without_approval_label'),
            permission: 'discussion.startWithoutApproval'
          }, 95);
        });

        extend(PermissionGrid.prototype, 'replyItems', function (items) {
          items.add('replyWithoutApproval', {
            icon: 'check',
            label: app.translator.trans('flarum-approval.admin.permissions.reply_without_approval_label'),
            permission: 'discussion.replyWithoutApproval'
          }, 95);
        });

        extend(PermissionGrid.prototype, 'moderateItems', function (items) {
          items.add('approvePosts', {
            icon: 'check',
            label: app.translator.trans('flarum-approval.admin.permissions.approve_posts_label'),
            permission: 'discussion.approvePosts'
          }, 65);
        });
      });
    }
  };
});