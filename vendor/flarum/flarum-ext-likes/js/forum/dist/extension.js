'use strict';

System.register('flarum/likes/addLikeAction', ['flarum/extend', 'flarum/app', 'flarum/components/Button', 'flarum/components/CommentPost'], function (_export, _context) {
  "use strict";

  var extend, app, Button, CommentPost;

  _export('default', function () {
    extend(CommentPost.prototype, 'actionItems', function (items) {
      var post = this.props.post;

      if (post.isHidden() || !post.canLike()) return;

      var isLiked = app.session.user && post.likes().some(function (user) {
        return user === app.session.user;
      });

      items.add('like', Button.component({
        children: app.translator.trans(isLiked ? 'flarum-likes.forum.post.unlike_link' : 'flarum-likes.forum.post.like_link'),
        className: 'Button Button--link',
        onclick: function onclick() {
          isLiked = !isLiked;

          post.save({ isLiked: isLiked });

          // We've saved the fact that we do or don't like the post, but in order
          // to provide instantaneous feedback to the user, we'll need to add or
          // remove the like from the relationship data manually.
          var data = post.data.relationships.likes.data;
          data.some(function (like, i) {
            if (like.id === app.session.user.id()) {
              data.splice(i, 1);
              return true;
            }
          });

          if (isLiked) {
            data.unshift({ type: 'users', id: app.session.user.id() });
          }
        }
      }));
    });
  });

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/likes/addLikesList', ['flarum/extend', 'flarum/app', 'flarum/components/CommentPost', 'flarum/helpers/punctuateSeries', 'flarum/helpers/username', 'flarum/helpers/icon', 'flarum/likes/components/PostLikesModal'], function (_export, _context) {
  "use strict";

  var extend, app, CommentPost, punctuateSeries, username, icon, PostLikesModal;

  _export('default', function () {
    extend(CommentPost.prototype, 'footerItems', function (items) {
      var post = this.props.post;
      var likes = post.likes();

      if (likes && likes.length) {
        var limit = 4;
        var overLimit = likes.length > limit;

        // Construct a list of names of users who have liked this post. Make sure the
        // current user is first in the list, and cap a maximum of 4 items.
        var names = likes.sort(function (a) {
          return a === app.session.user ? -1 : 1;
        }).slice(0, overLimit ? limit - 1 : limit).map(function (user) {
          return m(
            'a',
            { href: app.route.user(user), config: m.route },
            user === app.session.user ? app.translator.trans('flarum-likes.forum.post.you_text') : username(user)
          );
        });

        // If there are more users that we've run out of room to display, add a "x
        // others" name to the end of the list. Clicking on it will display a modal
        // with a full list of names.
        if (overLimit) {
          var count = likes.length - names.length;

          names.push(m(
            'a',
            { href: '#', onclick: function onclick(e) {
                e.preventDefault();
                app.modal.show(new PostLikesModal({ post: post }));
              } },
            app.translator.transChoice('flarum-likes.forum.post.others_link', count, { count: count })
          ));
        }

        items.add('liked', m(
          'div',
          { className: 'Post-likedBy' },
          icon('thumbs-o-up'),
          app.translator.transChoice('flarum-likes.forum.post.liked_by' + (likes[0] === app.session.user ? '_self' : '') + '_text', names.length, {
            count: names.length,
            users: punctuateSeries(names)
          })
        ));
      }
    });
  });

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost.default;
    }, function (_flarumHelpersPunctuateSeries) {
      punctuateSeries = _flarumHelpersPunctuateSeries.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumLikesComponentsPostLikesModal) {
      PostLikesModal = _flarumLikesComponentsPostLikesModal.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/likes/components/PostLikedNotification', ['flarum/components/Notification', 'flarum/helpers/username', 'flarum/helpers/punctuateSeries'], function (_export, _context) {
  "use strict";

  var Notification, username, punctuateSeries, PostLikedNotification;
  return {
    setters: [function (_flarumComponentsNotification) {
      Notification = _flarumComponentsNotification.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }, function (_flarumHelpersPunctuateSeries) {
      punctuateSeries = _flarumHelpersPunctuateSeries.default;
    }],
    execute: function () {
      PostLikedNotification = function (_Notification) {
        babelHelpers.inherits(PostLikedNotification, _Notification);

        function PostLikedNotification() {
          babelHelpers.classCallCheck(this, PostLikedNotification);
          return babelHelpers.possibleConstructorReturn(this, Object.getPrototypeOf(PostLikedNotification).apply(this, arguments));
        }

        babelHelpers.createClass(PostLikedNotification, [{
          key: 'icon',
          value: function icon() {
            return 'thumbs-o-up';
          }
        }, {
          key: 'href',
          value: function href() {
            return app.route.post(this.props.notification.subject());
          }
        }, {
          key: 'content',
          value: function content() {
            var notification = this.props.notification;
            var user = notification.sender();
            var auc = notification.additionalUnreadCount();

            return app.translator.transChoice('flarum-likes.forum.notifications.post_liked_text', auc + 1, {
              user: user,
              username: auc ? punctuateSeries([username(user), app.translator.transChoice('flarum-likes.forum.notifications.others_text', auc, { count: auc })]) : undefined
            });
          }
        }, {
          key: 'excerpt',
          value: function excerpt() {
            return this.props.notification.subject().contentPlain();
          }
        }]);
        return PostLikedNotification;
      }(Notification);

      _export('default', PostLikedNotification);
    }
  };
});;
'use strict';

System.register('flarum/likes/components/PostLikesModal', ['flarum/components/Modal', 'flarum/helpers/avatar', 'flarum/helpers/username'], function (_export, _context) {
  "use strict";

  var Modal, avatar, username, PostLikesModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }],
    execute: function () {
      PostLikesModal = function (_Modal) {
        babelHelpers.inherits(PostLikesModal, _Modal);

        function PostLikesModal() {
          babelHelpers.classCallCheck(this, PostLikesModal);
          return babelHelpers.possibleConstructorReturn(this, Object.getPrototypeOf(PostLikesModal).apply(this, arguments));
        }

        babelHelpers.createClass(PostLikesModal, [{
          key: 'className',
          value: function className() {
            return 'PostLikesModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('flarum-likes.forum.post_likes.title');
          }
        }, {
          key: 'content',
          value: function content() {
            return m(
              'div',
              { className: 'Modal-body' },
              m(
                'ul',
                { className: 'PostLikesModal-list' },
                this.props.post.likes().map(function (user) {
                  return m(
                    'li',
                    null,
                    m(
                      'a',
                      { href: app.route.user(user), config: m.route },
                      avatar(user),
                      ' ',
                      ' ',
                      username(user)
                    )
                  );
                })
              )
            );
          }
        }]);
        return PostLikesModal;
      }(Modal);

      _export('default', PostLikesModal);
    }
  };
});;
'use strict';

System.register('flarum/likes/main', ['flarum/extend', 'flarum/app', 'flarum/models/Post', 'flarum/Model', 'flarum/components/NotificationGrid', 'flarum/likes/addLikeAction', 'flarum/likes/addLikesList', 'flarum/likes/components/PostLikedNotification'], function (_export, _context) {
  "use strict";

  var extend, app, Post, Model, NotificationGrid, addLikeAction, addLikesList, PostLikedNotification;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumModelsPost) {
      Post = _flarumModelsPost.default;
    }, function (_flarumModel) {
      Model = _flarumModel.default;
    }, function (_flarumComponentsNotificationGrid) {
      NotificationGrid = _flarumComponentsNotificationGrid.default;
    }, function (_flarumLikesAddLikeAction) {
      addLikeAction = _flarumLikesAddLikeAction.default;
    }, function (_flarumLikesAddLikesList) {
      addLikesList = _flarumLikesAddLikesList.default;
    }, function (_flarumLikesComponentsPostLikedNotification) {
      PostLikedNotification = _flarumLikesComponentsPostLikedNotification.default;
    }],
    execute: function () {

      app.initializers.add('flarum-likes', function () {
        app.notificationComponents.postLiked = PostLikedNotification;

        Post.prototype.canLike = Model.attribute('canLike');
        Post.prototype.likes = Model.hasMany('likes');

        addLikeAction();
        addLikesList();

        extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
          items.add('postLiked', {
            name: 'postLiked',
            icon: 'thumbs-o-up',
            label: app.translator.trans('flarum-likes.forum.settings.notify_post_liked_label')
          });
        });
      });
    }
  };
});