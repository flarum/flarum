import Notification from 'flarum/components/Notification';
import username from 'flarum/helpers/username';

export default class NewPostNotification extends Notification {
  icon() {
    return 'star';
  }

  href() {
    const notification = this.props.notification;
    const discussion = notification.subject();
    const content = notification.content() || {};

    return app.route.discussion(discussion, content.postNumber);
  }

  content() {
    return app.translator.trans('flarum-subscriptions.forum.notifications.new_post_text', {user: this.props.notification.sender()});
  }
}
