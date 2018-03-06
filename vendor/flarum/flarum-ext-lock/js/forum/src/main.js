import { extend } from 'flarum/extend';
import app from 'flarum/app';
import Model from 'flarum/Model';
import Discussion from 'flarum/models/Discussion';
import NotificationGrid from 'flarum/components/NotificationGrid';

import DiscussionLockedPost from 'flarum/lock/components/DiscussionLockedPost';
import DiscussionLockedNotification from 'flarum/lock/components/DiscussionLockedNotification';
import addLockBadge from 'flarum/lock/addLockBadge';
import addLockControl from 'flarum/lock/addLockControl';

app.initializers.add('flarum-lock', () => {
  app.postComponents.discussionLocked = DiscussionLockedPost;
  app.notificationComponents.discussionLocked = DiscussionLockedNotification;

  Discussion.prototype.isLocked = Model.attribute('isLocked');
  Discussion.prototype.canLock = Model.attribute('canLock');

  addLockBadge();
  addLockControl();

  extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
    items.add('discussionLocked', {
      name: 'discussionLocked',
      icon: 'lock',
      label: app.translator.trans('flarum-lock.forum.settings.notify_discussion_locked_label')
    });
  });
});
