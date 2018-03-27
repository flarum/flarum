import Component from 'flarum/Component';
import IndexPage from 'flarum/components/IndexPage';
import listItems from 'flarum/helpers/listItems';
import humanTime from 'flarum/helpers/humanTime';
import icon from 'flarum/helpers/icon';

import tagLabel from 'flarum/tags/helpers/tagLabel';
import sortTags from 'flarum/tags/utils/sortTags';

export default class TagsPage extends Component {
  init() {
    this.tags = sortTags(app.store.all('tags').filter(tag => !tag.parent()));

    app.current = this;
    app.history.push('tags', icon('th-large'));
    app.drawer.hide();
    app.modal.close();
  }

  view() {
    const pinned = this.tags.filter(tag => tag.position() !== null);
    const cloud = this.tags.filter(tag => tag.position() === null);

    return (
      <div className="TagsPage">
        {IndexPage.prototype.hero()}
        <div className="container">
          <nav className="TagsPage-nav IndexPage-nav sideNav" config={IndexPage.prototype.affixSidebar}>
            <ul>{listItems(IndexPage.prototype.sidebarItems().toArray())}</ul>
          </nav>

          <div className="TagsPage-content sideNavOffset">
            <ul className="TagTiles">
              {pinned.map(tag => {
                const lastDiscussion = tag.lastDiscussion();
                const children = sortTags(app.store.all('tags').filter(child => child.parent() === tag));

                return (
                  <li className={'TagTile ' + (tag.color() ? 'colored' : '')}
                    style={{backgroundColor: tag.color()}}>
                    <a className="TagTile-info" href={app.route.tag(tag)} config={m.route}>
                      <h3 className="TagTile-name">{tag.name()}</h3>
                      <p className="TagTile-description">{tag.description()}</p>
                      {children
                        ? (
                          <div className="TagTile-children">
                            {children.map(child => [
                              <a href={app.route.tag(child)} config={function(element, isInitialized) {
                                if (isInitialized) return;
                                $(element).on('click', e => e.stopPropagation());
                                m.route.apply(this, arguments);
                              }}>
                                {child.name()}
                              </a>,
                              ' '
                            ])}
                          </div>
                        ) : ''}
                    </a>
                    {lastDiscussion
                      ? (
                        <a className="TagTile-lastDiscussion"
                          href={app.route.discussion(lastDiscussion, lastDiscussion.lastPostNumber())}
                          config={m.route}>
                          <span className="TagTile-lastDiscussion-title">{lastDiscussion.title()}</span>
                          {humanTime(lastDiscussion.lastTime())}
                        </a>
                      ) : (
                        <span className="TagTile-lastDiscussion"/>
                      )}
                  </li>
                );
              })}
            </ul>

            {cloud.length ? (
              <div className="TagCloud">
                {cloud.map(tag => {
                  const color = tag.color();

                  return [
                    tagLabel(tag, {link: true}),
                    ' '
                  ];
                })}
              </div>
            ) : ''}
          </div>
        </div>
      </div>
    );
  }
}
