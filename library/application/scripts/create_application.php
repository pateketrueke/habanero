<?php

/**
 * Skeleton
 */

$skel_dir = dirname(__DIR__).DS.'assets';

   create_dir($app_path);
   create_dir($app_path.DS.'config');
    copy_file($app_path.DS.'config', $skel_dir.DS.'application'.EXT);

   create_dir($app_path.DS.'config'.DS.'environments');
    copy_file($app_path.DS.'config'.DS.'environments', $skel_dir.DS.'development'.EXT);
    copy_file($app_path.DS.'config'.DS.'environments', $skel_dir.DS.'production'.EXT);

   create_dir($app_path.DS.'controllers');
    copy_file($app_path.DS.'controllers', $skel_dir.DS.'home'.EXT);
     template($app_path.DS.'controllers', $skel_dir.DS.'base'.EXT, array(
        'app_name' => basename($app_path),
      ));

   create_dir($app_path.DS.'database');
        chmod($app_path.DS.'database', 0777);

   create_dir($app_path.DS.'database'.DS.'backup');
   create_dir($app_path.DS.'database'.DS.'migrate');

  create_file($app_path.DS.'database'.DS.'db.sqlite');
        chmod($app_path.DS.'database'.DS.'db.sqlite', 0777);

  create_file($app_path.DS.'database'.DS.'schema'.EXT, "<?php\n");
  create_file($app_path.DS.'database'.DS.'seeds'.EXT, "<?php\n");

   create_dir($app_path.DS.'library');
  create_file($app_path.DS.'library'.DS.'helpers'.EXT, "<?php\n");

   create_dir($app_path.DS.'static');
        chmod($app_path.DS.'static', 0777);

   create_dir($app_path.DS.'static'.DS.'css');
        chmod($app_path.DS.'static'.DS.'css', 0777);
  create_file($app_path.DS.'static'.DS.'css'.DS.'all.css', "\n");
        chmod($app_path.DS.'static'.DS.'css'.DS.'all.css', 0777);

   create_dir($app_path.DS.'static'.DS.'img');

   create_dir($app_path.DS.'static'.DS.'js');
        chmod($app_path.DS.'static'.DS.'js', 0777);
  create_file($app_path.DS.'static'.DS.'js'.DS.'all.js', "\n");
        chmod($app_path.DS.'static'.DS.'js'.DS.'all.js', 0777);
    copy_file($app_path.DS.'static'.DS.'js', $skel_dir.DS.'jquery-1.7.1.min.js');
    copy_file($app_path.DS.'static'.DS.'js', $skel_dir.DS.'modernizr-2.0.6.min.js');

     copy_dir($app_path, $skel_dir.DS.'tasks');

   create_dir($app_path.DS.'views');
   create_dir($app_path.DS.'views'.DS.'assets');

   create_dir($app_path.DS.'views'.DS.'assets'.DS.'css');
  create_file($app_path.DS.'views'.DS.'assets'.DS.'css'.DS.'app.css', "/**\n *= base\n */");
    copy_file($app_path.DS.'views'.DS.'assets'.DS.'css', $skel_dir.DS.'base.css');

   create_dir($app_path.DS.'views'.DS.'assets'.DS.'js');
  create_file($app_path.DS.'views'.DS.'assets'.DS.'js'.DS.'app.js', "/**\n *= lib/jquery-ujs\n */\n");
   create_dir($app_path.DS.'views'.DS.'assets'.DS.'js'.DS.'lib');
    copy_file($app_path.DS.'views'.DS.'assets'.DS.'js'.DS.'lib', $skel_dir.DS.'jquery-ujs.js');

   create_dir($app_path.DS.'views'.DS.'errors');
    copy_file($app_path.DS.'views'.DS.'errors', $skel_dir.DS.'errors'.DS.'404.html'.EXT);
    copy_file($app_path.DS.'views'.DS.'errors', $skel_dir.DS.'errors'.DS.'500.html'.EXT);

   create_dir($app_path.DS.'views'.DS.'layouts');
    copy_file($app_path.DS.'views'.DS.'layouts', $skel_dir.DS.'views'.DS.'default.html'.EXT);

   create_dir($app_path.DS.'views'.DS.'home');
    copy_file($app_path.DS.'views'.DS.'home', $skel_dir.DS.'views'.DS.'index.html'.EXT);


    $ignored_files = array(
      '.develop',
      'access.log',
      'error.log',
      'static/css/all.css',
      'static/js/all.js',
      'database/db.sqlite',
    );

  create_file($app_path.DS.'.gitignore', join("\n", $ignored_files) . "\n");
    copy_file($app_path, $skel_dir.DS.'.develop');
    copy_file($app_path, $skel_dir.DS.'.htaccess');

    copy_file($app_path, $skel_dir.DS.'Stubfile');
    copy_file($app_path, $skel_dir.DS.'migrate'.EXT);
    copy_file($app_path, $skel_dir.DS.'config'.EXT);
    copy_file($app_path, $skel_dir.DS.'routes'.EXT);
    copy_file($app_path, $skel_dir.DS.'index'.EXT);

/* EOF: ./library/application/scripts/create_application.php */
