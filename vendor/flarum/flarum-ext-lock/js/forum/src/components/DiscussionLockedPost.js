import EventPost from 'flarum/components/EventPost';

export default class DiscussionLockedPost extends EventPost {
  icon() {
    return this.props.post.content().locked
      ? 'lock'
      : 'unlock';
  }

  descriptionKey() {
    return this.props.post.content().locked
      ? 'flarum-lock.forum.post_stream.discussion_locked_text'
      : 'flarum-lock.forum.post_stream.discussion_unlocked_text';
  }
}
