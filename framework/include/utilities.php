<?php

/**
 * Utility functions library
 */

/**#@+
 * Slug transformation options
 */
define('SLUG_STRICT', 1);
define('SLUG_LOWER', 2);
define('SLUG_TRIM', 4);
/**#@-*/


/**
 * Retrieve the character at first position in the provided string
 *
 * @param  mixed  String
 * @return string
 */
function char($text) {
  return ! is_numeric($text) ? substr((string) $text, 0, 1) : chr((int) $text);
}


/**
 * Make a string lowercase and non alphabetic charater to underscore
 *
 * @param     string  String
 * @param     boolean Use ucwords()?
 * @param     boolean Strict mode?
 * @staticvar array   Replacements
 * @return    string
 */
function underscore($text, $ucwords = FALSE, $strict = FALSE) {
  static $repl = array(
    '/(^|\W)([A-Z])/e' => '"\\1_".strtolower("\\2");',
    '/[A-Z](?=\w)/' => '_\\0',
  );


  $text = plain(unents($text));

  if ($ucwords) {
    $text = ucwords($text);
  }

  $text = preg_replace(array_keys($repl), $repl, $text);
  $text = trim(strtr($text, ' ', '_'), '_');
  $text = strtolower($text);

  return $text;
}


/**
 * Convert the specified string to camel case format
 *
 * @param  string  String
 * @param  boolean Use ucfirst()?
 * @param  string  Character separator
 * @return string
 */
function camelcase($text, $ucfirst = FALSE, $glue = '') {
  static $repl = array(
            '/[^a-z0-9]|\s+/i' => ' ',
            '/\s([a-z])/ie' => '$glue.ucfirst("\\1");',
          );


  $text = preg_replace(array_keys($repl), $repl, underscore($text));

  if ($ucfirst) {
    $text = ucfirst($text);
  }

  return $text;
}


/**
 * Unique hash
 *
 * @param     integer String length
 * @staticvar string  Charset
 * @return    string
 */
function salt($length = 8) {
  static $chars = '@ABCD,EFGH.IJKL-MNOP=QRST~UVWX$YZab/cdef*ghij;klmn:opqr_stuv(wxyz)0123!4567|89{}';


  $length = (int) $length;

  $length > 32 && $length = 32;

  $out = '';

  do
  {
    $index = substr($chars, mt_rand(0, 79), 1);

    if ( ! strstr($out, $index)) {
      $out .= $index;
    }

    $current = strlen($out);

  } while($current !== $length);

  return $out;
}


/**
 * Slugify string segments
 *
 * @param  string  Path|Route
 * @param  boolean Character separator
 * @param  mixed   SLUG_STRICT|SLUG_LOWER|SLUG_TRIM
 * @return string
 */
function slug($text, $glue = '-', $options = NULL) {
  $strict = ((int) $options & SLUG_STRICT) == 0 ? FALSE : TRUE;
  $lower = ((int) $options & SLUG_LOWER) == 0 ? FALSE : TRUE;
  $trim = ((int) $options & SLUG_TRIM) == 0 ? FALSE : TRUE;


  $expr = $strict ? '\W+' : '[^' . substr(match('%l'), 1, -1) . '\/]';
  $text = preg_replace("/$expr/", $glue, plain(unents($text)));
  $text = $lower ? strtolower($text) : $text;

  if ($trim) {
    $char = preg_quote($glue, '/');
    $text = preg_replace("/$char+/", $glue, $text);
    $text = trim($text, $glue);
  }

  return $text;
}


/**
 * Remove punctuation characters
 *
 * @param     string  String
 * @param     boolean Magic regex
 * @staticvar array   Entities set
 * @return    string
 */
function plain($text, $special = FALSE) {
  static $set = NULL,
         $rev = NULL;


  if (is_null($set)) {
    $old  = $rev = array();
    $html = get_html_translation_table(HTML_ENTITIES);

    foreach ($html as $char => $ord) {
      if (ord($char) >= 192) {
        $char = utf8_encode($char);
        $key = substr($ord, 1, 1);

        $set[$char] = $key;

        if ( ! isset($old[$key])) {
          $old[$key] = (array) $key;
        }

        $old[$key] []= $char;
        $old[$key] []= $ord;
      }
    }

    foreach ($old as $key => $val) {
      $rev[$key] = '(?:' . join('|', $val) . ')';
    }
  }


  $text = strtr($text, $set);
  $text = $special ? strtr($text, $rev) : $text;

  return $text;
}


/**
 * Strips out some type of tags
 *
 * @param  string  String
 * @param  boolean Allow comments?
 * @return string
 */
function strips($text, $comments = FALSE) {
  $out = preg_replace('/[<\{\[]\/*[^<\{\[!\]\}>]*[\]\}>]/Us', '', $text);
  $out = ! $comments ? strip_tags($out) : $out;

  return $out;
}


/**
 * Entity repair and escaping
 *
 * @param     mixed   String|Array
 * @param     boolean Escape tags?
 * @staticvar array   Hex replacements
 * @return    string
 */
function ents($text, $escape = FALSE) {
  static $expr = array(
            '/(&#?[0-9a-z]{2,})([\x00-\x20])*;?/i' => '\\1;\\2',
            '/&#x([0-9a-f]+);?/ei' => 'chr(hexdec("\\1"));',
            '/(&#x?)([0-9A-F]+);?/i' => '\\1\\2;',
            '/&#(\d+);?/e' => 'chr("\\1");',
          );


  $hash = uniqid('--entity-backup');
  $text = preg_replace('/&([a-z0-9;_]+)=([a-z0-9_]+)/i', "{$hash}\\1=\\2", $text);

  $text = preg_replace(array_keys($expr), $expr, $text);
  $text = preg_replace('/&(#?[a-z0-9]+);/i', "{$hash}\\1;", $text);
  $text = str_replace(array('&', '\\', $hash), array('&amp;', '&#92;', '&'), $text);

  if ($escape) {
    $text = strtr($text, array(
        '<' => '&lt;',
        '>' => '&gt;',
        '"' => '&quot;',
        "'" => '&#39;',
    ));
  }

  $text = preg_replace("/[\200-\237]|\240|[\241-\377]/", '\\0', $text);
  $text = preg_replace("/{$hash}(.+?);/", '&\\1;', $text);

  return $text;
}


/**
 * Revert entities
 *
 * @param     string String
 * @staticvar array  Entities set
 * @staticvar array  Replacements
 * @return    string
 */
function unents($text) {
  static $set = NULL,
         $expr = array(
            '/&amp;([a-z]+|(#\d+)|(#x[\da-f]+));/i' => '&\\1;',
            '/&#x([0-9a-f]+);/ei' => 'chr(hexdec("\\1"));',
            '/&#([0-9]+);/e' => 'chr("\\1");',
          );

  if (is_null($set)) {
    $set = get_html_translation_table(HTML_ENTITIES);
    $set = array_flip($set);

    $set['&apos;'] = "'";
  }

  $text = preg_replace(array_keys($expr), $expr, $text);
  $text = strtr($text, $set);

  return html_entity_decode($text);
}


/**
 * HTML generic tag
 *
 * @param   string  Tag name
 * @param   mixed   Attributes
 * @param   mixed   Inner text value|Function callback
 * @return  string
 */
function tag($name, array $args = array(), $text = '') {
  static $set = NULL;


  if (is_null($set)) {
    $test = include LIB.DS.'assets'.DS.'scripts'.DS.'html_vars'.EXT;
    $set  = $test['empty'];
  }

  $attrs = attrs($args);

  if (in_array($name, $set)) {
    return "<$name$attrs>";
  }


  if (is_closure($text)) {
    ob_start() && $text();

    $text = ob_get_clean();
  }

  return "<$name$attrs>$text</$name>";
}


/**
 * Make a string of HTML attributes
 *
 * @param  mixed  Array|...
 * @return string
 */
function attrs(array $args) {
  // TODO: allow classes mixup?
  if (func_num_args() > 1) {
    $set = array_slice(func_get_args(), 1);
    foreach ($set as $one) {
      is_array($one) && $args = array_merge($args, $one);
    }
  }


  $out = array('');

  foreach ($args as $key => $value) {
    if (is_bool($value)) {
      if ($value) {
        $out []= $key;
      }
    } elseif (is_array($value)) {
      if ($key === 'style') {
        $props = array();

        foreach ($value as $key => $val) {
          $props []= $key . ':' . trim($val);
        }

        $out []= 'style="' . join(';', $props) . '"';
      } else {
        foreach ($value as $index => $test) {
          $out []= $key . '-' . $index . '="' . (string) $test . '"';
        }
      }
    } elseif ( ! is_numeric($key)) {
      $out []= $key . '="' . ents((string) $value, TRUE) . '"';
    }
  }

  $out = join(' ', $out);

  return $out;
}


/**
 * Retrieve params from attributes string
 *
 * @param     string String
 * @staticvar string Match regex
 * @return    array
 */
function args($text, $prefix = '') {
  static $regex = '/(?:^|\s+)(?:([\w:-]+)\s*=\s*([\'"`]?)(.*?)\\2|[\w:-]+)(?=\s+|$)/';


  $out  = array();

  preg_match_all($regex, $text, $match);

  foreach ($match[1] as $i => $key) {
    if (empty($key)) {
      $out []= trim($match[0][$i]);
      continue;
    }

    $val = ents($match[3][$i], TRUE);
    $key = strtolower($key);

    $out[$key] = $val;
  }

  return $out;
}

/* EOF: ./framework/include/utilities.php */
