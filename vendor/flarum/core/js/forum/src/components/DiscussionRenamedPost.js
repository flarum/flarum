import EventPost from 'flarum/components/EventPost';
import extractText from 'flarum/utils/extractText';

/**
 * The `DiscussionRenamedPost` component displays a discussion event post
 * indicating that the discussion has been renamed.
 *
 * ### Props
 *
 * - All of the props for EventPost
 */
export default class DiscussionRenamedPost extends EventPost {
  icon() {
    return 'pencil';
  }

  description(data) {
    const renamed = app.translator.trans('core.forum.post_stream.discussion_renamed_text', data);
    const oldName = app.translator.trans('core.forum.post_stream.discussion_renamed_old_tooltip', data);

    return <span title={extractText(oldName)}>{renamed}</span>;
  }

  descriptionData() {
    const post = this.props.post;
    const oldTitle = post.content()[0];
    const newTitle = post.content()[1];

    return {
      'old': oldTitle,
      'new': <strong className="DiscussionRenamedPost-new">{newTitle}</strong>
    };
  }
}
