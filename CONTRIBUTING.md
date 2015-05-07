# Contributing to Flarum

Thanks for your interest in contributing to Flarum!

## Stuff You Can Do

- **Report bugs.** If you find a bug, we'd like to know about it. Work out which GitHub repository the bug report belongs to (i.e. [an extension repository](http://github.com/flarum) vs. [the core repository](http://github.com/flarum)) and then give us as much information as you can. Thanks!

- **Give us feedback.** We want to know where Flarum falls short so that we can make it better. Tell us what you think on the [Development Forum](http://discuss.flarum.org) or in [Gitter](http://gitter.im/flarum/flarum).

- **Contribute code.** Take a look at the [issue list](http://github.com/flarum/core/issues) and see if there's anything you can help out with. See below for instructions on submitting a Pull Request.

- **Spread the word.** Tell the world about Flarum!

## Pull Requests

1. Review the [Contributor License Agreement](#contributor-license-agreement).

2. Create a new branch.

  ```sh
  git checkout -b new-flarum-branch
  ```

  > Please implement only one feature/bugfix per branch to keep pull requests clean and focused.

3. Code. 
  - Follow the coding style: [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md). 
  - Include tests and make sure they pass (subject to [#3](https://github.com/flarum/core/issues/3) and [#4](https://github.com/flarum/core/issues/4)).

4. Commit. 
  - Commit messages are **required**. 
  - They should include a short description of the changes on the first line, then a blank line, then more details if necessary. 

5. Clean up. Squash together minor commits.

  ```sh
  git rebase -i
  ```

6. Update your branch so that it is based on top of the latest code from the Flarum repository.

  ```sh
  git fetch origin
  git rebase origin/master
  ```

7. Fork your repository on GitHub and push to it.

  ```sh
  git remote add mine git@github.com:<your user name>/flarum.git
  git push mine new-flarum-branch
  ```

8. Submit a pull request.
  - Go to the Flarum repository you just pushed to (e.g. https://github.com/your-user-name/flarum).
  - Click "Pull Request". 
  - Write your branch name in the branch field. 
  - Click "Update Commit Range". 
  - Ensure that the correct commits and files changes are included. 
  - Fill in a descriptive title and other details about your pull request. 
  - Click "Send pull request".

10. Respond to feedback.
  - We may suggest changes to your code. Maintaining a high standard of code quality is important for the longevity of this project — use it as an opportunity to improve your own skills and learn something new!

## Contributor License Agreement

By contributing your code to Flarum you grant Toby Zerner a non-exclusive, irrevocable, worldwide, royalty-free, sublicenseable, transferable license under all of Your relevant intellectual property rights (including copyright, patent, and any other rights), to use, copy, prepare derivative works of, distribute and publicly perform and display the Contributions on any licensing terms, including without limitation: (a) open source licenses like the MIT license; and (b) binary, proprietary, or commercial licenses. Except for the licenses granted herein, You reserve all right, title, and interest in and to the Contribution.

You confirm that you are able to grant us these rights. You represent that You are legally entitled to grant the above license. If Your employer has rights to intellectual property that You create, You represent that You have received permission to make the Contributions on behalf of that employer, or that Your employer has waived such rights for the Contributions.

You represent that the Contributions are Your original works of authorship, and to Your knowledge, no other person claims, or has the right to claim, any right in any invention or patent related to the Contributions. You also represent that You are not legally obligated, whether by entering into an agreement or otherwise, in any way that conflicts with the terms of this license.

Toby Zerner acknowledges that, except as explicitly described in this Agreement, any Contribution which you provide is on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING, WITHOUT LIMITATION, ANY WARRANTIES OR CONDITIONS OF TITLE, NON-INFRINGEMENT, MERCHANTABILITY, OR FITNESS FOR A PARTICULAR PURPOSE.
