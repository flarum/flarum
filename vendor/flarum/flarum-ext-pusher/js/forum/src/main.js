/*global Pusher*/

import { extend } from 'flarum/extend';
import app from 'flarum/app';
import DiscussionList from 'flarum/components/DiscussionList';
import DiscussionPage from 'flarum/components/DiscussionPage';
import IndexPage from 'flarum/components/IndexPage';
import Button from 'flarum/components/Button';

app.initializers.add('flarum-pusher', () => {
  const loadPusher = m.deferred();

  $.getScript('//js.pusher.com/3.0/pusher.min.js', () => {
    const socket = new Pusher(app.forum.attribute('pusherKey'), {
      authEndpoint: app.forum.attribute('apiUrl') + '/pusher/auth',
      cluster: app.forum.attribute('pusherCluster'),
      auth: {
        headers: {
          'X-CSRF-Token': app.session.csrfToken
        }
      }
    });

    loadPusher.resolve({
      main: socket.subscribe('public'),
      user: app.session.user ? socket.subscribe('private-user' + app.session.user.id()) : null
    });
  });

  app.pusher = loadPusher.promise;
  app.pushedUpdates = [];

  extend(DiscussionList.prototype, 'config', function(x, isInitialized, context) {
    if (isInitialized) return;

    app.pusher.then(channels => {
      channels.main.bind('newPost', data => {
        const params = this.props.params;

        if (!params.q && !params.sort && !params.filter) {
          if (params.tags) {
            const tag = app.store.getBy('tags', 'slug', params.tags);

            if (data.tagIds.indexOf(tag.id()) === -1) return;
          }

          const id = String(data.discussionId);

          if ((!app.current.discussion || id !== app.current.discussion.id()) && app.pushedUpdates.indexOf(id) === -1) {
            app.pushedUpdates.push(id);

            if (app.current instanceof IndexPage) {
              app.setTitleCount(app.pushedUpdates.length);
            }

            m.redraw();
          }
        }
      });

      extend(context, 'onunload', () => channels.main.unbind('newPost'));
    });
  });

  extend(DiscussionList.prototype, 'view', function(vdom) {
    if (app.pushedUpdates) {
      const count = app.pushedUpdates.length;

      if (count) {
        vdom.children.unshift(
          Button.component({
            className: 'Button Button--block DiscussionList-update',
            onclick: () => {
              this.refresh(false).then(() => {
                this.loadingUpdated = false;
                app.pushedUpdates = [];
                app.setTitleCount(0);
                m.redraw();
              });
              this.loadingUpdated = true;
            },
            loading: this.loadingUpdated,
            children: app.translator.transChoice('flarum-pusher.forum.discussion_list.show_updates_text', count, {count})
          })
        );
      }
    }
  });

  // Prevent any newly-created discussions from triggering the discussion list
  // update button showing.
  // TODO: Might be better pause the response to the push updates while the
  // composer is loading? idk
  extend(DiscussionList.prototype, 'addDiscussion', function(returned, discussion) {
    const index = app.pushedUpdates.indexOf(discussion.id());

    if (index !== -1) {
      app.pushedUpdates.splice(index, 1);
    }

    if (app.current instanceof IndexPage) {
      app.setTitleCount(app.pushedUpdates.length);
    }

    m.redraw();
  });

  extend(DiscussionPage.prototype, 'config', function(x, isInitialized, context) {
    if (isInitialized) return;

    app.pusher.then(channels => {
      channels.main.bind('newPost', data => {
        const id = String(data.discussionId);

        if (this.discussion && this.discussion.id() === id && this.stream) {
          const oldCount = this.discussion.commentsCount();

          app.store.find('discussions', this.discussion.id()).then(() => {
            this.stream.update();

            if (!document.hasFocus()) {
              app.setTitleCount(Math.max(0, this.discussion.commentsCount() - oldCount));

              $(window).one('focus', () => app.setTitleCount(0));
            }
          });
        }
      });

      extend(context, 'onunload', () => channels.main.unbind('newPost'));
    });
  });

  extend(IndexPage.prototype, 'actionItems', items => {
    items.remove('refresh');
  });

  app.pusher.then(channels => {
    if (channels.user) {
      channels.user.bind('notification', () => {
        app.session.user.pushAttributes({
          unreadNotificationsCount: app.session.user.unreadNotificationsCount() + 1,
          newNotificationsCount: app.session.user.newNotificationsCount() + 1
        });
        delete app.cache.notifications;
        m.redraw();
      });
    }
  });
});
