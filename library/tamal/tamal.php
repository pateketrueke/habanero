<?php

/**
 * Template markup library
 */

class tamal extends prototype
{

  /**#@+
   * @ignore
   */

  // lambdas
  private static $fn = '(?:\s*\(([^()]+?)\)\s*|())\s*~\s*>(?=\b|$)';

  // quotes
  private static $qt = array(
                    '\\' => '<!--#BS#-->',
                    '"' => '<!--#QUOT#-->',
                    "'" => '<!--#APOS#-->',
                  );

  // code fixes
  private static $fix = array(
                    '/>\|/' => '>',
                    '/>\s*<\?/' => '><?',
                    '/\?>\s*<\//' => '?></',
                    '/\s*<\/pre>/s' => "\n</pre>",
                    '/<\?=\s*(.+?)\s*;?\s*\?>/' => '<?php echo \\1; ?>',
                    '/<\?php\s+(?!echo\s+|\})/' => "<?php ",
                    '/<!--#HASH\d{7}#-->/' => '',
                    '/^\s*\|(.*?)$/m' => '\\1',
                    '/ *?<!--#PRE#-->/' => '',
                    '/\s*,?\s+\)\s*/' => ')',
                    '/>\s*<\//' => '></',
                    '/\?>\s*<\?php\s*/' => "\n",
                    '/<\/li>\s*<li/' => '</li><li',
                    '/#\{(.+?)\}/' => '<?php echo \\1; ?>',
                    '/\}[\s;]*else(?=\s*if|\b)/s' =>'} else',
                    '/([,([])\s*([a-z][\w:-]+)\s*=>\s*/' => "\\1'\\2'=>",
                  );



  // open blocks
  private static $open = '(?:if|else(?:\s*if)?|while|switch|for(?:each)?)\b';

  // filter blocks
  private static $blocks = array();

  // defaults
  protected static $defs = array(
    'indent' => 2,
  );

  /**#@-*/



  /**
   * Render file
   *
   * @param  string Filepath
   * @param  array  Local vars
   * @return string
   */
  final public static function render($file, array $vars = array()) {
    if ( ! is_file($file)) {
      return FALSE;
    }


    $php_file = TMP.DS.str_replace(DS, '__DS__', $file);

    if (is_file($php_file)) {
      if (filemtime($file) > filemtime($php_file)) {
        unlink($php_file);
      }
    }


    if ( ! is_file($php_file)) {
      $out = static::parse(read($file), $file);
      write($php_file, $out);
    }

    return render($php_file, TRUE, array(
      'locals' => $vars,
    ));
  }


  /**
   * Parse markup
   *
   * @param  string Taml template
   * @return mixed
   */
  final public static function parse($text) {
    $code  = '';
    $stack = array();

    // TODO: improve this?
    $text  = preg_replace_callback('/\{\s*[\w:-]+(?=\s*=>|\s*[}])[^{}]*?\}/s', function ($match) {
      return preg_replace("/[\r\n\t]+/", ' ', $match[0]);
    }, $text);


    $test  = array_filter(explode("\n", $text));
    $file  = func_num_args() > 1 ? func_get_arg(1) : '';


    foreach ($test as $i => $line) {
      $key    = '$out';
      $tab    = strlen($line) - strlen(ltrim($line));
      $next   = isset($test[$i + 1]) ? $test[$i + 1] : NULL;
      $indent = strlen($next) - strlen(ltrim($next));

      if ( ! strlen(trim($line))) {
        continue;
      } elseif ($tab && ($tab % static::$defs['indent'])) {
        return FALSE;
      }


      if ($indent > $tab) {
        $stack []= substr(mt_rand(), 0, 7);
      }

      foreach ($stack as $top) {
        $key .= "['<!--#HASH$top#-->']";
      }

      if ($indent < $tab) {
        $dec = $tab - $indent;

        while ($dec > 0) {
          array_pop($stack);
          $dec -= static::$defs['indent'];
        }
      }

      $code .= $key;

      $line  = static::escape(rtrim($line));

      $code .= $indent > $tab ? "=array(-1=>'$line')" : "[]='$line'";
      $code .= ";";
    }


    @eval($code);

    if (empty($out)) {
      return FALSE;
    }

    $out = static::compile($out);
    $out = static::fixate($out);

    return $out;
  }


  /**#@+
   * @ignore
   */

  // apply filters
  final private static function filtrate($filter, $value) {
    return tamal_helper::apply($filter, array(static::unescape($value)));
  }

  // render markup tags
  final private static function markup($tag, array $args, $text = '') {
    $tmp   = array();
    $merge = FALSE;

    foreach ($args as $key => $val) {
      if (is_numeric($key)) {
        $tmp []= "array($val)";
        unset($args[$key]);
      }
    }


    if ( ! empty($args)) {
      $tmp []= str_replace("\n", '', var_export($args, TRUE));
    }

    $args = join(',', $tmp);
    $hash = md5($tag . $args . ticks());
    $out  = $hash . tag($tag, array(), $text);

    $repl = "<$tag<?php echo attrs($args); ?>";

    $args && $out = str_replace("$hash<$tag", $repl, $out);

    $out  = str_replace($hash, '', $out);

    return $out;
  }

  // compile lines
  final private static function compile($tree) {
    $span  = str_repeat(' ', static::$defs['indent']);
    $open  = '/^\s*-\s*' . static::$open . '/';
    $block = '/[-=].+?' . static::$fn . '/';
    $out   = array();

    if ( ! empty($tree[-1])) {
      $sub[$tree[-1]] = array_slice($tree, 1);

      if (preg_match($block, $tree[-1])) {
        $sub[$tree[-1]] []= '- })';
      } elseif (preg_match($open, $tree[-1])) {
        $sub[$tree[-1]] []= '- }';
      }

      $out []= static::compile($sub);
    } else {
      foreach ($tree as $key => $value) {
        if ( ! is_scalar($value)) {
          continue;
        } elseif (preg_match($block, $value)) {
          $tree []= '- })';
        } elseif (preg_match($open, $value)) {
          $tree []= '- }';
        }
      }


      foreach ($tree as $key => $value) {
        $indent = strlen($key) - strlen(ltrim($key));

        if (is_string($value)) {
          $out []= static::line($value, '', $indent - static::$defs['indent']);
          continue;
        } elseif (substr(trim($key), 0, 1) === ':') {
          $value = trim(join("\n", static::flatten($value)));
          $out []= static::filtrate(substr(trim($key), 1), $value);
          continue;
        } elseif (substr(trim($key), 0, 1) === '/') {
          $value = join("\n|", static::flatten($value));
          $out []= '<!--' . trim(substr($key, 1)) . "\n$span$value\n-->";
          continue;
        } elseif (substr(trim($key), 0, 3) === 'pre') {
          $value = join("\n", static::flatten($value));
          $value = preg_replace("/^$span\s{{$indent}}/m", '<!--#PRE#-->', $value);
        }

        $value = is_array($value) ? static::compile($value) : $value;
        $out []= static::line($key, $value, $indent);
      }
    }

    $out = join("\n", array_filter($out));

    return $out;
  }

  // parse single line
  final private static function line($key, $text = '', $indent = 0) {
    static $tags = NULL,
           $regex = '/^(?:#([a-z_][\da-z_-]*))?(?:.?([\d.a-z_-]+))?/i';

    $key  = static::unescape($key);
    $text = ents(static::unescape($text));


    if (is_null($tags)) {
      $test = include LIB.DS.'assets'.DS.'scripts'.DS.'html_vars'.EXT;
      $test = array_merge($test['empty'], $test['complete']);
      $tags = '(' . join('|', $test) . ')';
    }


    switch (substr($key, 0, 1)) {
      case '/';
        // <!-- ... -->
        return '<!--' . trim(substr($key, 1)) . "-->$text";
      break;
      case '<';
        // html
        return $key . $text;
      break;
      case '-';
        // php
        $key = rtrim(substr($key, 1), ';');
        $key = preg_replace('/\belse\s*;/', 'else{', static::block($key));

        return "<?php $key ?>$text";
      break;
      case '=';
          // print
        $key = trim(substr($key, 1));
        $key = static::block(rtrim($key, ';'), TRUE);

        return "<?php $key ?>$text";
      break;
      case '\\';
        return substr($key, 1) . $text;
      break;
      case ';';
        continue;
      break;
      default;
        $tag  = '';
        $args = array();

        // tag name
        preg_match("/^$tags(?=\b)/", $key, $match);

        if ( ! empty($match[0])) {
          $key = substr($key, strlen($match[0]));
          $tag = $match[1];
        }

        // attributes (raw)
        preg_match('/^[#.][.\w-]+/', $key, $match);

        if ( ! empty($match[0])) {
          $key = substr($key, strlen($match[0]));

          preg_match($regex, $match[0], $match);

          ! empty($match[1]) && $args['id'] = $match[1];
          ! empty($match[2]) && $args['class'] = strtr($match[2], '.', ' ');
        }

        // attributes { hash => val }
        preg_match('/^\s*\{(.+?)\}/', $key, $match);

        if ( ! empty($match[0])) {
          $key    = str_replace($match[0], '', $key);
          $args []= $match[1];
        }

        // output
        preg_match('/^\s*=.+?/', $key, $match);

        if ( ! empty($match[0])) {
          $key  = trim(substr(trim($key), 1));
          $text = "<?php echo $key; ?>$text";
        } else {
          $text = trim($key) . $text;
          $text = "\n$text\n";
        }

        $out  = ($tag OR $args) ? static::markup($tag ?: 'div', $args, $text) : $text;
        $out  = static::indent(trim($out));

        return $out;
      break;
    }
  }

  // parse blocks
  final private static function block($line, $echo = FALSE) {
    $suffix = ';';
    $prefix = $echo ? 'echo ' : '';

    if (preg_match('/' . static::$fn . '/', $line, $match)) {
      $suffix = '';
      $prefix = "\$__=get_defined_vars();$prefix";

      $open   = substr(trim(str_replace($match[0], '', $line)), -1) === '=' ? '(' : '';

      $args   = ! empty($match[1]) ? $match[1] : '';
      $line   = str_replace($match[0], "{$open}function($args)", $line);
      $line  .= 'use($__){extract($__,EXTR_SKIP|EXTR_REFS);unset($__);';
    } elseif (preg_match('/^\s*(' . static::$open . ')(.+?)$/', $line, $match)) {
      $line   = "$match[1]($match[2])";
      $suffix = '{';
    }

    return "$prefix$line$suffix";
  }

  // flatten array
  final private static function flatten($set, $out = array()) {
    foreach ($set as $one) {
      is_array($one) ? $out = static::flatten($one, $out) : $out []= $one;
    }
    return $out;
  }

  // apply fixes
  final private static function fixate($code) {
    $code = preg_replace('/^\s{' . static::$defs['indent'] . '}/m', '', $code);
    $code = preg_replace(array_keys(static::$fix), static::$fix, $code);
    $code = str_replace('@@', 'get_defined_vars()', $code);

    return $code;
  }

  // indentation
  final private static function indent($text, $max = 0) {
    $repl  = str_repeat(' ', $max ?: static::$defs['indent']);
    $test  = explode("\n", $text);
    $last  = array_pop($test);

    $text  = join("\n$repl", array_filter($test));
    $text .= "\n$last";

    return $text;
  }

  // protect chars
  final private static function escape($text, $rev = FALSE) {
    return $rev ? strtr(trim($text), array_flip(static::$qt)) : strtr($text, static::$qt);
  }

  // revert chars
  final private static function unescape($text) {
    return static::escape($text, TRUE);
  }

  /**#@-*/
}

/* EOF: ./library/tamal/tamal.php */
