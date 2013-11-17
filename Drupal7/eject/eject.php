<?php
  drupal_set_title(t('Eject! Eject! Eject!'));
  global $base_path;
  $eject_conf = array();
  $eject_conf['base_dir'] = trim(`pwd`);
  $databases = array();

  if (!$_GET['eject_stage']) {
    // Display opening screen.
    ?>

<h2>About Eject Eject Eject</h2>
<p>This script was designed as a last-ditch option for migrating a Drupal site off of a host in a worst-case scenario where you
have no FTP or shell access and can't install modules like <a href="http://drupal.org/project/backup_migrate">Backup and Migrate</a>.</p>

<h2>Usage</h2>
<p>Eject Eject Eject will attempt to bundle your site for migration in four stages. You will be prompted to refresh this page 
between stages.</p>

<h3>Step 1: check requirements</h3>
<p>This script utilizes a number of PHP core functions that are frequently disabled for security reasons. These include dir() and 
shell_exec(). During the first stage Eject Eject Eject will check for the availability of these functions.</p>

<p>The code also requires some commonly available linux commandline tools like pwd, mysqldump, tar and gzip so it also attempts to 
locate these tools. Once this step is completed you should be prompted to continue to step 2 unless one or more requirements are 
missing (in which case you're probably screwed).</p>

<h3>Step 2: locating database resources</h3>
<p>During this stage Eject Eject Eject will try to gather database credentials from your site's configuration file. Note this 
process supports multi-site installations and if more than one site is found you will be given a choice of which databases you'd 
like to package for retrieval.</p>

<h3>Step 3: download your dumps</h3>
<p>At this point the script has dumped any selected databases to file. To avoid running into issues with shared hosting file space
limits you must download your dumps before proceeding to step 4.</p>

<h3>Step 4: packaging your files for download</h3>
<p>The last step involves making tarball your site's files for download. This is the last step in the process and if it is 
successful you will be given a link to a tar.gz file containing all of the site's files.</p>

<form name="ejectejecteject" action="" method="GET">
  <input type="hidden" name="eject_stage" value="1"></input>
  <button>Let's do this!</button>
</form>
<?php
  }

  if ($_GET['eject_stage'] == 1) {
?>
<h2>Step 1: checking requirements</h2>
<?php
    $prerequisites = array();
    // Check for dir()
    eject_find_function('dir', $prerequisites);
  
    // Check for shell_exec()
    eject_find_function('shell_exec', $prerequisites);

    // Check for system()
    eject_find_function('system', $prerequisites);
?>
<h3>Required PHP functions</h3>
<p>The Eject Eject Eject script requires several PHP core functions that may be disabled for security reasons. If any of
these functions are not accessible the script will not work.</p>
<?php
    print theme('item_list', $prerequisites['functions']);
    $prerequisites = array();
    
    // Check for mysqldump
    eject_find_cli('mysqldump --help', $prerequisites);
    
    // Check for tar
    eject_find_cli('tar --help', $prerequisites);
    
    // Check for gzip
    eject_find_cli('gzip --help', $prerequisites);
    
    // Check for pwd
    eject_find_cli('pwd', $prerequisites);
?>
<h3>Required commands</h3>
<p>The script utilizes several common *nix commands to accomplish most of it's work. If any of these commands are unavailable
the script will not work.</p>
<?php
    print theme('item_list', $prerequisites['commands']);
    if ($prerequisites['error'] == 0) {
?>
<form name="ejectejecteject" action="" method="GET">
  <input type="hidden" name="eject_stage" value="2"></input>
  <button>Proceed to step 2</button>
</form>
<?php
    }
  }
  
  if ($_GET['eject_stage'] == 2) {
?>
<h2>Step 2: select database(s) to export</h2>
<p>Drupal stores all of your site's content and configuration information in a database. To successfully migrate your site you
will need a copy of this database. If you are trying to export a <a href="">multi-site installation</a> of Drupal you should see a list of
available databases below. Eject Eject Eject will only try to export the databases you choose so be sure to select the ones
you need.</p>
<form action="" method="GET">
<?php
   $eject_conf = variable_get('eject_conf', '');
   $site_dirs = dir('sites');
   while (($file = $site_dirs->read()) !== FALSE) {

     if ($file !== '.' && $file !== '..' && is_dir('sites/' . $file)) {

        if (file_exists("sites/$file/settings.php")) {

         $settings = fopen("sites/$file/settings.php", 'r');

         while ($line = fgets($settings)) {

           if (preg_match('/^\$db_url \=/', $line)) {
             $line = explode("'", $line);
             $db_info = parse_url($line[1]);
             $db_info['path'] = preg_replace('/\//', '', $db_info['path']);
?>
  <label for="<?php print $db_info['path'];?>"><?php print $db_info['path'];?></label>
  <input type="checkbox" name="<?php print $db_info['path']; ?>"></input><br />
<?php
             $databases[] = $db_info;
           }
          } 
        }
      }
    }
    $eject_conf['databases'] = $databases;
    variable_set('eject_conf', $eject_conf);
?>
  <input type="hidden" name="eject_stage" value="3">
  <button>Proceed to step 3</button>
</form>
<?php
  }

  if ($_GET['eject_stage'] == 3) {
?>
<h2>Step 3: download database dumps</h2>
<p>The databases you selected in the previous step have been dumped to file and are ready to download. Since these files are
typically large they will be deleted before the rest of your site files are packaged for download. This means you need to download
the file(s) listed below before proceeding to the next step.</p>
<?php
    $eject_conf = variable_get('eject_conf', '');
    $databases = $eject_conf['databases'];
    $db_links = array();
    foreach ($databases as $database) {
      if (!strcmp($_GET[$database['path']], 'on')) {
        if ($eject_conf['mysqldump_location']) {
          $cmd = preg_replace('/mysqldump/', './mysqldump', $eject_conf['mysqldump_location']);
          $dump_string = $cmd . ' -u ' . $database['user'];
        }
        else {
          $dump_string = 'mysqldump -u ' . $database['user'];
        }
        $dump_string .= ' -p' . $database['pass'];
        $dump_string .= ' -h ' . $database['host'];
        $dump_string .= ' ' .  $database['path'];
        $dump_string .= ' > ' . trim($eject_conf['base_dir']) . '/' . $database['path']. '.txt';
        ob_start();
        system($dump_string, $ret);
        $output = ob_get_contents();
        ob_end_clean();
        $db_links[] = '<a href="' . $base_path . $database['path'] . '.txt">' . $database['path'] . '</a>';
      }
    }
    
    print '<strong>Right click on each link and select "save link" to download.</strong>' . theme('item_list', $db_links);
?>
<form action="" method="GET">
  <input type="hidden" name="eject_stage" value="4">
  <button>Proceed to step 4</button>
</form>
<?php
  }

  if($_GET['eject_stage'] == 4) {
?>
<h2>Step 4: download site files</h2>
<p>The site's files, including Drupal core, contributed modules, themes and any uploaded files have been packaged for download.
Once you have finished downloading you may optionally run the cleanup step.</p>
<?php
    // Nuke any database dumps before tarballing the site. This is an effort to get around filespace limits on cheap hosting packages.
    $eject_conf = variable_get('eject_conf', '');
    $databases = $eject_conf['databases'];
    foreach ($databases as $database) {
      if (file_exists($eject_conf['base_dir'] . '/' . $database['path'] . '.txt')) {
        $filepath = $eject_conf['base_dir'] . '/' . $database['path'] . '.txt';
        `rm $filepath`;
        drupal_set_message('Removed ' . $filepath);
      }
    }
    `tar -cf ejected.tar *`;
    `gzip ejected.tar`;
     if (file_exists($eject_conf['base_dir'] . '/' . 'ejected.tar.gz')) {
       print '<p>Click on <a href="' . $base_path . 'ejected.tar.gz">this link</a> to download.</p>';
     }
?>
<form action="" method="GET">
  <input type="hidden" name="eject_stage" value="5">
  <button>Proceed to cleanup</button>
</form>
<?php
  }
  
  if ($_GET['eject_stage'] == 5) {
    $eject_conf = variable_get('eject_conf', '');
    $filepath = $eject_conf['base_dir'] . '/ejected.tar.gz';
    `rm $filepath`;
    variable_del('eject_conf');
?>
<h2>Step 5: cleanup</h2>
<p><strong>Eject Eject Eject has finished. It is highly recommended that you delete this page to prevent others from exporting
a copy of your site.</strong></p>
<p>All files and database entries created by Eject Eject Eject have been removed from the system. Depending
on the level of logging on this site there may be traces left in log files or watchdog. Of course, if you're exporting a
site you own you've got nothing to worry about, right?</p>
<h3>Next steps:</h3>
<p>Assuming you've run Eject Eject Eject as part of a planned site migration you will need to acquire new hosting. You
will need to copy the files you downloaded to your new host and unpack them, push the database dump to a new database and perform
any configuration tweaks necessary to get your site working correctly in the new environment. Note the following steps assume
you are hosting your site on a linux-based system and have access to a shell account as well as some familiarity with *nix
commandline.</p>
<h3>Unpacking your files</h3>
<p>Once you've uploaded your files to the new host via <a href="http://en.wikipedia.org/wiki/File_Transfer_Protocol">FTP</a>, 
<a href="http://en.wikipedia.org/wiki/Rsync">rsync</a>, <a href="http://en.wikipedia.org/wiki/SSH_File_Transfer_Protocol">sftp</a> or some other
method, you'll need to unpack them. First make sure your files are in the same directory as webroot then (on a Linux-based host) simply 
perform the following:</p>
<p><em>tar -xvzf ejected.tar.gz</em></p>
<p>Repeat this process for your database file(s). You should now see a Drupal error message when you visit your website.
No worries, the next two steps should clear that right up.</p>
<h3>Pushing the database image</h3>
<p>Before you can access the contents of your old site database you first need to create a database on your new hosting. Methods
for doing this vary from host to host, anything from commandline mysql client, phpMyAdmin or opening a helpdesk ticket and
waiting are possible. You should also create a non-root database user account and grant it all privileges on your newly created
database.</p>
<p>You should now be able to push the contents of your old database to the new one. From the command line this would look
something like this:</p>
<p><em>mysql -u username -p -h database_server database_name < olddatabase.txt</em></p>
<p>Once this process is complete you will want to take a look at the database with mysql or phpMyAdmin to confirm database tables
where created.</p>
<h3>Tweaking your site configuration</h3>
<p>It's unlikely that your site is working at this point. The most likely cause of problems is incorrect database information in
settings.php. If your site isn't displaying correctly (or at all) you will probably need to edit settings.php and modify the database
information to match settings on your new host. You may also need to tweak your .htaccess file if your site was installed in a subdirectory.
<p>If your old site used Apache rewrite rules you will need to replicate these on your new host. Consult your hosting provider's 
documentation for more details on how to do this.</p>

<?php
  }

function eject_find_function($function, &$prerequisites) {
  if(!isset($prerequisites['errors'])) {
    $prerequisites['errors'] = 0;
  }

  if (!function_exists($function)) {
    drupal_set_message("Unable to proceed, the $function function is unavailable.", 'error');
    $prerequisites['functions'][] = t($function . ' is unavailable.');
    $prerequisites['errors'] = 1;
  }
  else {
    $prerequisites['functions'][] = t($function . ' is available.');
  }
}

function eject_find_cli($command, &$prerequisites) {
  $eject_conf = variable_get('eject_conf', '');
  if(!isset($prerequisites['errors'])) {
    $prerequisites['errors'] = 0;
  }
  ob_start();
  system("$command", $ret);
  $output = ob_get_contents();
  ob_end_clean();
  $command = trim(preg_replace('/--\w+$/', '', $command));
  if ($ret == 127) {
    $locations = explode("\n", `locate $command`);
    if (!count($locations)) {
      drupal_set_message(t('Unable to proceed, cannot find @command', array('@command' => $command)), 'error');
      $prerequisites['errors'] = 1;
      $prerequisites['commands'][] = t('@command is missing or unavailable!', array('@command' => $command));
    }
    else {
      foreach ($locations as $location) {
        if (preg_match('/' . $command . '$/', $location) && file_exists($location)) {
          drupal_set_message('location found:' . $location);
          $eject_conf[$command . '_location'] = $location;
          $prerequisites['commands'][] = t('@command found at @path', array('@command' => $command, '@path' => $location));
        }
      }
    }
  }
  elseif ($ret === 0) {
    $prerequisites['commands'][] = t('@command is available and on $PATH', array('@command' => $command));
  }
  variable_set('eject_conf', $eject_conf);
}
?>