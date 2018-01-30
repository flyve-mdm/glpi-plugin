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

1. See what’s going on!
1.1 [Issue Dashboard](#issue-dashboard)
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
3.2 Request a New Feature
3.3 Implement a New Feature
4. Submitting
4.1 Submitting an Issue.
4.1.1 Check for Past Issues.
4.1.2 Information Needed.
4.1.3 Information Needed.
















# 1. See what's going on!

### :fire: 
### 1.1 Issue Dashboard
If you want to know all the issues we're dealing with right now, take a look to our [glpi-plugin Issue Dashboard](../issues) and look for areas in which you can help.

### :fire_engine:
### 1.2 Pull Request Dashboard
If you want to give us a hand solving issues then great, take a look to our [glpi-plugin Pull Request Dashboard](../pulls) and check for an open or closed PR. We don’t want to duplicate efforts.

# 2. Assistance

### :loudspeaker:
## 2.1 Live Support
You can find us in [Telegram](https://t.me/flyvemdm), we'll help you as soon as possible.

### :closed_book:
## 2.2 Technical Questions
For general technical questions, post an appropriately tagged question on [StackOverflow](http://stackoverflow.com/).

### :speech_balloon:
## 2.3 Discussion
For general discussion, use the [Flyve-MDM mailing list](http://mail.ow2.org/wws/info/flyve-mdm-dev).

### :ear:
## 2.4 Customers Assistance
Use our official [support channel](https://support.teclib.com/).

# 3. Feature Requests

## 3.1 Requirement for a Feature Request

### 3.1.1 Major Feature Request
For a major new feature request, [open an Issue](../issues/new) and outline your proposal so it can be discussed.

### 3.1.2 Minor Feature Request
For a minor new feature request, you can craft it and directly submit it as a Pull Request, we'll take care of it.

## 3.2 _Request a New_ Feature
You can request a new feature by [submitting an Issue](../issues/new)

## 3.3 _Implement a New_ Feature
If you like to _implement_ a new feature please [submit an Issue](../issues/new) with a proposal, so we can be sure it's relevant.

# 4. Submitting

## 4.1 How to Submit an Issue.

### 4.1.1 Check for Past Issues.
Before submitting the issue please check the [Issue Tracker](https://github.com/flyve-mdm//issues), maybe the bug was already reported by another contributor. By doing this you help us maximize the effort spent on solving problems and the addition of new features.

### 4.1.2 Information Needed.
We require the following information:

* :warning: **Observed Results:** A brief description of the problem.
* :boom: **Expected Results:** What did you expect to happen?
* :mag: **Relevant Code:** Code that you find relevant with this issue. You can use [Gist](https://gist.github.com/).
* :computer: **Context:** Provide context, project version, OS version, device model, etc.
* :bulb: **Suggest a Fix:** Point out what might be causing the problem.

### 4.1.3 :rocket: Submit an Issue.
Having all data and hand, file the new issue by filling out our [Issue form](../issues/new).
### That's it! :tada:

## 4.2 How to Create a Pull Request (PR).

Before submitting your Pull Request check for an open or closed PR that relates to your submission. We don't want to duplicate efforts.

### 4.2.1 Create a Branch (PR)

Create a new branch before committing any changes. Name it anything except _master, develop, release-*, or hotfix-*_.

:no_entry_sign: **Important:** Please, Do not commit to our default **`develop`** branch.

### 4.2.2 Make Changes (PR)


Make your changes in a new branch, the project is organized according to the branch model [Git Flow.](http://git-flow.readthedocs.io/en/latest/)

```console
    git checkout -b my-fix-branch develop
```

### 4.2.3 Commit Your Changes

#### 4.2.3.1 Commit Description
Commit your changes using a descriptive commit message that follows the [Conventional Commit](http://conventionalcommits.org/). This leads to **more readable messages** that are easy to follow when looking through the project history. But also, we use the git commit messages to **automatically generate changelogs** from these messages.

#### 4.2.3.2 Commit Message Format
Each commit message consists of a **header**, a **body** and a **footer**.  The header has a special
format that includes a **type**, a **scope** and a **subject**:

```
<type>(<scope>): <subject>
<BLANK LINE>
<body>
<BLANK LINE>
<footer>
```

The **header** is mandatory and the **scope** of the header is optional.
#### 4.2.3.3 Commit Message Lenght
:exclamation:Any line of the commit message **cannot be longer 100 characters!** This allows the message to be easier to read on GitHub as well as in various git tools.

Footer should contain a [closing reference to an issue](https://help.github.com/articles/closing-issues-via-commit-messages/) if any.

#### 4.2.3.4 Examples
:shit:
**Bad**

```console
docs(readme): fix orthography, remove out of date paragraph and fix broken links
```
:+1:
**Good**

```console
    docs(readme): change content

    fix orthography
    remove out of date paragraph
    fix broken links
```
### 4.2.3 Commit Your Changes
* Push your branch to GitHub:

```console
    git push origin my-fix-branch
```

* In GitHub, send a pull request to our [Repository](https://github.com/flyve-mdm/).

Keep in mind that the PR should be named in reference to the main fix or feature you provide, minor information can be added to the description.

Use the WIP label while you're working on it, this will prevent us from merging unfinished work.

**Bad**

> WIP Fix errors in installation method, update dependencies and improve installation documentation

**Good**

> WIP Fix installation method

> What's the new behavior?
> 
> * Dependencies updated
> * Documentation improved

Also, avoid using your branch or the commit guidelines to name your PR, for example:

**Bad**

> feat(private): implement private data method

**Good**

> WIP Feature private information

In case your contribution has to do with reports, remember those are created in the develop branch, nor master or PR's.

* If we suggest changes then:

  * Make the required updates.

  * Rebase your branch and force push to your GitHub repository (this will update your Pull Request)

  * Remove the WIP label

# 5. What to do next?

You can safely delete your branch and pull the changes from the main (upstream) repository:

* Delete the remote branch on GitHub either through the GitHub web UI or your local shell as you prefer.

# 6. Coding Rules

To ensure consistency throughout the source code, keep these rules in mind as you are working:

* All features or bug fixes must be [tested](#test-and-build) by one or more specs (unit-tests).
* All methods must be documented.


# Good luck! :tada: