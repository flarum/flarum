import Component from 'flarum/Component';
import avatar from 'flarum/helpers/avatar';
import icon from 'flarum/helpers/icon';
import listItems from 'flarum/helpers/listItems';
import ItemList from 'flarum/utils/ItemList';
import Button from 'flarum/components/Button';
import LoadingIndicator from 'flarum/components/LoadingIndicator';

/**
 * The `AvatarEditor` component displays a user's avatar along with a dropdown
 * menu which allows the user to upload/remove the avatar.
 *
 * ### Props
 *
 * - `className`
 * - `user`
 */
export default class AvatarEditor extends Component {
  init() {
    /**
     * Whether or not an avatar upload is in progress.
     *
     * @type {Boolean}
     */
    this.loading = false;
  }

  static initProps(props) {
    super.initProps(props);

    props.className = props.className || '';
  }

  view() {
    const user = this.props.user;

    return (
      <div className={'AvatarEditor Dropdown ' + this.props.className + (this.loading ? ' loading' : '')}>
        {avatar(user)}
        <a className={ user.avatarUrl() ? "Dropdown-toggle" : "Dropdown-toggle AvatarEditor--noAvatar" }
          title={app.translator.trans('core.forum.user.avatar_upload_tooltip')}
          data-toggle="dropdown"
          onclick={this.quickUpload.bind(this)}>
          {this.loading ? LoadingIndicator.component() : (user.avatarUrl() ? icon('pencil') : icon('plus-circle'))}
        </a>
        <ul className="Dropdown-menu Menu">
          {listItems(this.controlItems().toArray())}
        </ul>
      </div>
    );
  }

  /**
   * Get the items in the edit avatar dropdown menu.
   *
   * @return {ItemList}
   */
  controlItems() {
    const items = new ItemList();

    items.add('upload',
      Button.component({
        icon: 'upload',
        children: app.translator.trans('core.forum.user.avatar_upload_button'),
        onclick: this.upload.bind(this)
      })
    );

    items.add('remove',
      Button.component({
        icon: 'times',
        children: app.translator.trans('core.forum.user.avatar_remove_button'),
        onclick: this.remove.bind(this)
      })
    );

    return items;
  }

  /**
   * If the user doesn't have an avatar, there's no point in showing the
   * controls dropdown, because only one option would be viable: uploading.
   * Thus, when the avatar editor's dropdown toggle button is clicked, we prompt
   * the user to upload an avatar immediately.
   *
   * @param {Event} e
   */
  quickUpload(e) {
    if (!this.props.user.avatarUrl()) {
      e.preventDefault();
      e.stopPropagation();
      this.upload();
    }
  }

  /**
   * Prompt the user to upload a new avatar.
   */
  upload() {
    if (this.loading) return;

    // Create a hidden HTML input element and click on it so the user can select
    // an avatar file. Once they have, we will upload it via the API.
    const user = this.props.user;
    const $input = $('<input type="file">');

    $input.appendTo('body').hide().click().on('change', e => {
      const data = new FormData();
      data.append('avatar', $(e.target)[0].files[0]);

      this.loading = true;
      m.redraw();

      app.request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/avatar',
        serialize: raw => raw,
        data
      }).then(
        this.success.bind(this),
        this.failure.bind(this)
      );
    });
  }

  /**
   * Remove the user's avatar.
   */
  remove() {
    const user = this.props.user;

    this.loading = true;
    m.redraw();

    app.request({
      method: 'DELETE',
      url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/avatar'
    }).then(
      this.success.bind(this),
      this.failure.bind(this)
    );
  }

  /**
   * After a successful upload/removal, push the updated user data into the
   * store, and force a recomputation of the user's avatar color.
   *
   * @param {Object} response
   * @protected
   */
  success(response) {
    app.store.pushPayload(response);
    delete this.props.user.avatarColor;

    this.loading = false;
    m.redraw();
  }

  /**
   * If avatar upload/removal fails, stop loading.
   *
   * @param {Object} response
   * @protected
   */
  failure(response) {
    this.loading = false;
    m.redraw();
  }
}
