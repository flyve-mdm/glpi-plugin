---
title: CONTRIBUTING
robots: noindex, nofollow
description: contribute guidelines
tags: contributing, GitHub
---

# How to contribute to Flyve MDM

Welcome to our ever-growing community :octocat:!

We are more than happy to accept external contributions to the project in the form of feedback, translations, bug reports and even better, pull requests.

We present you the guidelines to start contributing in any of the Flyve MDM projects.

# Table of content:

1. See what's going on! 
1.1 Issue Dashboard
1.2 Pull Request Dashboard
2. Assistance
2.1 Live Support
2.2 Technical Questions
2.3 Discussion
2.4 Customers Assistance
3. Feature Requests
3.1 Requirement for a Feature Request
3.1.1 Major Feature Request
3.1.2 Minor Feature Request
3.2 _Request a New_ Feature
3.3 _Implement a New_ Feature
4. Submitting
4.1 How to Submit an Issue.
4.1.1 Check for Past Issues.
4.1.2 Information Needed.
4.1.3 Submit an Issue.
4.2 How to Create a Pull Request (PR).
4.2.1 Create a Branch
4.2.2 Make Changes
4.2.3 Commit Your Changes
4.2.3.1 Rules to Follow
4.2.3.2 Commit Format
4.2.3.2.1 Header: Writting a <type>.
4.2.3.2.2 Header: Writting the (<optional scope>).
4.2.3.2.3 Header: Writting a <description>
4.2.3.2.4 Header Lenght
4.2.3.2.5 Writting the <optional body>.
4.2.3.2.6 Writting the <optional footer>.
4.2.3.3 Commit Examples
4.2.4 Push your Changes
4.2.5 Create a Pull Request
4.2.5.1 How to Write a Title for a Pull Request.
4.2.5.2 How to Write a Description for a Pull Request.
4.2.5.1 Additional Information.
4.2.6 How to proceed with suggestions.
5. What to do next?
6. Coding Rules

# 1. See what's going on!

:fire: 
## 1.1 Issue Dashboard
If you want to know all the issues we're dealing with right now, take a look at our [glpi-plugin Issue Dashboard](https://github.com/flyve-mdm/glpi-plugin/issues) and look for areas in which you can help.

:fire_engine:
## 1.2 Pull Request Dashboard
If you want to give us a hand solving issues then great, take a look at our [glpi-plugin Pull Request Dashboard](https://github.com/flyve-mdm/glpi-plugin/issues) and check for an open or closed PR. We don’t want to duplicate efforts.

# 2. Assistance

:loudspeaker:
## 2.1 Live Support
You can find us in [Telegram](https://t.me/flyvemdm), we'll help you as soon as possible.

:closed_book:
## 2.2 Technical Questions
For general technical questions, post an appropriately tagged question on [StackOverflow](http://stackoverflow.com/).

:speech_balloon:
## 2.3 Discussion
For general discussion, use the [Flyve-MDM mailing list](http://mail.ow2.org/wws/info/flyve-mdm-dev).

:ear:
## 2.4 Customers Assistance
Use our official [support channel](https://support.teclib.com/).

# 3. Feature Requests

## 3.1 Requirement for a Feature Request

### 3.1.1 Major Feature Request
For a major new feature request, [open an Issue](https://github.com/flyve-mdm/glpi-plugin/issues/new) and outline your proposal so it can be discussed.

### 3.1.2 Minor Feature Request
For a minor new feature request, you can craft it and directly [submit it as a Pull Request](https://github.com/flyve-mdm/glpi-plugin/pulls), we'll take care of it.

## 3.2 _Request a New_ Feature
You can request a new feature by [submitting an Issue](https://github.com/flyve-mdm/glpi-plugin/issues/new)

## 3.3 _Implement a New_ Feature
If you like to _implement_ a new feature please [submit an Issue](https://github.com/flyve-mdm/glpi-plugin/issues/new) with a proposal, so we can be sure it's relevant.

# 4. Submitting

## 4.1 How to Submit an Issue.

### 4.1.1 Check for Past Issues.
Before submitting the issue please check the [Issue Tracker](https://github.com/flyve-mdm/glpi-plugin/issues/), maybe the bug was already reported by another contributor. By doing this you help us maximize the effort spent on solving problems and the addition of new features.

### 4.1.2 Information Needed.
We require the following information:

* :warning: **Observed Results:** A brief description of the problem.
* :boom: **Expected Results:** What did you expect to happen?

### 4.1.3 :rocket: Submit an Issue.
Having all data and hand, file the new issue by filling out our [Issue form](https://github.com/flyve-mdm/glpi-plugin/issues/new).
### That's it! :tada:

## 4.2 How to Create a Pull Request (PR).

Before submitting your Pull Request check for an open or closed PR that relates to your submission. We don't want to duplicate efforts.

### 4.2.1 Create a Branch

Create a new branch before committing any changes. A _branch is a parallel version of a repository._ It is contained within the repository but does not affect the **`primary or master` branch**. Name it anything _except master, develop, release-*, or hotfix-*_. For the educational purpose in our examples, we'll use **`created-branch`**.

:no_entry_sign: **Important:** Do not commit to our default **`develop`** branch.

### 4.2.2 Make Changes


Make your changes in your **newly created** branch, the project is organized according to the branch model [Git Flow.](http://git-flow.readthedocs.io/en/latest/)

```console
    git checkout -b created-branch develop
```

### 4.2.3 Commit Your Changes
A commit, or "revision", is an individual change to a file (or set of files). It's like when you save a file, except with Git, every time you save it creates a unique ID (a.k.a. the "SHA" or "hash") that allows you to keep a record of what changes were made when and by who. Commits usually contain a commit message which is a brief description of what changes were made.

### 4.2.3.1 Rules to Follow
For commits, we follow the [Conventional Commit](http://conventionalcommits.org/). This leads to **more readable messages** that are easy to follow when looking through the project history. But also, we use the git commit messages to **automatically generate changelogs** from these messages.

### 4.2.3.2 Commit Format
Each commit message consists of a **header**, a **body** and a **footer**.  The header has a special
format that includes a **type**, a **scope** and a **description**:

The commit message should be structured as follows:

```
<type>(<optional scope>): <description>
<BLANK LINE>
<optional body>
<BLANK LINE>
<optional footer>
```

### 4.2.3.2.1 Header: Writting a <type>.
 

>[color=#65c9c0] :point_right:<type>:point_left:(<optional scope>): <description>
<BLANK LINE>
<optional body>
<BLANK LINE>
<optional footer>
    
Commits must be prefixed with a type, which consists of a verb, **feat, fix, build,** followed by a colon and space.

**Your options:**

* **build**: Changes that affect the build system or external dependencies (example scopes: gulp, broccoli, npm).
* **ci**: Changes to our CI configuration files and scripts (example scopes: Travis, Circle, BrowserStack, SauceLabs).
* **docs**: Documentation only changes.
* **feat**: A new feature.
* **fix**: A bug fix.
* **perf**: A code change that improves performance.
* **refactor**: A code change that neither fixes a bug or adds a feature.
* **style**: Changes that do not affect the meaning of the code (white-space, formatting, missing semi-colons, etc).
* **test**: Adding missing tests or correcting existing tests.

---
> [color=#168784] **Example for <type>:**
> :point_right:feat:point_left:(parser): add ability to parse arrays
---
    
### 4.2.3.2.2 Header: Writting the (<optional scope>).

>[color=#65c9c0] <type>:point_right:**(<optional scope>)**:point_left:: <description>
<BLANK LINE>
<optional body>
<BLANK LINE>
<optional footer>

A scope (optional) may be provided to a commit’s type, to provide additional contextual information and is contained in parenthesis.

---
> [color=#168784] **Example for a (<optional scope>):**
> feat:point_right:(parser):point_left:: add ability to parse arrays 
---

### 4.2.3.2.3 Header: Writting a <description>
A description must immediately follow the **``<type>(<optional scope>):``** The description is a short description of the pull request.

**Important**
* Use the imperative, present tense: "change" not "changed" nor "changes".
* Don't capitalize the first letter.
* Do not use a dot (.) at the end.

---
> [color=#168784] **Example for <description>**:
> feat(parser)::point_right:add ability to parse arrays:point_left:
---

### 4.2.3.2.4 Header Lenght

The **header** cannot be longer than 100 characters. This allows the message to be easier to read on GitHub as well as in various git tools.

### 4.2.3.2.5 Writting the <optional body>.

>[color=#65c9c0] <type>(<optional scope>):<description>
<BLANK LINE>
:point_right:**<optional body>**:point_left:
<BLANK LINE>
<optional footer>

The body should include the motivation for the change and contrast this with previous behavior.

---
> [color=#168784] **Example for <optional body>**:
fix orthography
remove out of date paragraph
fix broken links
---

### 4.2.3.2.6 Writting the <optional footer>.

>[color=#65c9c0] <type>(<optional scope>):<description>
<BLANK LINE>
<optional body>
<BLANK LINE>
:point_right:**<optional footer>**:point_left:

The <optional footer> should contain a [closing reference to an issue](https://help.github.com/articles/closing-issues-using-keywords/) if any.

For example, to close an issue numbered **`123`**, you could use the phrases **`Closes #123`** in your pull request description or commit message. Once the branch is merged into the default branch, the issue will close.

---
> [color=#168784] **Example for <optional footer>**:
:point_right:Closes #123:point_left:
---

### 4.2.3.3 Commit Examples
:shit:
**Bad**

```console
docs(readme): fix orthography, remove out of date paragraph and fix broken links
```
:+1:
**Good**

```console
docs(readme): document design improvement change content

fix orthography
remove out of date paragraph
fix broken links
```

### 4.2.4 Push your Changes
Pushing refers to **sending your committed changes to a remote repository**, such as a repository hosted on GitHub. For instance, if you change something locally, you'd want to then push those changes so that others may access them.

After working on your changes you need to Push it (upload) your **newly created branch** to GitHub

```console
    git push origin created-branch
```

### 4.2.5 Create a Pull Request

Pull requests or PR are **proposed changes** to a repository submitted by a user and accepted or rejected by a repository's collaborators.

After all the work being pushed to the newly created branch, In GitHub, send a pull request to our [repository.](https://github.com/flyve-mdm/glpi-plugin/pulls)

### 4.2.5.1 How to Write a Title for a Pull Request.

:straight_ruler:
**Title Lenght**
Keep it concise and don't write more than **50 characters** in the title.

:construction:
**For Work in Progress (WIP)**
If you don’t want your PR to be merged accidentally, add the word "wip" or "WIP" to its title and the [WIP bot](https://github.com/apps/wip) will set its status to error.

---
> [color=#168784] **Example: Titles for work in progress (WIP)**
WIP Contribution Guideline Improvement.
---

:white_check_mark:
**Finalized Work**
If you are done with your work and want it to be merged, just write a descriptive title with no more than 50 characters.

---
> [color=#168784] **Example: Titles for Finalized Work**
Contribution Guideline Improvement.
---

Keep in mind that the Pull Request should be named in reference to the main fix or feature you provide, minor information can be added to the description.

### 4.2.5.2 How to Write a Description for a Pull Request.

We provide a [template](https://github.com/flyve-mdm/glpi-plugin/community) for Pull Request descriptions. When you're creating a Pull Request it'll be shown automatically. Just fill it out and you're done.

### 4.2.5.1 Additional Information.

:page_with_curl:
**Choose the right label**: Look at the [list of available labels.](https://github.com/flyve-mdm/glpi-plugin/issues/labels)

### 4.2.6 How to proceed with suggestions.

In case your contribution has to do with reports, remember those are created in the develop branch, nor master or PR's.

* If we suggest changes then:

* Make the required updates.

* Rebase your branch and force push to your GitHub repository (this will update your Pull Request)

* Remove the WIP label

# 5. What to do next?

After your pull request is merged, you can safely delete your branch and pull the changes
from the main (upstream) repository:

* Delete the remote branch on GitHub either through the GitHub web UI or your local shell as follows:

    ```shell
    git push origin --delete created-branch
    ```

* Check out the develop branch:

    ```shell
    git checkout develop -f
    ```

* Delete the local branch:

    ```shell
    git branch -D created-branch
    ```

* Update your master with the latest upstream version:

    ```shell
    git pull --ff upstream master
    ```

# 6. Coding Rules

To ensure consistency throughout the source code, keep these rules in mind as you are working:

* All features or bug fixes must be [tested](#test-and-build) by one or more specs (unit-tests).
* All methods must be documented.


# Good luck! :tada: