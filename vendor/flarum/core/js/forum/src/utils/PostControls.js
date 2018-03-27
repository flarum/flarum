import EditPostComposer from 'flarum/components/EditPostComposer';
import Button from 'flarum/components/Button';
import Separator from 'flarum/components/Separator';
import ItemList from 'flarum/utils/ItemList';

/**
 * The `PostControls` utility constructs a list of buttons for a post which
 * perform actions on it.
 */
export default {
  /**
   * Get a list of controls for a post.
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @public
   */
  controls(post, context) {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach(section => {
      const controls = this[section + 'Controls'](post, context).toArray();
      if (controls.length) {
        controls.forEach(item => items.add(item.itemName, item));
        items.add(section + 'Separator', Separator.component());
      }
    });

    return items;
  },

  /**
   * Get controls for a post pertaining to the current user (e.g. report).
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  userControls(post, context) {
    return new ItemList();
  },

  /**
   * Get controls for a post pertaining to moderation (e.g. edit).
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  moderationControls(post, context) {
    const items = new ItemList();

    if (post.contentType() === 'comment' && post.canEdit()) {
      if (!post.isHidden()) {
        items.add('edit', Button.component({
          icon: 'pencil',
          children: app.translator.trans('core.forum.post_controls.edit_button'),
          onclick: this.editAction.bind(post)
        }));
      }
    }

    return items;
  },

  /**
   * Get controls for a post that are destructive (e.g. delete).
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  destructiveControls(post, context) {
    const items = new ItemList();

    if (post.contentType() === 'comment' && !post.isHidden()) {
      if (post.canEdit()) {
        items.add('hide', Button.component({
          icon: 'trash-o',
          children: app.translator.trans('core.forum.post_controls.delete_button'),
          onclick: this.hideAction.bind(post)
        }));
      }
    } else {
      if (post.contentType() === 'comment' && post.canEdit()) {
        items.add('restore', Button.component({
          icon: 'reply',
          children: app.translator.trans('core.forum.post_controls.restore_button'),
          onclick: this.restoreAction.bind(post)
        }));
      }
      if (post.canDelete()) {
        items.add('delete', Button.component({
          icon: 'times',
          children: app.translator.trans('core.forum.post_controls.delete_forever_button'),
          onclick: this.deleteAction.bind(post, context)
        }));
      }
    }

    return items;
  },

  /**
   * Open the composer to edit a post.
   */
  editAction() {
    app.composer.load(new EditPostComposer({ post: this }));
    app.composer.show();
  },

  /**
   * Hide a post.
   *
   * @return {Promise}
   */
  hideAction() {
    this.pushAttributes({ hideTime: new Date(), hideUser: app.session.user });

    return this.save({ isHidden: true }).then(() => m.redraw());
  },

  /**
   * Restore a post.
   *
   * @return {Promise}
   */
  restoreAction() {
    this.pushAttributes({ hideTime: null, hideUser: null });

    return this.save({ isHidden: false }).then(() => m.redraw());
  },

  /**
   * Delete a post.
   *
   * @return {Promise}
   */
  deleteAction(context) {
    if (context) context.loading = true;

    return this.delete()
      .then(() => {
        const discussion = this.discussion();

        discussion.removePost(this.id());

        // If this was the last post in the discussion, then we will assume that
        // the whole discussion was deleted too.
        if (!discussion.postIds().length) {
          // If there is a discussion list in the cache, remove this discussion.
          if (app.cache.discussionList) {
            app.cache.discussionList.removeDiscussion(discussion);
          }

          if (app.viewingDiscussion(discussion)) {
            app.history.back();
          }
        }
      })
      .catch(() => {})
      .then(() => {
        if (context) context.loading = false;
        m.redraw();
      });
  }
};
