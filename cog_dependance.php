<?php
/*
** This script use a json file to get all needed repositories for a site, and
** check for updates.
**
** @author    : Constantin Guay
** @url       : http://const-g.fr
** @version   : 1.6.5
** @usage     : php cog_dependance.php argv1 [argv2]
** @param     : $argv[1]
**                = install > will install new repo only.
**                = update  > will *check* for new version.
**                = update cron  > Like "update" but simplier, made for cron task response.
**                = upgrade > will pull all repo.
**                = copy > will force the copy of the argv[2] to its destination folder.
**              $argv[2]
**                = (optionnal) tells which repositorie, or "all".
**                = [cron] With update as first argmument, simplify the returned data for cron task.
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
** @todo      : . After the installation, if found, run a cog_setup.sh.
**              . Or a cog_update.sh after an update.
**              . Remove all those files.
** @changelog :
**              1.6.5 : . Send the cron email only if there is update.
**              1.6.4 : . Changed name of script files to be run (removed cog_ prefix).
**                      . Add php email function to manage cron reports.
**              1.6.3 : . Added cog_setup and cog_update bash script (and remove them).
**              1.6.2 : . Fixed the installation phase.
**                1.6.2.5 . Small fix.
**              1.6.1 : . Added a cron value for second argument.
**              1.6.0 : . Make an empty json file if not present and check for the usr.local dir.
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

$version = "1.6.5";

define("DEBUG", false);

$sudo = "sudo -u www-data ";
$sudo_root = "sudo -u root ";
$installed = $hasUpdateNumber = $canBeCloned = 0;
$firstInstall = $cronjob = $isInstalled = false;


$dirname  = "/var/www/cog_git_dependancies";
$filename = $dirname . "/cog_dependance.json";
$email_message = "";


if($argv[1] == "update" && ( !empty($argv[2]) && $argv[2] == "cron") ) {
  $cronjob = true;
  $argv[2] = null;
}

// Colors
function green($v)    { return("\033[32m" . $v . "\033[0m"); }
function grey($v)     { return("\033[0;37m" . $v . "\033[0m"); }
function red($v)      { return("\033[31m" . $v . "\033[0m"); }
function skyblue($v)  { return("\033[38;5;32m" . $v . "\033[0m"); }
function yellow($v)   { return("\033[33m" . $v . "\033[0m"); }

function bold($v)     { return("\033[1m" . $v . "\033[0m"); }
function bold_red($v) { return("\033[31m" . $v . "\033[0m"); }

// Check if is installed
if(!file_exists("/usr/local/cog_dependancies")) {
  echo( skyblue("Creating /usr/local/cog_dependancies") . "\n" );
  exec($sudo_root . "mkdir /usr/local/cog_dependancies && chown www-data /usr/local/cog_dependancies\n");
}

if(!file_exists($dirname)) {
  echo( skyblue("Creating " . $dirname) . "\n" );
  exec($sudo_root . "mkdir " . $dirname . " && chown www-data " . $dirname . "\n");
} 
if(!file_exists($filename)) {
  echo( skyblue("Creating a blank " . $filename) . "\n" );
  echo( exec($sudo . "touch " . $filename . "\n") );
  exec('echo "{ \"email\": \"\", \"repositories\": [] }" > ' . $filename . "\n");
  $firstInstall = true;
}

if(!file_exists("/usr/local/cog_dependancies") || !file_exists($filename))
  exit("Cog_Dependancies is not installed.");

if($cronjob) {
  $email_message .= "=================================\nWelcome to cog_dependancies " . $version ."\n=================================\n\n";
}

echo(bold("\n*************************************\n" .
  "* Welcome to cog_dependancies " . $version . " *" .
  "\n*************************************\n\n"));

if(empty($argv[2]))
  $argv[2] = 'all';

if($argv[1] == "update" && $argv[2] == "cron") {
  $argv[2] = null;
}

if($argv[2] == "self")
  $argv[2] = "cog_git_dependancies";

if(empty($argv[1]))
  exit("What do you want me to do ?!\n");

if($argv[1] == "copy" && empty($argv[2]))
  exit("What do you want me to copy ?!\n");



// Json
$dep = file_get_contents($filename);
$repos = json_decode($dep);

// Adding mySelf
$mySelf = json_decode('{ "name" : "cog_git_dependancies", "url": "git://github.com/Cog-g/cog_git_dependancies.git", "version": "master", "path" : "/var/www", "sudo_root" : "false", "use_folder" : "true" }');
array_unshift($repos->repositories, $mySelf);

if(empty($repos->email)) {
  exit("[FATAL] Please give me an email adress to send report (put it in the Json file).\n");
}

// Git 1.8 is mandatory to use --single-branch clone option
$git_version = substr(exec("git --version"), 12, 3);
$option_single_branch = "";
if(floatval($git_version) > 1.8) 
  $option_single_branch = "--single-branch ";



foreach ($repos->repositories as $repo) {
  $changed = $thisOne = false;
  
  if($argv[2] == 'all' || $argv[2] == $repo->name)
    $thisOne = true;

  if(empty($repo->version))
    $repo->version  = "master";

  $repo->exists = false;

  /*if($argv[1] == "update")
    echo("Checking : " . $repo->name . " ");*/

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
    echo(yellow( 'Installing ' . $repo->name . "#" . $repo->version . "..." ) . "\n");

    if(!file_exists($install_dir)) {
      //echo( skyblue($sudo_root . "mkdir " . $install_dir . " && chown www-data " . $install_dir) . "\n" );
      exec($sudo_root . "mkdir " . $install_dir . " && chown www-data " . $install_dir . "\n");
    }

    // Will clone the soda-v1 branch directly into /tmp/plugin (instead of soda-theme folder).
    $clone = exec($sudo . 'git clone -b ' . $repo->version . ' ' . $option_single_branch . $repo->url . ' ' . $install_dir . "\n");

    if(substr($clone, 0, 5) == "fatal") {
      echo( red("Fatal Error.") .  "\n" );
      continue;
    }

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
      $canBeCloned++;
      $message = $repo_filename . " is not cloned yet, but could be.\n";
      $hasUpdate = bold_red( $message ) . "\n          -> Run : sudo php " . $argv[0] . " install " . $repo->name . "\n\n";
      echo($hasUpdate);
    }
    
    if($hasUpdate != '## ' . $repo->version) {
      if($repo->exists && $argv[1] == "upgrade") {        
        exec('cd ' . $install_dir . "\n" . 
                $sudo . "git pull\n" .
                $sudo_root . "chown -R www-data " . $install_dir . "\n");
        $changed = true;
        
        $message = $repo_filename . " #" . str_replace('## ', "", $hasUpdate) . " has been updated";
        echo( green( $message ) . "\n");
      }
      elseif($repo->exists) {
        $hasUpdateNumber++;
        $message = $repo->name . " #" . str_replace('## ', "", $hasUpdate) . " can be upgraded";
        echo( yellow( $message ) . "\n       " . yellow("->") . " Run : sudo php " . $argv[0] . " upgrade " . $repo->name . "\n\n");
      }
    }
    else {
      $message = $repo->name . " #" . str_replace('## ', "", $hasUpdate) . " is up-to-date";
      echo( green( $message ) . "\n");
    }

    $email_message .= $message . ".\n";
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

    echo(exec("echo \"\n[" . yellow("***") . "] Copying from " . $install_dir . " to " . $repo->dir . "\"\n"));
    exec( $sudo_root . "cp -pr " . $install_dir . "/* " . $repo->dir . "/"
      . "&& rm -f "  . $repo->dir . "/README*"
      . "&& rm -f "  . $repo->dir . "/.git*"
      . "&& rm -fR " . $repo->dir . "/.git\n");

    //
    // Run cog_setup.sh/update script if they are found.
    //
    $fileToLaunch = false;
    if( ($argv[1] == "upgrade" || $argv[1] == "copy") && file_exists($repo->dir . '/on_update.sh')) {
      $fileToLaunch = 'on_update.sh';
    }
    elseif($argv[1] == "install" && file_exists($repo->dir . '/on_setup.sh')) {
      $fileToLaunch = 'on_setup.sh';
    }

    if($fileToLaunch !== false) {
      echo( "\n" . skyblue("Running " . $fileToLaunch . "...\n") );
      exec( $sudo_root . "$(which sh) " . $repo->dir . "/" . $fileToLaunch 
        . " && rm -f "  . $repo->dir . "/on_setup.sh"
        . " && rm -f "  . $repo->dir . "/on_update.sh\n" );
      echo( skyblue("File done.\n") );
    }
    echo("\n");
  }
}

if($canBeCloned > 0) {
  $email_message .= "\n\n" . $canBeCloned . " can be cloned\n";
  echo("\n" . $canBeCloned . " can be cloned\n");
}

if($hasUpdateNumber > 0) {
  $email_message .= "\n\n" . $hasUpdateNumber . " can be upgraded\n";
  echo("\n" . $hasUpdateNumber . " can be upgraded\n");
}

if($argv[1] == "install" && $installed == 0)
  echo( green("Nothing to install.") . "\n");

if($cronjob && ( $canBeCloned > 0 || $hasUpdateNumber > 0 ))
  mail($repos->email, 'Dependancies report', $email_message);

echo("\n");
?>