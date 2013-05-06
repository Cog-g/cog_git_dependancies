# cog_Git_Dependancies

This script use a json file to get all needed repositories from git, and check for updates.


## Installation

I recommand to clone it on /tmp to launch the installation :

	$ cd /tmp
	$ git clone git://github.com/Cog-g/cog_git_dependancies.git
	$ sudo php /tmp/cog_git_dependancies/cog_dependance.php install
	$ rm -r /tmp/cog_git_dependancies

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
		"email": "",
		"repositories": []
	}

___(1.6.4)_ PLEASE INSERT YOUR EMAIL IN THIS JSON FILE__

Here is an example of a Json :

	{
		"email": "my@email.com",
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

## Script to launch _(1.6.3)_

Adding a `on_setup.sh` or `on_update.sh` file to your repository will make the program to run and delete them.

### on_setup.sh
Will be ran on installation.

### on_update.sh
Will be ran on upgrade or copy.

_These files will be removed after an installation/upgrade/copy_

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
	15 3 * * * php /var/www/cog_git_dependancies/cog_dependance.php update cron >/dev/null 2>&1

_(1.6.1)_ Adding parameter "cron" to update return data without any color.
Will check every day at 3:15 am and should send an email.




### Changelog

- 1.6.4 : . Changed name of script files to be run (removed cog_ prefix).
					. Add php email function to manage cron reports.
- 1.6.3 : . Added cog_setup and cog_update bash script (and remove them).
- 1.6.2 : . Fixed the installation phase.
		1.6.2.5 . Small fix.
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