# cog_Git_Dependancies

This script use a json file to get all needed repositories from git, and check for updates.


## Installation

I recommand to install it on /var/www :

	/var/www/cog_dependance/cog_dependance.php

Run the first install :

	$ cd /usr/local
	$ git clone git://github.com/Cog-g/cog_git_dependancies.git
	$ sudo php cog_dependance.php install

If the Json file is not found in /var/www/cog_dependance it will be created.

The Json file contains all the repositories with

- name
- url
- version
- path
- sudo_root
- use_folder

### Json

You can start with a _almost_ blank cog_dependance.json :

	{
		"repositories": []
	}


Here is an example of a Json :

	{
		"repositories": [{
	    "name" : "soda",
			"url": "git://github.com/buymeasoda/soda-theme.git",
			"version": "soda-v1",
			"path" : "/tmp/plugins",
    	"sudo_root" : "false",
    	"use_folder" : "true"
		},{
	    "name" : "tmpl",
			"url": "git://github.com/buymeasoda/tmpl.git",
			"version": "",
			"path" : "/tmp/plugins",
    	"sudo_root" : "false",
    	"use_folder" : "true"
		}]
	}


_(1.5.8)_ You don't need to add the cog_dependance git to your Json, it will check for itself anyway.

### URL

(string) It is the url of the repository.

### Version

(void|string) Could be a branch or a tag. If it is empty, master branch will be used.

### Path

(string) The path where you want to clone your repository.

### Sudo_root (deprecated)

(bool) not used anymore.

### use_folder

(bool) Tells if the folder of the repo should be use or, if the files needs to be directly copied to the specified folder.


## Run

__As root,__ simply run a command like :

	$ sudo php cog_dependance.php install

Will install all needed repositories in the Json (if the repo is not present yet).

	$ sudo php cog_dependance.php udpate

Will check for new version __(without install them)__
*You can set a crontask to send you the result of this command each day/week/whatever.*

	$ sudo php cog_dependance.php upgrade

Will install any update.

## Cron

You can automate checking for updates

	$ crontab -e
	15 3 * * * php /var/www/cog_git_dependancies/cog_dependance.php update cron

Will check every day at 3:15 am and should send an email.
_(1.6.1)_ Adding paramter "cron" to update return data without any color.




### Changelog

- 1.6.1 : . Added a cron value for second argument.
- 1.6.0 : . Make an empty json file if not present and check for the usr.local dir.
- 1.5.8 : . Removed self-update from the json file to add it on the code.
- 1.5.7 : . On copying, create the dir if not present.
- 1.5.6 : . Fixed the copy, only if copy passed on argument or if there is any change.
- 1.5.5 : . Added a params to specify a repo to upgrade/install/copy/check
- 1.5.4 : . Added copy parameter to force a new copy.
- 1.5.3 : . Added a changed value to copy new files to the dir if it is needed.
- 1.5.2 : . Fixed some right access.
- 1.5.1 : . Added the forgoten params to copy "-ipr"
          . Check for writing permission to the needed folder