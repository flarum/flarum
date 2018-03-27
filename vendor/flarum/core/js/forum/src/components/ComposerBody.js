import Component from 'flarum/Component';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import TextEditor from 'flarum/components/TextEditor';
import avatar from 'flarum/helpers/avatar';
import listItems from 'flarum/helpers/listItems';
import ItemList from 'flarum/utils/ItemList';

/**
 * The `ComposerBody` component handles the body, or the content, of the
 * composer. Subclasses should implement the `onsubmit` method and override
 * `headerTimes`.
 *
 * ### Props
 *
 * - `originalContent`
 * - `submitLabel`
 * - `placeholder`
 * - `user`
 * - `confirmExit`
 * - `disabled`
 *
 * @abstract
 */
export default class ComposerBody extends Component {
  init() {
    /**
     * Whether or not the component is loading.
     *
     * @type {Boolean}
     */
    this.loading = false;

    /**
     * The content of the text editor.
     *
     * @type {Function}
     */
    this.content = m.prop(this.props.originalContent);

    /**
     * The text editor component instance.
     *
     * @type {TextEditor}
     */
    this.editor = new TextEditor({
      submitLabel: this.props.submitLabel,
      placeholder: this.props.placeholder,
      onchange: this.content,
      onsubmit: this.onsubmit.bind(this),
      value: this.content()
    });
  }

  view() {
    // If the component is loading, we should disable the text editor.
    this.editor.props.disabled = this.loading;

    return (
      <div className={'ComposerBody ' + (this.props.className || '')}>
        {avatar(this.props.user, {className: 'ComposerBody-avatar'})}
        <div className="ComposerBody-content">
          <ul className="ComposerBody-header">{listItems(this.headerItems().toArray())}</ul>
          <div className="ComposerBody-editor">{this.editor.render()}</div>
        </div>
        {LoadingIndicator.component({className: 'ComposerBody-loading' + (this.loading ? ' active' : '')})}
      </div>
    );
  }

  /**
   * Draw focus to the text editor.
   */
  focus() {
    this.$(':input:enabled:visible:first').focus();
  }

  /**
   * Check if there is any unsaved data – if there is, return a confirmation
   * message to prompt the user with.
   *
   * @return {String}
   */
  preventExit() {
    const content = this.content();

    return content && content !== this.props.originalContent && this.props.confirmExit;
  }

  /**
   * Build an item list for the composer's header.
   *
   * @return {ItemList}
   */
  headerItems() {
    return new ItemList();
  }

  /**
   * Handle the submit event of the text editor.
   *
   * @abstract
   */
  onsubmit() {
  }

  /**
   * Stop loading.
   */
  loaded() {
    this.loading = false;
    m.redraw();
  }
}
