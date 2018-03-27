import Button from 'flarum/components/Button';

/**
 * The `LogInButton` component displays a social login button which will open
 * a popup window containing the specified path.
 *
 * ### Props
 *
* - `path`
 */
export default class LogInButton extends Button {
  static initProps(props) {
    props.className = (props.className || '') + ' LogInButton';

    props.onclick = function() {
      const width = 600;
      const height = 400;
      const $window = $(window);

      window.open(app.forum.attribute('baseUrl') + props.path, 'logInPopup',
        `width=${width},` +
        `height=${height},` +
        `top=${$window.height() / 2 - height / 2},` +
        `left=${$window.width() / 2 - width / 2},` +
        'status=no,scrollbars=no,resizable=no');
    };

    super.initProps(props);
  }
}
