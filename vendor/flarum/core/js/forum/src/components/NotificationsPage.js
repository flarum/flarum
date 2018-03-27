import Page from 'flarum/components/Page';
import NotificationList from 'flarum/components/NotificationList';

/**
 * The `NotificationsPage` component shows the notifications list. It is only
 * used on mobile devices where the notifications dropdown is within the drawer.
 */
export default class NotificationsPage extends Page {
  init() {
    super.init();

    app.history.push('notifications');

    this.list = new NotificationList();
    this.list.load();

    this.bodyClass = 'App--notifications';
  }

  view() {
    return <div className="NotificationsPage">{this.list.render()}</div>;
  }
}
