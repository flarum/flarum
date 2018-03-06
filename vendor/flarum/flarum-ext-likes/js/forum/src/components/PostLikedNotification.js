import Notification from 'flarum/components/Notification';
import username from 'flarum/helpers/username';
import punctuateSeries from 'flarum/helpers/punctuateSeries';

export default class PostLikedNotification extends Notification {
  icon() {
    return 'thumbs-o-up';
  }

  href() {
    return app.route.post(this.props.notification.subject());
  }

  content() {
    const notification = this.props.notification;
    const user = notification.sender();
    const auc = notification.additionalUnreadCount();

    return app.translator.transChoice('flarum-likes.forum.notifications.post_liked_text', auc + 1, {
      user,
      username: auc ? punctuateSeries([
        username(user),
        app.translator.transChoice('flarum-likes.forum.notifications.others_text', auc, {count: auc})
      ]) : undefined
    });
  }

  excerpt() {
    return this.props.notification.subject().contentPlain();
  }
}
