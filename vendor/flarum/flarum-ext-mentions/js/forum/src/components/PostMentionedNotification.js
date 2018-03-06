import Notification from 'flarum/components/Notification';
import username from 'flarum/helpers/username';
import punctuateSeries from 'flarum/helpers/punctuateSeries';

export default class PostMentionedNotification extends Notification {
  icon() {
    return 'reply';
  }

  href() {
    const notification = this.props.notification;
    const post = notification.subject();
    const auc = notification.additionalUnreadCount();
    const content = notification.content();

    return app.route.discussion(post.discussion(), auc ? post.number() : (content && content.replyNumber));
  }

  content() {
    const notification = this.props.notification;
    const auc = notification.additionalUnreadCount();
    const user = notification.sender();

    return app.translator.transChoice('flarum-mentions.forum.notifications.post_mentioned_text', auc + 1, {
      user,
      username: auc ? punctuateSeries([
        username(user),
        app.translator.transChoice('flarum-mentions.forum.notifications.others_text', auc, {count: auc})
      ]) : undefined
    });
  }

  excerpt() {
    return this.props.notification.subject().contentPlain();
  }
}
