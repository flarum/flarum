'use strict';

System.register('flarum/sticky/addStickyBadge', ['flarum/extend', 'flarum/models/Discussion', 'flarum/components/Badge'], function (_export, _context) {
  "use strict";

  var extend, Discussion, Badge;
  function addStickyBadge() {
    extend(Discussion.prototype, 'badges', function (badges) {
      if (this.isSticky()) {
        badges.add('sticky', Badge.component({
          type: 'sticky',
          label: app.translator.trans('flarum-sticky.forum.badge.sticky_tooltip'),
          icon: 'thumb-tack'
        }), 10);
      }
    });
  }

  _export('default', addStickyBadge);

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumModelsDiscussion) {
      Discussion = _flarumModelsDiscussion.default;
    }, function (_flarumComponentsBadge) {
      Badge = _flarumComponentsBadge.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/sticky/addStickyControl', ['flarum/extend', 'flarum/utils/DiscussionControls', 'flarum/components/DiscussionPage', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var extend, DiscussionControls, DiscussionPage, Button;
  function addStickyControl() {
    extend(DiscussionControls, 'moderationControls', function (items, discussion) {
      if (discussion.canSticky()) {
        items.add('sticky', Button.component({
          children: app.translator.trans(discussion.isSticky() ? 'flarum-sticky.forum.discussion_controls.unsticky_button' : 'flarum-sticky.forum.discussion_controls.sticky_button'),
          icon: 'thumb-tack',
          onclick: this.stickyAction.bind(discussion)
        }));
      }
    });

    DiscussionControls.stickyAction = function () {
      this.save({ isSticky: !this.isSticky() }).then(function () {
        if (app.current instanceof DiscussionPage) {
          app.current.stream.update();
        }

        m.redraw();
      });
    };
  }

  _export('default', addStickyControl);

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumUtilsDiscussionControls) {
      DiscussionControls = _flarumUtilsDiscussionControls.default;
    }, function (_flarumComponentsDiscussionPage) {
      DiscussionPage = _flarumComponentsDiscussionPage.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/sticky/addStickyExcerpt', ['flarum/extend', 'flarum/components/DiscussionList', 'flarum/components/DiscussionListItem', 'flarum/utils/string'], function (_export, _context) {
  "use strict";

  var extend, DiscussionList, DiscussionListItem, truncate;
  function addStickyControl() {
    extend(DiscussionList.prototype, 'requestParams', function (params) {
      params.include.push('startPost');
    });

    extend(DiscussionListItem.prototype, 'infoItems', function (items) {
      var discussion = this.props.discussion;

      if (discussion.isSticky()) {
        var startPost = discussion.startPost();

        if (startPost) {
          var excerpt = m(
            'span',
            null,
            truncate(startPost.contentPlain(), 200)
          );

          items.add('excerpt', excerpt, -100);
        }
      }
    });
  }

  _export('default', addStickyControl);

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsDiscussionList) {
      DiscussionList = _flarumComponentsDiscussionList.default;
    }, function (_flarumComponentsDiscussionListItem) {
      DiscussionListItem = _flarumComponentsDiscussionListItem.default;
    }, function (_flarumUtilsString) {
      truncate = _flarumUtilsString.truncate;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/sticky/components/DiscussionStickiedPost', ['flarum/components/EventPost'], function (_export, _context) {
  "use strict";

  var EventPost, DiscussionStickiedPost;
  return {
    setters: [function (_flarumComponentsEventPost) {
      EventPost = _flarumComponentsEventPost.default;
    }],
    execute: function () {
      DiscussionStickiedPost = function (_EventPost) {
        babelHelpers.inherits(DiscussionStickiedPost, _EventPost);

        function DiscussionStickiedPost() {
          babelHelpers.classCallCheck(this, DiscussionStickiedPost);
          return babelHelpers.possibleConstructorReturn(this, (DiscussionStickiedPost.__proto__ || Object.getPrototypeOf(DiscussionStickiedPost)).apply(this, arguments));
        }

        babelHelpers.createClass(DiscussionStickiedPost, [{
          key: 'icon',
          value: function icon() {
            return 'thumb-tack';
          }
        }, {
          key: 'descriptionKey',
          value: function descriptionKey() {
            return this.props.post.content().sticky ? 'flarum-sticky.forum.post_stream.discussion_stickied_text' : 'flarum-sticky.forum.post_stream.discussion_unstickied_text';
          }
        }]);
        return DiscussionStickiedPost;
      }(EventPost);

      _export('default', DiscussionStickiedPost);
    }
  };
});;
'use strict';

System.register('flarum/sticky/main', ['flarum/extend', 'flarum/app', 'flarum/Model', 'flarum/models/Discussion', 'flarum/sticky/components/DiscussionStickiedPost', 'flarum/sticky/addStickyBadge', 'flarum/sticky/addStickyControl', 'flarum/sticky/addStickyExcerpt'], function (_export, _context) {
  "use strict";

  var extend, notificationType, app, Model, Discussion, DiscussionStickiedPost, addStickyBadge, addStickyControl, addStickyExcerpt;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
      notificationType = _flarumExtend.notificationType;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumModel) {
      Model = _flarumModel.default;
    }, function (_flarumModelsDiscussion) {
      Discussion = _flarumModelsDiscussion.default;
    }, function (_flarumStickyComponentsDiscussionStickiedPost) {
      DiscussionStickiedPost = _flarumStickyComponentsDiscussionStickiedPost.default;
    }, function (_flarumStickyAddStickyBadge) {
      addStickyBadge = _flarumStickyAddStickyBadge.default;
    }, function (_flarumStickyAddStickyControl) {
      addStickyControl = _flarumStickyAddStickyControl.default;
    }, function (_flarumStickyAddStickyExcerpt) {
      addStickyExcerpt = _flarumStickyAddStickyExcerpt.default;
    }],
    execute: function () {

      app.initializers.add('flarum-sticky', function () {
        app.postComponents.discussionStickied = DiscussionStickiedPost;

        Discussion.prototype.isSticky = Model.attribute('isSticky');
        Discussion.prototype.canSticky = Model.attribute('canSticky');

        addStickyBadge();
        addStickyControl();
        addStickyExcerpt();
      });
    }
  };
});