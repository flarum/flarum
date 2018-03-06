import Model from 'flarum/Model';
import computed from 'flarum/utils/computed';
import { getPlainContent } from 'flarum/utils/string';

export default class Post extends Model {}

Object.assign(Post.prototype, {
  number: Model.attribute('number'),
  discussion: Model.hasOne('discussion'),

  time: Model.attribute('time', Model.transformDate),
  user: Model.hasOne('user'),
  contentType: Model.attribute('contentType'),
  content: Model.attribute('content'),
  contentHtml: Model.attribute('contentHtml'),
  contentPlain: computed('contentHtml', getPlainContent),

  editTime: Model.attribute('editTime', Model.transformDate),
  editUser: Model.hasOne('editUser'),
  isEdited: computed('editTime', editTime => !!editTime),

  hideTime: Model.attribute('hideTime', Model.transformDate),
  hideUser: Model.hasOne('hideUser'),
  isHidden: computed('hideTime', hideTime => !!hideTime),

  canEdit: Model.attribute('canEdit'),
  canDelete: Model.attribute('canDelete')
});
