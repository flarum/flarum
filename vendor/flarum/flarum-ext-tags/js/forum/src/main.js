import Model from 'flarum/Model';
import Discussion from 'flarum/models/Discussion';
import IndexPage from 'flarum/components/IndexPage';

import Tag from 'flarum/tags/models/Tag';
import TagsPage from 'flarum/tags/components/TagsPage';
import DiscussionTaggedPost from 'flarum/tags/components/DiscussionTaggedPost';

import addTagList from 'flarum/tags/addTagList';
import addTagFilter from 'flarum/tags/addTagFilter';
import addTagLabels from 'flarum/tags/addTagLabels';
import addTagControl from 'flarum/tags/addTagControl';
import addTagComposer from 'flarum/tags/addTagComposer';

app.initializers.add('flarum-tags', function(app) {
  app.routes.tags = {path: '/tags', component: TagsPage.component()};
  app.routes.tag = {path: '/t/:tags', component: IndexPage.component()};

  app.route.tag = tag => app.route('tag', {tags: tag.slug()});

  app.postComponents.discussionTagged = DiscussionTaggedPost;

  app.store.models.tags = Tag;

  Discussion.prototype.tags = Model.hasMany('tags');
  Discussion.prototype.canTag = Model.attribute('canTag');

  addTagList();
  addTagFilter();
  addTagLabels();
  addTagControl();
  addTagComposer();
});
