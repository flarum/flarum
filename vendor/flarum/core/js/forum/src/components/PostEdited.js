import Component from 'flarum/Component';
import humanTime from 'flarum/utils/humanTime';
import extractText from 'flarum/utils/extractText';

/**
 * The `PostEdited` component displays information about when and by whom a post
 * was edited.
 *
 * ### Props
 *
 * - `post`
 */
export default class PostEdited extends Component {
  init() {
    this.shouldUpdateTooltip = false;
    this.oldEditedInfo = null;
  }

  view() {
    const post = this.props.post;
    const editUser = post.editUser();
    const editedInfo = extractText(app.translator.trans(
      'core.forum.post.edited_tooltip',
      {user: editUser, ago: humanTime(post.editTime())}
    ));
    if (editedInfo !== this.oldEditedInfo) {
      this.shouldUpdateTooltip = true;
      this.oldEditedInfo = editedInfo;
    }

    return (
      <span className="PostEdited" title={editedInfo}>
        {app.translator.trans('core.forum.post.edited_text')}
      </span>
    );
  }

  config(isInitialized) {
    if (this.shouldUpdateTooltip) {
      this.$().tooltip('destroy').tooltip();
      this.shouldUpdateTooltip = false;
    }
  }
}
