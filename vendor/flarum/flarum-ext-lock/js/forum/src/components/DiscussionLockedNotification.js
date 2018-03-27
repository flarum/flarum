import Notification from 'flarum/components/Notification';

export default class DiscussionLockedNotification extends Notification {
  icon() {
    return 'lock';
  }

  href() {
    const notification = this.props.notification;

    return app.route.discussion(notification.subject(), notification.content().postNumber);
  }

  content() {
    return app.translator.trans('flarum-lock.forum.notifications.discussion_locked_text', {user: this.props.notification.sender()});
  }
}
