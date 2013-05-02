<?php
/*
** This script use a json file to get all needed repositories for a site, and
** check for updates.
**
** @author    : Constantin Guay
** @url       : http://const-g.fr
** @version   : 1.5.8
** @usage     : php cog_dependance.php argv1 [argv2]
** @param     : $argv[1]
**                = install > will install new repo only.
**                = update  > will *check* for new version.
**                = upgrade > will pull all repo.
**                = copy > will force the copy of the argv[2] to its destination folder.
**              $argv[2] : (optionnal) tells which repositorie, or "all".
** @required  : -> a cog_dependance.json with a "repositories" array contening all
**               parameters for each one :
**                  name : the name of the repo. It will be use as folder name.
**                  url : url of the git repo
**                  version : A tag or branch, if empty will use "master"
**                  path : The path on the server where the repository should be up.
**                  sudo_root : If, to access to the patch, we need root acces, set true.
**                  use_folder : true if you want to use a folder withe the repository name
**                               or the name specified in the Json, or false if you want
**                               the content directly in the path.
** @optional  : -> git 1.8 to use the single branch clone option.
** @licence   : MIT
** @todo      : - a "script_to_lauch" parameter to set a script to launch after 
**                the installation of the repo.
** @changelog :
**              1.5.8 : . Removed self-update from the json file to add it on the code and added colors.
**              1.5.7 : . On copying, create the dir if not present.
**              1.5.6 : . Fixed the copy, only if copy passed on argument or if there is any change.
**              1.5.5 : . Added a params to specify a repo to upgrade/install/copy/check
**              1.5.4 : . Added copy parameter to force a new copy.
**              1.5.3 : . Added a changed value to copy new files to the dir if it is needed.
**              1.5.2 : . Fixed some right access.
**              1.5.1 : . Added the forgoten params to copy "-ipr"
**                      . Check for writing permission to the needed folder
*/

define("DEBUG", false);

if(empty($argv[1]))
  exit("What do you want me to do ?!\n");

if($argv[1] == "copy" && empty($argv[2]))
  exit("What do you want me to copy ?!\n");

if(empty($argv[2]))
  $argv[2] = 'all';

$filename = "/var/www/cog_git_dependancies/cog_dependance.json";
if(!file_exists($filename))
  exit($filename . " does not exists.\n");

// Json
$dep = file_get_contents($filename);
$repos = json_decode($dep);

// Adding mySelf
$mySelf = json_decode('{ "name" : "cog_git_dependancies", "url": "git://github.com/Cog-g/cog_git_dependancies.git", "version": "master", "path" : "/var/www", "sudo_root" : "false", "use_folder" : "true" }');
array_unshift($repos->repositories, $mySelf);


// Git 1.8 is mandatory to use --single-branch clone option
$git_version = substr(exec("git --version"), 12, 3);
$option_single_branch = "";
if(floatval($git_version) > 1.8) 
  $option_single_branch = "--single-branch ";

// Colors
$red      = "\033[38;31m";
$green    = "\033[38;32m";
$skyblue  = "\033[38;5;32m";


$sudo = "sudo -u www-data ";
$sudo_root = "sudo -u root ";
$installed = 0;


foreach ($repos->repositories as $repo) {
  $changed = $thisOne = false;
  
  if($argv[2] == 'all' || $argv[2] == $repo->name)
    $thisOne = true;

  if(empty($repo->version))
    $repo->version  = "master";

  $repo->exists = false;

  if($argv[1] == "update")
    echo("Checking : " . $repo->name . " ");

  if(!file_exists($repo->path) || !is_writable($repo->path)) {
    echo($repo->path . " is not writable\n");
    continue;
  }

  
  if(!empty($repo->name)) {
    $repo_filename = $repo->name;
  }
  else {
    $path = $repo->url;
    if(strpos($path, "@") && strpos($path, ":") && strpos($path, ".git")) {
      $repo_filename = substr($path, strpos($path, ":")+1);
      $repo_filename = str_replace(".git", "", $repo_filename);
    }
    else {
      $repo->pathinfo = pathinfo($path);
      $repo_filename = $repo->pathinfo['filename'];
    }
  }

  // This is the dir where all repositories will be stored.
  // content will be copied in the right folder after, without
  // any git folder/file.
  $install_dir = "/usr/local/cog_dependancies/" . $repo_filename . "#" . $repo->version;
  $repo->dir = $repo->path;
  
  if($repo->use_folder == 'true') 
    $repo->dir .= '/' . $repo_filename;

  // Check if the .git folder exists
  if(file_exists($install_dir . "/.git"))
    $repo->exists = true;

  //
  //
  // If the argument is INSTALL
  //
  //
  if(!$repo->exists && $argv[1] == "install" && $thisOne ) {
    echo('Installing ' . $repo->name . "#" . $repo->version . "\n");
    // Will clone the soda-v1 branch directly into /tmp/plugin (instead of soda-theme folder).
    $clone = exec($sudo . 'git clone -b ' . $repo->version . ' ' . $option_single_branch . $repo->url . ' ' . $install_dir . "\n");

    $changed = true;
    $installed++; // one more.
  }

  //
  //
  // If the argument is UPDATE | UPGRADE
  //
  //
  if( ($argv[1] == "update" || $argv[1] == "upgrade" ) && $thisOne) {
    if($repo->exists) {
      //$hasUpdate = exec('cd ' . $install_dir . "\n" . $sudo_root . "git status -sb\n");
      if(DEBUG) {
        echo ("\ncd " . $install_dir . " (with " . $sudo . ")\n");
      }
      $hasUpdate = exec('cd ' . $install_dir . " && " .
                          $sudo . "git remote update && " .
                          $sudo . "git status -sb && " .
                          $sudo_root . "chown -R www-data " . $install_dir . "\n");
    }
    else {
      $hasUpdate = " " . $skyblue . "is not cloned yet, but could be\033[39m \n          -> Run : php " . $argv[0] . " install [" . $repo->name . "]\n\n";
      echo($hasUpdate);
    }
    
    if($hasUpdate != '## ' . $repo->version) {
      if($repo->exists && $argv[1] == "upgrade") {        
        exec('cd ' . $install_dir . "\n" . 
                $sudo . "git pull\n" .
                $sudo_root . "chown -R www-data " . $install_dir . "\n");
        $changed = true;
        echo($repo_filename . " #" . str_replace('## ', "", $hasUpdate) . " " . $skyblue . "has been updated\033[39m\n");
      }
      elseif($repo->exists) {
        echo($repo->name . " #" . str_replace('## ', "", $hasUpdate) . " " . $red . "can be updated\033[39m\n         -> Run : php " . $argv[0] . " upgrade [" . $repo->name . "]\n\n");
      }
    }
    else {
      echo(" #" . str_replace('## ', "", $hasUpdate) . " is " . $green . "up-to-date\033[39m\n");
    }
  }

  $copy = false;
  if($argv[1] == "copy") {
    if( $thisOne ) {
      //echo(exec("echo \"\nCopy argv2 = " . $argv[2] . " => \"\n"));
      $copy = true;
    }
  }
  elseif($changed) {
    $copy = true;
  }

  if($copy) {
    // copy the file, without any git folder/file and remove README.*
    exec($sudo_root . "chown -R www-data " . $install_dir . "\n");
    
    // Check if the directory exists or create it.
    if(!file_exists($repo->dir)) {
      mkdir($repo->dir, 0755);
      exec($sudo_root . "chown -R www-data " . $repo->dir . "\n");
    }

    echo(exec("echo \"\nCopying from " . $install_dir . " to " . $repo->dir . "\"\n"));
    exec( $sudo_root . "cp -pr " . $install_dir . "/* " . $repo->dir . "/"
      . "&& rm -f " . $repo->dir . "/README*"
      . "&& rm -f " . $repo->dir . "/.git*"
      . "&& rm -fR " . $repo->dir . "/.git");
  }
}

if($argv[1] == "install" && $installed == 0)
  echo("Nothing to install.\n");
?>