<?php

function s3_handle()
{
  static $s3 = FALSE;

  if ( ! $s3 && ($test = option('assets'))) {
    $s3 = TRUE;

    foreach ((array) $test as $key => $val) {
      \Labourer\Config::set($key, $val);
    }
    \Labourer\AS3::initialize();

    $set    = \Labourer\AS3::buckets();
    $name   = \Labourer\Config::get('s3_bucket');
    $region = \Labourer\Config::get('s3_location') ?: FALSE;

    if ( ! isset($set[$name])) {
      \Labourer\AS3::put_bucket($name, S3::ACL_PUBLIC_READ, $region);
    }
  }

  return $s3;
}

function s3_clean_bucket()
{
  $name = \Labourer\Config::get('s3_bucket');

  foreach ($set as $one => $ok) {
    if ($ok) {
      notice("Removing files from 's3://$name/$one'");
      $old = \Labourer\AS3::get_bucket($name, "$one/");

      foreach ($old as $file) {
        \Labourer\AS3::delete_object($name, $file['name']);
      }
    }
  }
}

function s3_upload_asset($file, $path)
{
  $mime = \IO\Helpers::mimetype($path);
  $bucket = \Labourer\Config::get('s3_bucket');

  \Labourer\AS3::put_object_file($file, $bucket, $path, S3::ACL_PUBLIC_READ, array(), $mime);
}

function solve_paths($text)
{
  static $test = array(
            '/(?<=font\/)\S+\.(?:woff|eot|ttf|svg)\b/i',
            '/(?<=img\/)\S+\.(?:jpe?g|png|gif)\b/i',
          );

  foreach ($test as $expr) {
    $text = preg_replace_callback($expr, function ($match) {
        return \Sauce\App\Assets::solve($match[0]);
      }, $text);
  }

  return $text;
}

function css_min($text)
{
  static $expr = array(
            '/;+/' => ';',
            '/;?[\r\n\t\s]*\}\s*/s' => '}',
            '/\/\*.*?\*\/|[\r\n]+/s' => '',
            '/\s*([\{;:,\+~\}>])\s*/' => '\\1',
            '/:first-l(etter|ine)\{/' => ':first-l\\1 {', //FIX
            '/(?<!=)\s*#([a-f\d])\\1([a-f\d])\\2([a-f\d])\\3/i' => '#\\1\\2\\3',
          );

  return preg_replace(array_keys($expr), $expr, $text);
}

function js_min($text)
{
  return \JShrink\Minifier::minify($text);
}
