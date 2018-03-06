import Component from 'flarum/Component';

export default class TagHero extends Component {
  view() {
    const tag = this.props.tag;
    const color = tag.color();

    return (
      <header className={'Hero TagHero' + (color ? ' TagHero--colored' : '')}
        style={color ? {color: '#fff', backgroundColor: color} : ''}>
        <div className="container">
          <div className="containerNarrow">
            <h2 className="Hero-title">{tag.name()}</h2>
            <div className="Hero-subtitle">{tag.description()}</div>
          </div>
        </div>
      </header>
    );
  }
}
