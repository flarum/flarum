import Tag from 'flarum/tags/models/Tag';
import addTagsPermissionScope from 'flarum/tags/addTagsPermissionScope';
import addTagPermission from 'flarum/tags/addTagPermission';
import addTagsPane from 'flarum/tags/addTagsPane';
import addTagsHomePageOption from 'flarum/tags/addTagsHomePageOption';
import addTagChangePermission from 'flarum/tags/addTagChangePermission';

app.initializers.add('flarum-tags', app => {
  app.store.models.tags = Tag;

  addTagsPermissionScope();
  addTagPermission();
  addTagsPane();
  addTagsHomePageOption();
  addTagChangePermission();
});
