<?php

/**
 * Application spanish strings
 */

$lang['controller_already_exists'] = 'El controlador %{name} ya existe';
$lang['controller_missing'] = 'Hace falta un controlador';
$lang['controller_not_exists'] = 'El controlador %{name} no existe';
$lang['action_already_exists'] = 'La acción %{controller}#%{name} ya existe';
$lang['action_missing'] = 'Hace falta una acción';

$lang['verifying_generator'] = 'Comprobando generador';
$lang['verifying_installation'] = 'Comprobando instalación';

$lang['directory_must_be_empty'] = 'La carpeta de destino debe estar vacía';

$lang['counting_files'] = 'Cantidad de archivos: %{length}';
$lang['sizing_files'] = 'Peso total: %{size}';

$lang['action_method_building'] = 'Generando acción para %{controller}#%{name}';
$lang['action_route_building'] = 'Generando ruta para %{controller}#%{name}';
$lang['action_view_building'] = 'Generando vista para %{controller}#%{name}';

$lang['controller_class_building'] = 'Generando clase por defecto del controlador %{name}';
$lang['controller_route_building'] = 'Generando ruta por defecto del controlador %{name}';
$lang['controller_view_building'] = 'Generando vista por defecto del controlador %{name}';

$lang['usage'] = <<<HELP

  \clight_gray(Muestra el estado actual de la aplicación)\c
    \bgreen(app:status)\b

  \clight_gray(Genera y verifica la estructura de la aplicación)\c
    \bgreen(app:create)\b \bcyan(app)\b [--force]

  \clight_gray(Generador del controlador por defecto)\c
    \bgreen(app:controller)\b \bcyan(name)\b [--parent=class] [--no-view]

  \clight_gray(Generador de la acción por defecto)\c
    \bgreen(app:action)\b \bcyan(controller:name)\b [--method=get|put|post|delete] [--route=X] [--path=Y] [--no-view]

HELP;

/* EOF: ./stack/scripts/application/locale/es.php */
