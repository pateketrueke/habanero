<?php

/**
 * MVC view
 */

class view extends prototype
{

  // TODO: implement anything better?
  final public static function load($file, array $vars = array())
  {
    return render($file, TRUE, $vars);
  }

}

/* EOF: ./lib/tetl/mvc/view.php */
