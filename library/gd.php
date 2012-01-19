<?php

/**
 * GD2 library
 */

if ( ! function_exists('gd_info'))
{
  raise(ln('extension_missing', array('name' => 'GD2')));
}


/**#@+
 * @ignore
 */
! defined('JPEG') && define('JPEG', 'jpeg');
! defined('JPG') && define('JPG', 'jpeg');
! defined('PNG') && define('PNG', 'png');
! defined('GIF') && define('GIF', 'gif');
/**#@-*/

class gd
{
  /**#@+
   * @ignore
   */
  private $allow = array(JPEG, PNG, GIF);

  private $transparency = '#000';
  private $alpha = 127;
  private $swidth = 160;
  private $sheight = 40;



  private $file = '';
  private $size = 0;
  private $type = JPEG;
  private $mime = 'image/jpeg';
  private $resource = -1;

  // constructor
  private function __construct($path) {
    $test = getimagesize($path);
    $tmp  = imagecreatefromstring(read($path));

    $this->type = end(explode('/', $test['mime']));
    $this->mime = $test['mime'];
    $this->file = realpath($path);

    $this->resource = $this->fix_alpha($tmp);
  }
  /**#@-*/


  /**
   * Importar una imagen
   *
   * @param  string $path Ruta fisica del SO
   * @return void
   */
  final public function import($path) {
    if ( ! is_file($path)) {
      raise(ln('file_not_exists', array('name' => $path)));
    }
    return new static($path);
  }


  /**
   * Exportar la imagen
   *
   * @param  mixed  $test Nombre|Ruta fisica del SO|Tipo
   * @param  string $type Tipo de imagen
   * @return mixed
   */
  final public function export($test = '', $type = '') {
    $ext = str_replace(JPEG, 'jpg', $this->type);

    if ( ! empty($test) && is_dir($test)) {
      $output = rtrim($test, '\\/').DS;
      $output .= extn($this->file, TRUE);
      $output .= '.' . $ext;
    } elseif (func_num_args() == 0) {
      $output = $this->file;
    } elseif ( ! empty($test)) {
      $output = dirname($this->file).DS.extn($test, TRUE).".$ext";
    }

    $callback = 'image' . (in_array($type, $this->allow) ? $type : $this->type);

    if ( ! is_callable($callback)) {
      trigger_error(sprintf(ln('Not implemented yet: %s'), $callback)) +exit;
    } elseif ( ! empty($output)) {
      $callback($this->resource, $output);
      return $output;
    }
    $callback($this->resource);
  }


  /**
   * Convertir imagen
   *
   * @param  string  $type  Tipo de imagen
   * @param  boolean $force Forzar tipo?
   * @return void
   */
  final public function convert($type, $force = FALSE) {
    $type = in_array($type, $this->allow) ? $type : JPEG;

    if (is_true($force) OR ($this->type <> $type)) {
      ob_start();

      $this->type = $type;
      $this->file = preg_replace('/\.\w+$/', ".$type", $this->file);
      $this->mime = "image/$type";
      $this->export(NULL, $type);

      $out = ob_get_contents();
      ob_end_clean();

      $tmp = imagecreatefromstring($out);
      $tmp = $this->fix_alpha($tmp);
      $this->resource = $tmp;
    }
    return $this;
  }


  /**
   * Enviar imagen al navegador
   *
   * @return void
   */
  final public function output() {
    header("Content-Type: $this->mime");
    $this->export(NULL, $this->type);
    exit;
  }


  /**
   * Informacion de la imagen
   *
   * @return array
   */
  final public function info() {
    $set = array(
      'width' => $this->width(),
      'height' => $this->height(),
      'mime' => $this->mime,
      'type' => $this->type,
    );

    if (is_file($old = $this->file())) {
      $set = array_merge($set, array(
        'path' => dirname($old),
        'name' => extn($old, TRUE),

        'ctime' => filectime($old),
        'mtime' => filemtime($old),
        'atime' => fileatime($old),

        'ext' => ext($old, TRUE),
        'file' => $old,
      ));
    }
    return $set;
  }


  /**
   * Obtener paleta de colores
   *
   * @link   http://www.phpbuilder.com/board/showpost.php?p=10868783&postcount=2
   * @param  integer $limit Limitar resultado
   * @param  integer $step  Avance por pixel
   * @return array
   */
  final public function palette($limit = 10, $step = 5) {
    $out = array();

    $w = $this->width();
    $h = $this->height();

    if ($step < 1) {
      $step = 1;
    }

    for ($x = 0; $x < $w; $x += $step) {
      for ($y = 0; $y < $h; $y += $step) {
        $color = imagecolorat($this->resource, $x, $y);
        $rgb   = imagecolorsforindex($this->resource, $color);

        $R   = round(round(($rgb['red'] / 0x33)) * 0x33);
        $G   = round(round(($rgb['green'] / 0x33)) * 0x33);
        $B   = round(round(($rgb['blue'] / 0x33)) * 0x33);
        $hex = sprintf('%02x%02x%02x', $R, $G, $B);

        if (array_key_exists($hex, $out)) {
          $out[$hex] += 1;
        } else {
          $out[$hex] = 1;
        }
      }
    }

    arsort($out);

    $out = array_keys($out);
    $out = array_slice($out, 0, $limit);

    return $out;
  }


  /**
   * Tipo
   *
   * @param  boolean $mime Devolver MIME?
   * @return string
   */
  final public function type($mime = FALSE) {
    if (is_true($mime)) {
      return $this->mime;
    }
    return $this->type;
  }


  /**
   * Ancho
   *
   * @return integer
   */
  final public function width() {
    return ($out = @imagesx($this->resource)) ?: $this->swidth;
  }


  /**
   * Alto
   *
   * @return integer
   */
  final public function height() {
    return ($out = @imagesy($this->resource)) ?: $this->sheight;
  }


  /**
   * Archivo
   *
   * @return boolean
   */
  final public function file() {
    return $this->file;
  }



  // ---------------------------------------------------------------------------

  /**
   * Miniatura de la imagen
   *
   * @param  integer $width  Ancho
   * @param  integer $height Alto
   * @return void
   */
  final public function thumb($width = 120, $height = 0) {
    $this->fix_dimset($width, $w = $this->width());
    $this->fix_dimset($height, $h = $this->height());

    if ($height <= 0) {
      $height = $width;
    }

    $ratio = $w / $h;

    if (($width / $height) > $ratio) {
      $h = $width / $ratio;
      $w = $width;
    } else {
      $w = $height * $ratio;
      $h = $height;
    }

    $left = ($w / 2) -($width / 2);
    $top  = ($h / 2) -($height / 2);

    return $this->resize($w, $h)->crop($width, $height, $left, $top);
  }


  /**
   * Escalar imagen
   *
   * @param  integer $width  Ancho
   * @param  integer $height Alto
   * @return void
   */
  final public function scale($width, $height = 0) {
    $this->fix_dimset($width, $w = $this->width());
    $this->fix_dimset($height, $h = $this->height());

    if ($width && ! $height) {
      $height = ($width * $h) / $w;
    } elseif ( ! $width && $height) {
      $width = ($w / $h) * $height;
    } else {
      if($w > $h) {
        $width = ($w / $h) * $height;
      } else {
        $height = ($width * $h) / $w;
      }
    }

    return $this->resize($width, $height);
  }


  /**
   * Redimensionar imagen
   *
   * @param  integer $width  Ancho
   * @param  integer $height Alto
   * @return void
   */
  final public function resize($width, $height = 0) {
    $this->fix_dimset($width, $w = $this->width());
    $this->fix_dimset($height, $h = $this->height());

    if ($height <= 0) {
      $height = $width;
    }

    $old = $this->fix_alpha(imagecreatetruecolor($width, $height));

    $this->resample($old, 0, 0, $width, $height, 0, 0, $w, $h);
    $this->resource = $old;

    return $this;
  }


  /**
   * Recortar imagen
   *
   * @param  integer $width  Ancho
   * @param  integer $height Alto
   * @param  integer $left   Offset X
   * @param  integer $top  Offset Y
   * @return void
   */
  final public function crop($width, $height, $left = 0, $top = 0) {
    $this->fix_dimset($width, $w = $this->width());
    $this->fix_dimset($height, $h = $this->height());
    $this->fix_dimset($left, $w);
    $this->fix_dimset($top, $h);

    $old = $this->fix_alpha(imagecreatetruecolor($width, $height));

    imagecopyresampled($old, $this->resource, 0, 0, $left, $top, $width, $height, $width, $height);
    imagedestroy($this->resource);

    $this->resource = $old;
    return $this;
  }


  /**
   * Rotar imagen
   *
   * @param  integer $angle   Angulo
   * @param  mixed   $bgcolor HEX|RGB
   * @return void
   */
  final public function rotate($angle = 45, $bgcolor = '#fff') {
    if (($angle % 180) <> 0) {
      $bg  = $this->allocate($this->resource, $bgcolor);
      $tmp = imagerotate($this->resource, $angle, $bg, $this->type === PNG);

      is_resource($tmp) && $this->resource = $tmp;
    }
    return $this;
  }



  // --------------------------------------------------------------------------

  /**
   * Ajustar brillo
   *
   * @param  integer $mnt Porcentaje
   * @return void
   */
  final public function brightness($mnt = 13) {
    $mnt && $this->filter(__FUNCTION__, $mnt);
    return $this;
  }


  /**
   * Ajustar contraste
   *
   * @param  integer Porcentaje
   * @return void
   */
  final public function contrast($mnt = 20) {
    $mnt && $this->filter(__FUNCTION__, $mnt);
    return $this;
  }


  /**
   * Colorear imagen
   *
   * @param  integer $mnt   Porcentaje
   * @param  mixed   $mask  Color de mascara
   * @return void
   */
  final public function colorize($mnt = 33, $mask = '#aa0') {
    $mnt = is_num($mnt) ? $mnt : 25;
    $per = $mnt / 100;

    if ($mask <> 'gray') {
      if ( ! is_array($mask)) {
        $px = $this->fix_rgbhex($mask);
      } else {
        $px = array_values($mask);
      }
    }

    $mnt && $this->filter(__FUNCTION__, $px[0], $px[1], $px[2]);
    return $this;
  }


  /**
   * Distorsion
   *
   * @return void
   */
  final public function blur() {
    return $this->filter('gaussian_blur');
  }


  /**
   * Negativo
   *
   * @staticvar mixed Callback
   * @return  void
   */
  final public function negative() {
    return $this->filter('negate');
  }



  // --------------------------------------------------------------------------

  /**
   * Voltear imagen
   *
   * @param  mixed $vertical Girar verticalmente?
   * @return void
   */
  final public function mirror($vertical = FALSE) {
    $width  = $this->width();
    $height = $this->height();

    // (TRUE) vertical,v,ver,vert
    $vertical = ! is_string($vertical) ? (boolean) $vertical : (strtolower(substr($vertical, 0, 1)) != 'v' ? FALSE : TRUE);
    $old      = $this->fix_alpha(imagecreatetruecolor($width, $height));

    if ( ! is_true($vertical)) {
      for ($x = 0, $w = $width; $x < $width; $x += 1) {
        imagecopy($old, $this->resource, $w -= 1, 0, $x, 0, 1, $height);
      }
    } else {
      for ($y = 0, $h = $height; $y < $height; $y += 1) {
        imagecopy($old, $this->resource, 0, $h -= 1, 0, $y, $width, 1);
      }
    }

    $this->resource = $old;
    return $this;
  }


  /**
   * Aplicar mascara
   *
   * @param  mixed   $test  Imagen
   * @param  integer $left  Offset X
   * @param  integer $top   Offset Y
   * @param  integer $width   Ancho
   * @param  integer $height  Altura
   * @param  integer $opacity Opacidad
   * @return void
   */
  final public function mask($test, $left = 0, $top = 0, $width = '100%', $height = '100%', $opacity = 100) {
    if (is_num($opacity, 0, 1)) {
      $opacity *= 100;
    }

    $this->fix_dimset($x, $left, $cw = $this->width(), $w = $test->width());
    $this->fix_dimset($y, $top, $ch = $this->height(), $h = $test->height());
    $this->fix_dimset($width, $cw);
    $this->fix_dimset($height, $ch);

    $tmp = $this->fix_alpha(imagecreatetruecolor($width, $height));

    for ($wmax = ceil($width / $w), $r = $x, $m = 0; $m < $wmax; $m += 1, $r += $w) {
      for ($hmax = ceil($height / $h), $s = $y, $n = 0; $n < $hmax; $n += 1, $s += $h) {
        imagecopymerge($tmp, $test->resource, $r, $s, 0, 0, $w, $h, 100);
      }
    }

    imagecopymerge($this->resource, $tmp, $x, $y, 0, 0, $width, $height, $opacity);
    imagedestroy($tmp);

    return $this;
  }


  /**
   * Dibujar texto
   *
   * @param  string  $text  Cadena
   * @param  integer $left  Offset X
   * @param  integer $top   Offset Y
   * @param  integer $size  Tamaño
   * @param  mixed   $color   Color
   * @param  mixed   $opacity Opacidad
   * @param  string  $font  Fuente TrueType
   * @param  mixed   $angle   Inclinacion
   * @return void
   */
  final public function draw($text, $left = 0, $top = 0, $size = 5, $color = '#000', $opacity = 100, $font = '', $angle = 0) {
    $font = realpath($font);
    $this->fix_dimset($angle, 360);
    $pos = $this->outerbox($text, $size, $angle, $font);

    $this->fix_dimset($x, $left, $this->width(), $pos['width']);
    $this->fix_dimset($y, $top, $this->height(), $pos['height']);

    $pencil = $this->allocate($this->resource, $color, $opacity);

    if ( ! is_file($font)) {
      imagestring($this->resource, min($size, 5), $x, $y, $text, $pencil);
    } else {
      imagettftext($this->resource, $size, $angle, $x + $pos['left'], $y + $pos['top'], $pencil, $font, $text);
    }
    return $this;
  }


  /**
   * Rellenar area
   *
   * @param  mixed $color   Color
   * @param  mixed $left  Offset X
   * @param  mixed $top   Offset Y
   * @param  mixed $width   Ancho
   * @param  mixed $height  Altura
   * @param  mixed $opacity Opacidad
   * @return void
   */
  final public function fill($color, $left = 0, $top = 0, $width = '100%', $height = '100%', $opacity = 100) {
    $this->fix_dimset($width, $cw = $this->width());
    $this->fix_dimset($height, $ch = $this->height());
    $this->fix_dimset($x, $left, $cw, $width);
    $this->fix_dimset($y, $top, $ch, $height);

    $stroke = $this->allocate($this->resource, $color, $opacity);
    imagefilledrectangle($this->resource, $x, $y, $x + $width, $y + $height, $stroke);

    return $this;
  }


  /**
   * Rellenar area
   *
   * @param  mixed $from  Color de origen
   * @param  mixed $to    Color de destino
   * @param  mixed $left  Offset X
   * @param  mixed $top   Offset Y
   * @param  mixed $width   Ancho
   * @param  mixed $height  Altura
   * @param  mixed $step  Pixeles
   * @param  mixed $opacity Opacidad
   * @return void
   */
  final public function gradient($from, $to, $left, $top, $width = '100%', $height = '100%', $step = 1, $opacity = 100, $vertical = FALSE) {
    //http://blog.themeforest.net/tutorials/fun-with-the-php-gd-library-part-2/
    $base = $this->fix_rgbhex($from);
    $end  = $this->fix_rgbhex($to);

    $this->fix_dimset($width, $cw = $this->width());
    $this->fix_dimset($height, $ch = $this->height());
    $this->fix_dimset($x, $left, $cw, $width);
    $this->fix_dimset($y, $top, $ch, $height);

    $step = max(1, $step);
    $vertical = (boolean) $vertical;

    $max = is_true($vertical) ? $height : $width;
    $w   = is_true($vertical) ? $width : $step;
    $h   = is_true($vertical) ? $step : $height;

    foreach (array('r', 'g', 'b') as $m => $n) {
      ${$n . 'mod'} = ($end[$m] - $base[$m]) / ($max + 2);
    }

    for ($i = 0; $i < $max; $i += $step, is_true($vertical) ? $y += $step : $x += $step) {
      if (is_true($vertical) && ($diff = (($y + $h) - ($top + $height))) > 0) {
        $h -= $diff;
      } elseif (($diff = (($x + $w) -($left + $width))) > 0) {
        $w -= $diff;
      }

      $old[0] = ($rmod * $i) + $base[0];
      $old[1] = ($gmod * $i) + $base[1];
      $old[2] = ($bmod * $i) + $base[2];

      $color = $this->allocate($this->resource, $old, $opacity);
      imagefilledrectangle($this->resource, $x, $y, $x + $w, $y + $h, $color);
    }
    return $this;
  }



  // ---------------------------------------------------------------------------
  /**#@+
   * @ignore
   */
  final private function allocate( &$test, $color, $opacity = 100) {
    $old = $this->fix_rgbhex($color);
    if ($opacity < 100) {
      $alpha = (is_num($opacity, 0, 1) ? $opacity * 100 : min((int) $opacity, 100)) * 1.27;
      return imagecolorallocatealpha($test, $old[0], $old[1], $old[2], abs($alpha - 127));
    }
    return imagecolorallocate($test, $old[0], $old[1], $old[2]);
  }

  final private function filter() {
    $args = func_get_args();
    $test = array_shift($args);
    $type = is_string($test) ? constant('IMG_FILTER_' . strtoupper($test)) : $test;

    array_unshift($args, $type);
    array_unshift($args, $this->resource);

    call_user_func_array('imagefilter', $args);

    return $this;
  }

  final private function resample( &$tmp, $tx, $ty, $tw, $th, $sx, $sy, $sw, $sh) {
    if (($tw > $sw) OR ($th > $sh)) {
      return imagecopyresampled($tmp, $this->resource, $tx, $ty, $sx, $sy, $tw, $th, $sw, $sh);
    } elseif ( ! ($tw == $sw && $th == $sh)) {
      $rX = $sw / $tw;
      $rY = $sh / $th;
      $w  = 0;

      for ($y = 0; $y < $th; $y += 1) {
        $t  = 0;
        $ow = $w;
        $w  = round(($y +1) * $rY);

        for ($x = 0; $x < $tw; $x += 1) {
          $a  = 0;
          $ot = $t;
          $r  = $g = $b = 0;
          $t  = round(($x +1) *$rX);

          for ($u = 0; $u < ($w - $ow); $u += 1) {
            for ($p = 0; $p < ($t - $ot); $p += 1) {
              $c  = $this->getdot($ot + $p + $sx, $ow + $u + $sy);
              $r += array_shift($c);
              $g += array_shift($c);
              $b += array_shift($c);
              $a += 1;
            }
          }

          imagesetpixel($tmp, $x, $y, imagecolorclosest($tmp, $r / $a, $g / $a, $b / $a));
        }
      }
    }
  }

  final private function gray_value($r, $g, $b) {
    return round(($r * 0.3) + ($g * 0.59) + ($b * 0.11));
  }

  final private function gray_pixel($orig) {
    $gray = $this->gray_value($orig[0], $orig[1], $orig[2]);
    return array(0 => $gray, 1 => $gray, 2 => $gray);
  }

  final private function getdot($x = 0, $y = 0) {
    $test = imagecolorsforindex($this->resource, @imagecolorat($this->resource, $x, $y));
    return array_values($test);
  }

  final private function outerbox($test, $size = 5, $angle = 0, $file = NULL) {
    $file = realpath($file);

    if ( ! is_file($file)) {
      return array(
        'left' => 0,
        'top' => 0,
        'width' => imagefontwidth($size) * strlen($test),
        'height' => imagefontheight($size),
      );
    }

    $box = imagettfbbox($size, $angle, $file, $test);

    $xx = min(array($box[0], $box[2], $box[4], $box[6]));
    $yx = max(array($box[0], $box[2], $box[4], $box[6]));
    $xy = min(array($box[1], $box[3], $box[5], $box[7]));
    $yy = max(array($box[1], $box[3], $box[5], $box[7]));

    return array(
      'left' => $xx >= -1 ? - abs($xx + 1) : abs($xx + 2),
      'top' => abs($xy),
      'width' => $yx - $xx,
      'height' => $yy - $xy,
    );
  }


  final private function fix_alpha($tmp) {// TODO: PNG/GIF bad handling...
    if (in_array($this->type, array(PNG, GIF))) {
      $index = imagecolortransparent($tmp);

      if ($index >= 0) {
        $old = imagecolorsforindex($tmp, $index);

        if (is_array($old)) {
          $index = $this->allocate($tmp, $old, $old['alpha']);
        }

        imagefill($tmp, 0, 0, $index);
        imagecolortransparent($tmp, $index);
      } else {
        $old = $this->fix_rgbhex($this->transparency);

        imagealphablending($tmp, FALSE);
        $bgcolor = imagecolorallocatealpha($tmp, $old[0], $old[1], $old[2], $this->alpha);
        imagefill($tmp, 0, 0, $bgcolor);
        imagesavealpha($tmp, TRUE);
      }
    }
    return $tmp;
  }

  final private function fix_rgbhex($test) {
    if ($test === 'transparent') {
      $index = imagecolortransparent($this->resource);

      if ($index >= 0) {
        return array_values(imagecolorsforindex($this->resource, $index));
      }
      return $this->fix_rgbhex($this->transparency);
    } elseif (is_array($test)) {
      return $test;
    }

    $test = preg_replace('/[^a-fA-F0-9]/', '', $test);

    if (strlen($test) === 3) {
      $test = str_repeat(substr($test, 0, 1), 2)
            . str_repeat(substr($test, 1, 1), 2)
            . str_repeat(substr($test, 2, 1), 2);
    }

    $out[0] = hexdec(substr($test, 0, 2));
    $out[1] = hexdec(substr($test, 2, 2));
    $out[2] = hexdec(substr($test, 4, 2));

    return $out;
  }

  final private function fix_dimset( &$test, $offset, $max = NULL, $min = NULL) {
    if (strrpos($test, '%')) {
      $test = floor(((func_num_args() == 2 ? $offset : $max) / 100) * ((int) $test));
    }

    if (func_num_args() === 2) {
      $test = $test < 0 ? ($offset += 1) + $test : $test;
    } elseif (func_num_args() === 4) {
      if ($offset === 0) {
        $test = $min < $max ? floor(($max - $min) / 2) : 0;
      } else {
        $test = $offset < 0 ? ($offset += 1) +($max - $min) : $offset -= 1;
      }
    }
  }

  /**#@-*/
}

/* EOF: ./library/gd.php */
