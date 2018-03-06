/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import Modal from 'flarum/components/Modal';

export default class AddExtensionModal extends Modal {
  className() {
    return 'AddExtensionModal Modal--small';
  }

  title() {
    return app.translator.trans('core.admin.add_extension.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <p>{app.translator.trans('core.admin.add_extension.temporary_text')}</p>
        <p>{app.translator.trans('core.admin.add_extension.install_text', {a: <a href="https://discuss.flarum.org/t/extensions" target="_blank"/>})}</p>
        <p>{app.translator.trans('core.admin.add_extension.developer_text', {a: <a href="http://flarum.org/docs/extend" target="_blank"/>})}</p>
      </div>
    );
  }
}
