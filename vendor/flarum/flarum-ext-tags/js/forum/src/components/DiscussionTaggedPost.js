import EventPost from 'flarum/components/EventPost';
import punctuateSeries from 'flarum/helpers/punctuateSeries';
import tagsLabel from 'flarum/tags/helpers/tagsLabel';

export default class DiscussionTaggedPost extends EventPost {
  static initProps(props) {
    super.initProps(props);

    const oldTags = props.post.content()[0];
    const newTags = props.post.content()[1];

    function diffTags(tags1, tags2) {
      return tags1
        .filter(tag => tags2.indexOf(tag) === -1)
        .map(id => app.store.getById('tags', id));
    }

    props.tagsAdded = diffTags(newTags, oldTags);
    props.tagsRemoved = diffTags(oldTags, newTags);
  }

  icon() {
    return 'tag';
  }

  descriptionKey() {
    if (this.props.tagsAdded.length) {
      if (this.props.tagsRemoved.length) {
        return 'flarum-tags.forum.post_stream.added_and_removed_tags_text';
      }

      return 'flarum-tags.forum.post_stream.added_tags_text';
    }

    return 'flarum-tags.forum.post_stream.removed_tags_text';
  }

  descriptionData() {
    const data = {};

    if (this.props.tagsAdded.length) {
      data.tagsAdded = app.translator.transChoice('flarum-tags.forum.post_stream.tags_text', this.props.tagsAdded.length, {
        tags: tagsLabel(this.props.tagsAdded, {link: true}),
        count: this.props.tagsAdded.length
      });
    }

    if (this.props.tagsRemoved.length) {
      data.tagsRemoved = app.translator.transChoice('flarum-tags.forum.post_stream.tags_text', this.props.tagsRemoved.length, {
        tags: tagsLabel(this.props.tagsRemoved, {link: true}),
        count: this.props.tagsRemoved.length
      });
    }

    return data;
  }
}
