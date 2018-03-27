import Store from 'flarum/Store';
import Forum from 'flarum/models/Forum';
import User from 'flarum/models/User';
import Discussion from 'flarum/models/Discussion';
import Post from 'flarum/models/Post';
import Group from 'flarum/models/Group';
import Activity from 'flarum/models/Activity';
import Notification from 'flarum/models/Notification';

/**
 * The `store` initializer creates the application's data store and registers
 * the default resource types to their models.
 *
 * @param {App} app
 */
export default function store(app) {
  app.store = new Store({
    forums: Forum,
    users: User,
    discussions: Discussion,
    posts: Post,
    groups: Group,
    activity: Activity,
    notifications: Notification
  });
}
