import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('flarum-likes', () => {
  extend(PermissionGrid.prototype, 'replyItems', items => {
    items.add('likePosts', {
      icon: 'thumbs-o-up',
      label: app.translator.trans('flarum-likes.admin.permissions.like_posts_label'),
      permission: 'discussion.likePosts'
    });
  });
});
