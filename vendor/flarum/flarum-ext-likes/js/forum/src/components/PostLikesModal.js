import Modal from 'flarum/components/Modal';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';

export default class PostLikesModal extends Modal {
  className() {
    return 'PostLikesModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-likes.forum.post_likes.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <ul className="PostLikesModal-list">
          {this.props.post.likes().map(user => (
            <li>
              <a href={app.route.user(user)} config={m.route}>
                {avatar(user)} {' '}
                {username(user)}
              </a>
            </li>
          ))}
        </ul>
      </div>
    );
  }
}
