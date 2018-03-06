import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('suspend', () => {
  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('suspendUsers', {
      icon: 'ban',
      label: app.translator.trans('flarum-suspend.admin.permissions.suspend_users_label'),
      permission: 'user.suspend'
    });
  });
});
