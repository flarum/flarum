import { extend, notificationType } from 'flarum/extend';
import app from 'flarum/app';
import Model from 'flarum/Model';
import Discussion from 'flarum/models/Discussion';

import DiscussionStickiedPost from 'flarum/sticky/components/DiscussionStickiedPost';
import addStickyBadge from 'flarum/sticky/addStickyBadge';
import addStickyControl from 'flarum/sticky/addStickyControl';
import addStickyExcerpt from 'flarum/sticky/addStickyExcerpt';

app.initializers.add('flarum-sticky', () => {
  app.postComponents.discussionStickied = DiscussionStickiedPost;

  Discussion.prototype.isSticky = Model.attribute('isSticky');
  Discussion.prototype.canSticky = Model.attribute('canSticky');

  addStickyBadge();
  addStickyControl();
  addStickyExcerpt();
});