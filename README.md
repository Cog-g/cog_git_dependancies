# cog_Git_Dependancies

This script use a json file to get all needed repositories from git, and check for updates.


## Installation

I recommand to install it on /var/www
So, you will have two files :
  /var/www/cog_dependance.php
  /var/www/cog_dependance.json

The Json file contains all the repositories with
- url
- version
- path

### URL

It is the url of the repository.

### Version

Could be a branch or a tag. If it is empty, master branch will be used.

### Path

The path where you want to clone your repository.


## Run

Simply run a command like :

  php cog_dependance.php install

Will install all needed repositories in the Json (if the repo is not present yet).

  php cog_dependance.php udpate

Will check for new version __(without install them)__
*You can set a crontask to send you the result of this command each day/week/whatever.*

  php cog_dependance.php upgrade

Will install any update.