import ComposerBody from 'flarum/components/ComposerBody';
import extractText from 'flarum/utils/extractText';

/**
 * The `DiscussionComposer` component displays the composer content for starting
 * a new discussion. It adds a text field as a header control so the user can
 * enter the title of their discussion. It also overrides the `submit` and
 * `willExit` actions to account for the title.
 *
 * ### Props
 *
 * - All of the props for ComposerBody
 * - `titlePlaceholder`
 */
export default class DiscussionComposer extends ComposerBody {
  init() {
    super.init();

    /**
     * The value of the title input.
     *
     * @type {Function}
     */
    this.title = m.prop('');
  }

  static initProps(props) {
    super.initProps(props);

    props.placeholder = props.placeholder || extractText(app.translator.trans('core.forum.composer_discussion.body_placeholder'));
    props.submitLabel = props.submitLabel || app.translator.trans('core.forum.composer_discussion.submit_button');
    props.confirmExit = props.confirmExit || extractText(app.translator.trans('core.forum.composer_discussion.discard_confirmation'));
    props.titlePlaceholder = props.titlePlaceholder || extractText(app.translator.trans('core.forum.composer_discussion.title_placeholder'));
    props.className = 'ComposerBody--discussion';
  }

  headerItems() {
    const items = super.headerItems();

    items.add('title', <h3>{app.translator.trans('core.forum.composer_discussion.title')}</h3>, 100);

    items.add('discussionTitle', (
      <h3>
        <input className="FormControl"
          value={this.title()}
          oninput={m.withAttr('value', this.title)}
          placeholder={this.props.titlePlaceholder}
          disabled={!!this.props.disabled}
          onkeydown={this.onkeydown.bind(this)}/>
      </h3>
    ));

    return items;
  }

  /**
   * Handle the title input's keydown event. When the return key is pressed,
   * move the focus to the start of the text editor.
   *
   * @param {Event} e
   */
  onkeydown(e) {
    if (e.which === 13) { // Return
      e.preventDefault();
      this.editor.setSelectionRange(0, 0);
    }

    m.redraw.strategy('none');
  }

  preventExit() {
    return (this.title() || this.content()) && this.props.confirmExit;
  }

  /**
   * Get the data to submit to the server when the discussion is saved.
   *
   * @return {Object}
   */
  data() {
    return {
      title: this.title(),
      content: this.content()
    };
  }

  onsubmit() {
    this.loading = true;

    const data = this.data();

    app.store.createRecord('discussions').save(data).then(
      discussion => {
        app.composer.hide();
        app.cache.discussionList.addDiscussion(discussion);
        m.route(app.route.discussion(discussion));
      },
      this.loaded.bind(this)
    );
  }
}
