import Button from 'flarum/components/Button';

export default class UploadImageButton extends Button {
  init() {
    this.loading = false;
  }

  view() {
    this.props.loading = this.loading;
    this.props.className = (this.props.className || '') + ' Button';

    if (app.data.settings[this.props.name + '_path']) {
      this.props.onclick = this.remove.bind(this);
      this.props.children = app.translator.trans('core.admin.upload_image.remove_button');

      return (
        <div>
          <p><img src={app.forum.attribute(this.props.name+'Url')} alt=""/></p>
          <p>{super.view()}</p>
        </div>
      );
    } else {
      this.props.onclick = this.upload.bind(this);
      this.props.children = app.translator.trans('core.admin.upload_image.upload_button');
    }

    return super.view();
  }

  /**
   * Prompt the user to upload an image.
   */
  upload() {
    if (this.loading) return;

    const $input = $('<input type="file">');

    $input.appendTo('body').hide().click().on('change', e => {
      const data = new FormData();
      data.append(this.props.name, $(e.target)[0].files[0]);

      this.loading = true;
      m.redraw();

      app.request({
        method: 'POST',
        url: this.resourceUrl(),
        serialize: raw => raw,
        data
      }).then(
        this.success.bind(this),
        this.failure.bind(this)
      );
    });
  }

  /**
   * Remove the logo.
   */
  remove() {
    this.loading = true;
    m.redraw();

    app.request({
      method: 'DELETE',
      url: this.resourceUrl()
    }).then(
      this.success.bind(this),
      this.failure.bind(this)
    );
  }

  resourceUrl() {
    return app.forum.attribute('apiUrl') + '/' + this.props.name;
  }

  /**
   * After a successful upload/removal, reload the page.
   *
   * @param {Object} response
   * @protected
   */
  success(response) {
    window.location.reload();
  }

  /**
   * If upload/removal fails, stop loading.
   *
   * @param {Object} response
   * @protected
   */
  failure(response) {
    this.loading = false;
    m.redraw();
  }
}
