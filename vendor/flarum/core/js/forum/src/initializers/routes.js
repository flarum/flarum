import IndexPage from 'flarum/components/IndexPage';
import DiscussionPage from 'flarum/components/DiscussionPage';
import PostsUserPage from 'flarum/components/PostsUserPage';
import DiscussionsUserPage from 'flarum/components/DiscussionsUserPage';
import SettingsPage from 'flarum/components/SettingsPage';
import NotificationsPage from 'flarum/components/NotificationsPage';

/**
 * The `routes` initializer defines the forum app's routes.
 *
 * @param {App} app
 */
export default function(app) {
  app.routes = {
    'index': {path: '/all', component: IndexPage.component()},
    'index.filter': {path: '/:filter', component: IndexPage.component()},

    'discussion': {path: '/d/:id', component: DiscussionPage.component()},
    'discussion.near': {path: '/d/:id/:near', component: DiscussionPage.component()},

    'user': {path: '/u/:username', component: PostsUserPage.component()},
    'user.posts': {path: '/u/:username', component: PostsUserPage.component()},
    'user.discussions': {path: '/u/:username/discussions', component: DiscussionsUserPage.component()},

    'settings': {path: '/settings', component: SettingsPage.component()},
    'notifications': {path: '/notifications', component: NotificationsPage.component()}
  };

  /**
   * Generate a URL to a discussion.
   *
   * @param {Discussion} discussion
   * @param {Integer} [near]
   * @return {String}
   */
  app.route.discussion = (discussion, near) => {
    return app.route(near && near !== 1 ? 'discussion.near' : 'discussion', {
      id: discussion.id() + '-' + discussion.slug(),
      near: near && near !== 1 ? near : undefined
    });
  };

  /**
   * Generate a URL to a post.
   *
   * @param {Post} post
   * @return {String}
   */
  app.route.post = post => {
    return app.route.discussion(post.discussion(), post.number());
  };

  /**
   * Generate a URL to a user.
   *
   * @param {User} user
   * @return {String}
   */
  app.route.user = user => {
    return app.route('user', {
      username: user.username()
    });
  };
}
