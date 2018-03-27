import EventPost from 'flarum/components/EventPost';

export default class DiscussionStickiedPost extends EventPost {
  icon() {
    return 'thumb-tack';
  }

  descriptionKey() {
    return this.props.post.content().sticky
      ? 'flarum-sticky.forum.post_stream.discussion_stickied_text'
      : 'flarum-sticky.forum.post_stream.discussion_unstickied_text';
  }
}
