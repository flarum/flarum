import Component from 'flarum/Component';

/**
 * The `LoadingIndicator` component displays a loading spinner with spin.js. It
 * may have the following special props:
 *
 * - `size` The spin.js size preset to use. Defaults to 'small'.
 *
 * All other props will be assigned as attributes on the element.
 */
export default class LoadingIndicator extends Component {
  view() {
    const attrs = Object.assign({}, this.props);

    attrs.className = 'LoadingIndicator ' + (attrs.className || '');
    delete attrs.size;

    return <div {...attrs}>{m.trust('&nbsp;')}</div>;
  }

  config() {
    const size = this.props.size || 'small';

    $.fn.spin.presets[size].zIndex = 'auto';
    this.$().spin(size);
  }
}
