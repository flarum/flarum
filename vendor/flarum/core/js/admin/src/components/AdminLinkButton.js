/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import LinkButton from 'flarum/components/LinkButton';

export default class AdminLinkButton extends LinkButton {
  getButtonContent() {
    const content = super.getButtonContent();

    content.push(
      <div className="AdminLinkButton-description">
        {this.props.description}
      </div>
    );

    return content;
  }
}
