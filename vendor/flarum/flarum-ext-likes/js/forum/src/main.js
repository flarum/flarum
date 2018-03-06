import { extend } from 'flarum/extend';
import app from 'flarum/app';
import Post from 'flarum/models/Post';
import Model from 'flarum/Model';
import NotificationGrid from 'flarum/components/NotificationGrid';

import addLikeAction from 'flarum/likes/addLikeAction';
import addLikesList from 'flarum/likes/addLikesList';
import PostLikedNotification from 'flarum/likes/components/PostLikedNotification';

app.initializers.add('flarum-likes', () => {
  app.notificationComponents.postLiked = PostLikedNotification;

  Post.prototype.canLike = Model.attribute('canLike');
  Post.prototype.likes = Model.hasMany('likes');

  addLikeAction();
  addLikesList();

  extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
    items.add('postLiked', {
      name: 'postLiked',
      icon: 'thumbs-o-up',
      label: app.translator.trans('flarum-likes.forum.settings.notify_post_liked_label')
    });
  });
});
