<h1 align="center">PHP School Workshop Manager</h1>

<p align="center">
A tool to manage your PHP School workshops. Search, install, update & remove workshops with ease!
</p>

<p align="center">
<a href="https://travis-ci.org/php-school/workshop-manager">
    <img src="https://img.shields.io/travis/php-school/workshop-manager/master.svg?style=flat-square&label=Linux">
</a>
<a href="https://codecov.io/github/php-school/workshop-manager">
    <img src="https://img.shields.io/codecov/c/github/php-school/workshop-manager.svg?style=flat-square">
</a>
<a href="https://scrutinizer-ci.com/g/php-school/workshop-manager/">
    <img src="https://img.shields.io/scrutinizer/g/php-school/workshop-manager.svg?style=flat-square">
</a>
<a href="https://phpschool-team.slack.com/messages">
    <img src="https://phpschool.herokuapp.com/badge.svg">
</a>
</p>
----

## Installation

```
curl https://php-school.github.io/workshop-manager/workshop-manager.phar
mv workshop-manager.phar /usr/local/bin/workshop-manager
chmod +x /usr/local/bin/workshop-manager
workshop-manager verify
```

This snippet downloads the phar package (an executable PHP package) and moves it to a common install directory, makes it executable and then runs the workshop manager's verify command. You should see some green success messages if everything went will.


## Usage / Commands

### Search | Find

Quickly find available workshops by part of its name and get an instant indication if they're already installed.

```sh
 workshop-manager search php
 
 # Result
  *** Matches ***
 
 +----------------+----------------------------------------------------+-------------+-----------+----------+------------+
 | Name           | Description                                        | Code        | Type      | Level    | Installed? |
 +----------------+----------------------------------------------------+-------------+-----------+----------+------------+
 | PHP7 way       | To play with PHP7                                  | php7way     | Community |          |     ✘      |
 | Learn You PHP! | An introduction to PHP's core features: i/o, http, | learnyouphp | Core      |          |     ✘      |
 |                | arrays, exceptions and so on.                      |             |           |          |            |
 +----------------+----------------------------------------------------+-------------+-----------+----------+------------+

```

### Install

Install a workshop with its package field, you can find this by doing a search like above. 

```sh
 workshop-manager install learnyouphp
```

You can then get started on your workshop instantly by using its package name, in this case you would just run `learnyouphp` on the terminal. 

_*Tip:* If an error ever occurs and your not sure what it is, run it with `-vvv` to get more details or create an issue for us to look at_

### Update

A simple way to update a workshop you already have installed. As workshops are just packages they may include bugs :scream: so keeping them up to date is important!

```sh
 workshop-manager update learnyouphp
```

### Uninstall | Remove

Remove a workshop by its package name.

```sh
 workshop-manager uninstall learnyouphp
```

### Installed

List the installed workshops, just so you know what you can get working on :wink:

It will also let you know if you need to update any workshops that you already have installed.

```sh
 workshop-manager installed
 
 # Result
 *** Installed Workshops ***
 
 +----------------+----------------------------------------------------+-------------+------+----------+---------+------------------------+
 | Name           | Description                                        | Code        | Type | Level    | Version | New version available? |
 +----------------+----------------------------------------------------+-------------+------+----------+---------+------------------------+
 | Learn You PHP! | An introduction to PHP's core features: i/o, http, | learnyouphp | Core |          | 0.3.1   | Nope!                  |
 |                | arrays, exceptions and so on.                      |             |      |          |         |                        |
 +----------------+----------------------------------------------------+-------------+------+----------+---------+------------------------+

```

### Self-update

Keeping the workshop manager up to date is just as important as updated the workshops themselves. We're not perfect and there are probably bugs, or maybe we'll launch some uber cool features, either way you should run this on a regular basis.

```sh
 workshop-manager self-update
```

You can then continue using the workshop manager as you were before.

### Rollback

Something go horribly wrong after that `self-update` ? No worries we got your back :wink: just run...

```sh
 workshop-manager rollback
```

Then you might want to create an issue for that horrible bug you found :joy:

### Verify

You might need to verify your installation if your running into problems. This command will help diagnose those issues and point you in the right direction. 

```sh 
 workshop-manager verify
```

## Contributing

We welcome all contributions, new workshops, typos, feature requests bug fixes and so on. 

To contribute to the code just clone the project, do your changes and make sure all the tests run.

### Adding Workshops

We'd love to add your workshop so it can be installed with the workshop manager. To do so just fill in [the form](https://phpschool.io/submit) and let us take a look at things, we'll be pretty quick at getting it added!


### Deveopment Executable

Whilst developing awesome new features you'll want to use the source executable and not a built phar file. To do so just run the following...

```sh
php bin/workshop-manager
```

### Testing

Hmmmmm tests, we all love a good test! To run the suite use PHPUnit like so... 

```sh
composer test
```

_<p align="center">Made with :heart: by the PHPSchool Team</p>_
