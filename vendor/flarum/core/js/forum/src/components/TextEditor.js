import Component from 'flarum/Component';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';
import Button from 'flarum/components/Button';

/**
 * The `TextEditor` component displays a textarea with controls, including a
 * submit button.
 *
 * ### Props
 *
 * - `submitLabel`
 * - `value`
 * - `placeholder`
 * - `disabled`
 */
export default class TextEditor extends Component {
  init() {
    /**
     * The value of the textarea.
     *
     * @type {String}
     */
    this.value = m.prop(this.props.value || '');
  }

  view() {
    return (
      <div className="TextEditor">
        <textarea className="FormControl Composer-flexible"
          config={this.configTextarea.bind(this)}
          oninput={m.withAttr('value', this.oninput.bind(this))}
          placeholder={this.props.placeholder || ''}
          disabled={!!this.props.disabled}
          value={this.value()}/>

        <ul className="TextEditor-controls Composer-footer">
          {listItems(this.controlItems().toArray())}
        </ul>
      </div>
    );
  }

  /**
   * Configure the textarea element.
   *
   * @param {DOMElement} element
   * @param {Boolean} isInitialized
   */
  configTextarea(element, isInitialized) {
    if (isInitialized) return;

    const handler = () => {
      this.onsubmit();
      m.redraw();
    };

    $(element).bind('keydown', 'meta+return', handler);
    $(element).bind('keydown', 'ctrl+return', handler);
  }

  /**
   * Build an item list for the text editor controls.
   *
   * @return {ItemList}
   */
  controlItems() {
    const items = new ItemList();

    items.add('submit',
      Button.component({
        children: this.props.submitLabel,
        icon: 'check',
        className: 'Button Button--primary',
        itemClassName: 'App-primaryControl',
        onclick: this.onsubmit.bind(this)
      })
    );

    if (this.props.preview) {
      items.add('preview',
        Button.component({
          icon: 'eye',
          className: 'Button Button--icon',
          onclick: this.props.preview
        })
      );
    }

    return items;
  }

  /**
   * Set the value of the text editor.
   *
   * @param {String} value
   */
  setValue(value) {
    this.$('textarea').val(value).trigger('input');
  }

  /**
   * Set the selected range of the textarea.
   *
   * @param {Integer} start
   * @param {Integer} end
   */
  setSelectionRange(start, end) {
    const $textarea = this.$('textarea');

    $textarea[0].setSelectionRange(start, end);
    $textarea.focus();
  }

  /**
   * Get the selected range of the textarea.
   *
   * @return {Array}
   */
  getSelectionRange() {
    const $textarea = this.$('textarea');

    return [$textarea[0].selectionStart, $textarea[0].selectionEnd];
  }

  /**
   * Insert content into the textarea at the position of the cursor.
   *
   * @param {String} insert
   */
  insertAtCursor(insert) {
    const textarea = this.$('textarea')[0];
    const value = this.value();
    const index = textarea ? textarea.selectionStart : value.length;

    this.setValue(value.slice(0, index) + insert + value.slice(index));

    // Move the textarea cursor to the end of the content we just inserted.
    if (textarea) {
      const pos = index + insert.length;
      this.setSelectionRange(pos, pos);
    }
  }

  /**
   * Handle input into the textarea.
   *
   * @param {String} value
   */
  oninput(value) {
    this.value(value);

    this.props.onchange(this.value());

    m.redraw.strategy('none');
  }

  /**
   * Handle the submit button being clicked.
   */
  onsubmit() {
    this.props.onsubmit(this.value());
  }
}
