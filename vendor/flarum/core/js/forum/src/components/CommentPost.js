/*global s9e, hljs*/

import Post from 'flarum/components/Post';
import classList from 'flarum/utils/classList';
import PostUser from 'flarum/components/PostUser';
import PostMeta from 'flarum/components/PostMeta';
import PostEdited from 'flarum/components/PostEdited';
import EditPostComposer from 'flarum/components/EditPostComposer';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';
import Button from 'flarum/components/Button';

/**
 * The `CommentPost` component displays a standard `comment`-typed post. This
 * includes a number of item lists (controls, header, and footer) surrounding
 * the post's HTML content.
 *
 * ### Props
 *
 * - `post`
 */
export default class CommentPost extends Post {
  init() {
    super.init();

    /**
     * If the post has been hidden, then this flag determines whether or not its
     * content has been expanded.
     *
     * @type {Boolean}
     */
    this.revealContent = false;

    // Create an instance of the component that displays the post's author so
    // that we can force the post to rerender when the user card is shown.
    this.postUser = new PostUser({post: this.props.post});
    this.subtree.check(
      () => this.postUser.cardVisible,
      () => this.isEditing()
    );
  }

  content() {
    // Note: we avoid using JSX for the <ul> below because it results in some
    // weirdness in Mithril.js 0.1.x (see flarum/core#975). This workaround can
    // be reverted when we upgrade to Mithril 1.0.
    return super.content().concat([
      <header className="Post-header">{m('ul', listItems(this.headerItems().toArray()))}</header>,
      <div className="Post-body">
        {this.isEditing()
          ? <div className="Post-preview" config={this.configPreview.bind(this)}/>
          : m.trust(this.props.post.contentHtml())}
      </div>
    ]);
  }

  config(isInitialized, context) {
    super.config(...arguments);

    const contentHtml = this.isEditing() ? '' : this.props.post.contentHtml();

    // If the post content has changed since the last render, we'll run through
    // all of the <script> tags in the content and evaluate them. This is
    // necessary because TextFormatter outputs them for e.g. syntax highlighting.
    if (context.contentHtml !== contentHtml) {
      this.$('.Post-body script').each(function() {
        eval.call(window, $(this).text());
      });
    }

    context.contentHtml = contentHtml;
  }

  isEditing() {
    return app.composer.component instanceof EditPostComposer &&
      app.composer.component.props.post === this.props.post;
  }

  attrs() {
    const post = this.props.post;
    const attrs = super.attrs();

    attrs.className += ' '+classList({
      'CommentPost': true,
      'Post--hidden': post.isHidden(),
      'Post--edited': post.isEdited(),
      'revealContent': this.revealContent,
      'editing': this.isEditing()
    });

    return attrs;
  }

  configPreview(element, isInitialized, context) {
    if (isInitialized) return;

    // Every 50ms, if the composer content has changed, then update the post's
    // body with a preview.
    let preview;
    const updatePreview = () => {
      const content = app.composer.component.content();

      if (preview === content) return;

      preview = content;

      s9e.TextFormatter.preview(preview || '', element);
    };
    updatePreview();

    const updateInterval = setInterval(updatePreview, 50);
    context.onunload = () => clearInterval(updateInterval);
  }

  /**
   * Toggle the visibility of a hidden post's content.
   */
  toggleContent() {
    this.revealContent = !this.revealContent;
  }

  /**
   * Build an item list for the post's header.
   *
   * @return {ItemList}
   */
  headerItems() {
    const items = new ItemList();
    const post = this.props.post;
    const props = {post};

    items.add('user', this.postUser.render(), 100);
    items.add('meta', PostMeta.component(props));

    if (post.isEdited() && !post.isHidden()) {
      items.add('edited', PostEdited.component(props));
    }

    // If the post is hidden, add a button that allows toggling the visibility
    // of the post's content.
    if (post.isHidden()) {
      items.add('toggle', (
        Button.component({
          className: 'Button Button--default Button--more',
          icon: 'ellipsis-h',
          onclick: this.toggleContent.bind(this)
        })
      ));
    }

    return items;
  }
}
