<?php

i18n::load_path(__DIR__.DS.'locale', 'app');

app_generator::usage(ln('app.generator_title'), ln('app.generator_usage'));

app_generator::alias('app:create', 'create new');
app_generator::alias('app:status', 'status st');
app_generator::alias('app:action', 'action');
app_generator::alias('app:controller', 'controller');
app_generator::alias('app:execute', 'execute exec run');
app_generator::alias('app:configure', 'configure config conf');
app_generator::alias('app:prepare', 'precompile compile build');


// create application
app_generator::implement('app:create', function ($name = '') {
  info(ln('app.verifying_installation'));

  if ( ! $name) {
    error(ln('missing_arguments'));
  } else {
    $app_path = APP_PATH.DS.$name;

    if ( ! cli::flag('force') && dirsize($app_path)) {
      error(ln('app.directory_must_be_empty'));
    } else {
      require __DIR__.DS.'scripts'.DS.'create_application'.EXT;
      done();
    }
  }
});


// application status
app_generator::implement('app:status', function () {
  require __DIR__.DS.'scripts'.DS.'app_status'.EXT;
});


// controllers
app_generator::implement('app:controller', function($name = '') {
  if ( ! $name) {
    error(ln('app.missing_controller_name'));
  } else {
    require __DIR__.DS.'scripts'.DS.'create_controller'.EXT;
  }
  done();
});


// actions
app_generator::implement('app:action', function($name = '') {
  if ( ! $name) {
    error(ln('app.missing_action_name'));
  } else {
    require __DIR__.DS.'scripts'.DS.'create_action'.EXT;
  }
  done();
});


// task execution
app_generator::implement('app:execute', function ($name = '') {
  require __DIR__.DS.'scripts'.DS.'execute_task'.EXT;
});


// configuration status
app_generator::implement('app:configure', function () {
  require __DIR__.DS.'scripts'.DS.'configuration'.EXT;
});


// assets handling
app_generator::implement('app:prepare', function () {
  static $css_min = NULL;

  if (is_null($css_min)) {
    $css_min = function ($text) {
      static $expr = array(
                '/;+/' => ';',
                '/;?[\r\n\t\s]*\}\s*/s' => '}',
                '/\/\*.*?\*\/|[\r\n]+/s' => '',
                '/\s*([\{;:,\+~\}>])\s*/' => '\\1',
                '/:first-l(etter|ine)\{/' => ':first-l\\1 {', //FIX
                '/(?<!=)\s*#([a-f\d])\\1([a-f\d])\\2([a-f\d])\\3/i' => '#\\1\\2\\3',
              );

      return preg_replace(array_keys($expr), $expr, $text);
    };
  }


  $test = array();

  $test = array_merge($test, dir2arr(APP_PATH.DS.'static'.DS.'css'));
  $test = array_merge($test, dir2arr(APP_PATH.DS.'static'.DS.'js'));

  foreach ($test as $file) {
    if (preg_match('/(\w+)[a-f0-9]{32}\.(\w+)$/', $file, $match)) {
      $min_file = dirname($file).DS.sprintf('%s.min.%s', extn($file, TRUE), ext($file));
      success(ln('app.compiling_asset', array('name' => "$match[1].$match[2]")));

      write($min_file, $match[2] === 'css' ? $css_min(read($file)) : jsmin::minify(read($file)));
    }
  }
});



/* EOF: ./library/application/generator.php */
