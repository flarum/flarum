import { extend } from 'flarum/extend';
import PermissionGrid from 'flarum/components/PermissionGrid';

export default function() {
  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('tag', {
      icon: 'tag',
      label: app.translator.trans('flarum-tags.admin.permissions.tag_discussions_label'),
      permission: 'discussion.tag'
    }, 95);
  });
}
