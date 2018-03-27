'use strict';

System.register('flarum/pusher/main', ['flarum/extend', 'flarum/app', 'flarum/components/DiscussionList', 'flarum/components/DiscussionPage', 'flarum/components/IndexPage', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var extend, app, DiscussionList, DiscussionPage, IndexPage, Button;
  return {
    setters: [function (_flarumExtend) {
      /*global Pusher*/

      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumComponentsDiscussionList) {
      DiscussionList = _flarumComponentsDiscussionList.default;
    }, function (_flarumComponentsDiscussionPage) {
      DiscussionPage = _flarumComponentsDiscussionPage.default;
    }, function (_flarumComponentsIndexPage) {
      IndexPage = _flarumComponentsIndexPage.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {

      app.initializers.add('flarum-pusher', function () {
        var loadPusher = m.deferred();

        $.getScript('//js.pusher.com/3.0/pusher.min.js', function () {
          var socket = new Pusher(app.forum.attribute('pusherKey'), {
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

        extend(DiscussionList.prototype, 'config', function (x, isInitialized, context) {
          var _this = this;

          if (isInitialized) return;

          app.pusher.then(function (channels) {
            channels.main.bind('newPost', function (data) {
              var params = _this.props.params;

              if (!params.q && !params.sort && !params.filter) {
                if (params.tags) {
                  var tag = app.store.getBy('tags', 'slug', params.tags);

                  if (data.tagIds.indexOf(tag.id()) === -1) return;
                }

                var id = String(data.discussionId);

                if ((!app.current.discussion || id !== app.current.discussion.id()) && app.pushedUpdates.indexOf(id) === -1) {
                  app.pushedUpdates.push(id);

                  if (app.current instanceof IndexPage) {
                    app.setTitleCount(app.pushedUpdates.length);
                  }

                  m.redraw();
                }
              }
            });

            extend(context, 'onunload', function () {
              return channels.main.unbind('newPost');
            });
          });
        });

        extend(DiscussionList.prototype, 'view', function (vdom) {
          var _this2 = this;

          if (app.pushedUpdates) {
            var count = app.pushedUpdates.length;

            if (count) {
              vdom.children.unshift(Button.component({
                className: 'Button Button--block DiscussionList-update',
                onclick: function onclick() {
                  _this2.refresh(false).then(function () {
                    _this2.loadingUpdated = false;
                    app.pushedUpdates = [];
                    app.setTitleCount(0);
                    m.redraw();
                  });
                  _this2.loadingUpdated = true;
                },
                loading: this.loadingUpdated,
                children: app.translator.transChoice('flarum-pusher.forum.discussion_list.show_updates_text', count, { count: count })
              }));
            }
          }
        });

        // Prevent any newly-created discussions from triggering the discussion list
        // update button showing.
        // TODO: Might be better pause the response to the push updates while the
        // composer is loading? idk
        extend(DiscussionList.prototype, 'addDiscussion', function (returned, discussion) {
          var index = app.pushedUpdates.indexOf(discussion.id());

          if (index !== -1) {
            app.pushedUpdates.splice(index, 1);
          }

          if (app.current instanceof IndexPage) {
            app.setTitleCount(app.pushedUpdates.length);
          }

          m.redraw();
        });

        extend(DiscussionPage.prototype, 'config', function (x, isInitialized, context) {
          var _this3 = this;

          if (isInitialized) return;

          app.pusher.then(function (channels) {
            channels.main.bind('newPost', function (data) {
              var id = String(data.discussionId);

              if (_this3.discussion && _this3.discussion.id() === id && _this3.stream) {
                (function () {
                  var oldCount = _this3.discussion.commentsCount();

                  app.store.find('discussions', _this3.discussion.id()).then(function () {
                    _this3.stream.update();

                    if (!document.hasFocus()) {
                      app.setTitleCount(Math.max(0, _this3.discussion.commentsCount() - oldCount));

                      $(window).one('focus', function () {
                        return app.setTitleCount(0);
                      });
                    }
                  });
                })();
              }
            });

            extend(context, 'onunload', function () {
              return channels.main.unbind('newPost');
            });
          });
        });

        extend(IndexPage.prototype, 'actionItems', function (items) {
          items.remove('refresh');
        });

        app.pusher.then(function (channels) {
          if (channels.user) {
            channels.user.bind('notification', function () {
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
    }
  };
});