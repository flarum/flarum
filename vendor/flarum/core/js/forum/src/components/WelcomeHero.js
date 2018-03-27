import Component from 'flarum/Component';
import Button from 'flarum/components/Button';

/**
 * The `WelcomeHero` component displays a hero that welcomes the user to the
 * forum.
 */
export default class WelcomeHero extends Component {
  init() {
    this.hidden = localStorage.getItem('welcomeHidden');
  }

  view() {
    if (this.hidden) return <div/>;

    const slideUp = () => {
      this.$().slideUp(this.hide.bind(this));
    };

    return (
      <header className="Hero WelcomeHero">
        <div class="container">
          {Button.component({
            icon: 'times',
            onclick: slideUp,
            className: 'Hero-close Button Button--icon Button--link'
          })}

          <div className="containerNarrow">
            <h2 className="Hero-title">{app.forum.attribute('welcomeTitle')}</h2>
            <div className="Hero-subtitle">{m.trust(app.forum.attribute('welcomeMessage'))}</div>
          </div>
        </div>
      </header>
    );
  }

  /**
   * Hide the welcome hero.
   */
  hide() {
    localStorage.setItem('welcomeHidden', 'true');

    this.hidden = true;
  }
}
