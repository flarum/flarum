import { extend, override } from 'flarum/extend';
import app from 'flarum/app';
import Discussion from 'flarum/models/Discussion';
import Post from 'flarum/models/Post';
import Badge from 'flarum/components/Badge';
import DiscussionListItem from 'flarum/components/DiscussionListItem';
import PostComponent from 'flarum/components/Post';
import CommentPost from 'flarum/components/CommentPost';
import Button from 'flarum/components/Button';
import PostControls from 'flarum/utils/PostControls';

app.initializers.add('flarum-approval', () => {
  Discussion.prototype.isApproved = Discussion.attribute('isApproved');

  extend(Discussion.prototype, 'badges', function(items) {
    if (!this.isApproved() && !items.has('hidden')) {
      items.add('awaitingApproval', <Badge type="awaitingApproval" icon="gavel" label={app.translator.trans('flarum-approval.forum.badge.awaiting_approval_tooltip')}/>);
    }
  });

  Post.prototype.isApproved = Post.attribute('isApproved');
  Post.prototype.canApprove = Post.attribute('canApprove');

  extend(DiscussionListItem.prototype, 'attrs', function(attrs) {
    if (!this.props.discussion.isApproved()) {
      attrs.className += ' DiscussionListItem--unapproved';
    }
  });

  extend(PostComponent.prototype, 'attrs', function(attrs) {
    if (!this.props.post.isApproved()) {
      attrs.className += ' Post--unapproved';
    }
  });

  extend(CommentPost.prototype, 'headerItems', function(items) {
    if (!this.props.post.isApproved() && !this.props.post.isHidden()) {
      items.add('unapproved', app.translator.trans('flarum-approval.forum.post.awaiting_approval_text'));
    }
  });

  override(PostComponent.prototype, 'flagReason', function(original, flag) {
    if (flag.type() === 'approval') {
      return app.translator.trans('flarum-approval.forum.post.awaiting_approval_text');
    }

    return original(flag);
  });

  extend(PostControls, 'destructiveControls', function(items, post) {
    if (!post.isApproved() && post.canApprove()) {
      items.add('approve',
        <Button icon="check" onclick={PostControls.approveAction.bind(post)}>
          {app.translator.trans('flarum-approval.forum.post_controls.approve_button')}
        </Button>,
        10
      );
    }
  });

  PostControls.approveAction = function() {
    this.save({isApproved: true});

    if (this.number() === 1) {
      this.discussion().pushAttributes({isApproved: true});
    }
  };
}, -10); // set initializer priority to run after reports
