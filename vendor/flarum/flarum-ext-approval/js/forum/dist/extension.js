'use strict';

System.register('flarum/approval/main', ['flarum/extend', 'flarum/app', 'flarum/models/Discussion', 'flarum/models/Post', 'flarum/components/Badge', 'flarum/components/DiscussionListItem', 'flarum/components/Post', 'flarum/components/CommentPost', 'flarum/components/Button', 'flarum/utils/PostControls'], function (_export, _context) {
  "use strict";

  var extend, override, app, Discussion, Post, Badge, DiscussionListItem, PostComponent, CommentPost, Button, PostControls;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
      override = _flarumExtend.override;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumModelsDiscussion) {
      Discussion = _flarumModelsDiscussion.default;
    }, function (_flarumModelsPost) {
      Post = _flarumModelsPost.default;
    }, function (_flarumComponentsBadge) {
      Badge = _flarumComponentsBadge.default;
    }, function (_flarumComponentsDiscussionListItem) {
      DiscussionListItem = _flarumComponentsDiscussionListItem.default;
    }, function (_flarumComponentsPost) {
      PostComponent = _flarumComponentsPost.default;
    }, function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumUtilsPostControls) {
      PostControls = _flarumUtilsPostControls.default;
    }],
    execute: function () {

      app.initializers.add('flarum-approval', function () {
        Discussion.prototype.isApproved = Discussion.attribute('isApproved');

        extend(Discussion.prototype, 'badges', function (items) {
          if (!this.isApproved() && !items.has('hidden')) {
            items.add('awaitingApproval', m(Badge, { type: 'awaitingApproval', icon: 'gavel', label: app.translator.trans('flarum-approval.forum.badge.awaiting_approval_tooltip') }));
          }
        });

        Post.prototype.isApproved = Post.attribute('isApproved');
        Post.prototype.canApprove = Post.attribute('canApprove');

        extend(DiscussionListItem.prototype, 'attrs', function (attrs) {
          if (!this.props.discussion.isApproved()) {
            attrs.className += ' DiscussionListItem--unapproved';
          }
        });

        extend(PostComponent.prototype, 'attrs', function (attrs) {
          if (!this.props.post.isApproved()) {
            attrs.className += ' Post--unapproved';
          }
        });

        extend(CommentPost.prototype, 'headerItems', function (items) {
          if (!this.props.post.isApproved() && !this.props.post.isHidden()) {
            items.add('unapproved', app.translator.trans('flarum-approval.forum.post.awaiting_approval_text'));
          }
        });

        override(PostComponent.prototype, 'flagReason', function (original, flag) {
          if (flag.type() === 'approval') {
            return app.translator.trans('flarum-approval.forum.post.awaiting_approval_text');
          }

          return original(flag);
        });

        extend(PostControls, 'destructiveControls', function (items, post) {
          if (!post.isApproved() && post.canApprove()) {
            items.add('approve', m(
              Button,
              { icon: 'check', onclick: PostControls.approveAction.bind(post) },
              app.translator.trans('flarum-approval.forum.post_controls.approve_button')
            ), 10);
          }
        });

        PostControls.approveAction = function () {
          this.save({ isApproved: true });

          if (this.number() === 1) {
            this.discussion().pushAttributes({ isApproved: true });
          }
        };
      }, -10); // set initializer priority to run after reports
    }
  };
});