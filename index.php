<?php
ini_set('display_errors', false);
require_once('../../autoload.php');
require_once('../../php/variaveis.php');
require_once("$lib_dir/funcoes.php");
require_once("$lib_dir/funcoes_rest.php");
//require_once("$class_dir/class_ws.php");
$log_file = "$path/logs/alive.log";



$verb = strtoupper($_SERVER['REQUEST_METHOD']);
$versao = isset($_POST['versao']) && ($_POST['versao'] != '') ? $_POST['versao'] : $_GET['versao'];
$classe = isset($_POST['classe']) && ($_POST['classe'] != '') ? $_POST['classe'] : $_GET['classe'];
$method = isset($_POST['method']) && ($_POST['method'] != '') ? $_POST['method'] : $_GET['method'];
$versao = preg_replace('/[^0-9a-zA-Z_]/', '', $versao);
$classe = preg_replace('/[^0-9a-zA-Z_]/', '', $classe);
$method = preg_replace('/[^0-9a-zA-Z_]/', '', $method);
$ws = new class_ws();
$metodo = '';
$params = '';
if ($verb == 'POST') {
  $json = file_get_contents("php://input");
  if (!validaJson($json)) {
    deliver_response($_GET['format'], array('code' => 400, 'status' => 400, 'data' => 'Formato JSON inválido 2'));
  } else {
    $content_obj = json_decode($json, true);
    $content = (array) $content_obj;
  }

  if (isset($content) && !empty($content)) {

    if ($method == 'dados') {
      if ($classe == 'ios') {
        if ((isset($content['samples']) && ($content['samples'] != '')) && (isset($content['ssid']) && ($content['ssid'] != ''))) {
          $params = array('alive_json' => json_encode($content['samples'], JSON_UNESCAPED_SLASHES), 'ssid' => $content['ssid']);
          $metodo = 'put_dados';
        } else {
          deliver_response($_GET['format'], array('code' => 400, 'status' => 400, 'data' => 'Formato JSON inválido'));
        }
      }
    } else if ($method == 'ficha_medica') {
      if (is_array($content) && (isset($content['ssid']) && ($content['ssid'] != ''))) {
        if (array_key_exists('doador', $content) && isset($content['doador'])) {
          if ($content['doador'] === true) {
            $content['doador'] = 'S';
          } elseif ($content['doador'] === false) {
            $content['doador'] = 'N';
          }
        }
        $params = $content;
        $metodo = 'put_ficha_medica';
      } else {

        deliver_response($_GET['format'], array('code' => 400, 'status' => 400, 'data' => 'Formato JSON inválido'));
      }
    } else if ($method == 'get_ficha_medica') {
      if (is_array($content) && (isset($content['ssid']) && ($content['ssid'] != ''))) {
        $params = $content;
        $metodo = 'get_ficha_medica';
      } else {

        deliver_response($_GET['format'], array('code' => 400, 'status' => 400, 'data' => 'Formato JSON inválido'));
      }
    }

    $result = $ws->metodo($versao, 'alive', $metodo, $params);

    if ($result) {
      $ret = true;
    }
  }
}

if (!$ret) {
  $status = $ws->get_cod_erro();
  if (in_array($status, array(740, 741, 742))) {
    $data = $ws->get_erro();
    deliver_response($_GET['format'], array('code' => $status, 'status' => $status, 'data' => $data));
  } else {
    deliver_response($_GET['format'], array('code' => 500, 'status' => 500, 'data' => $result));
  }
} else {
  deliver_response($_GET['format'], array('code' => 200, 'status' => 200, 'data' => $result));
}

function validaJson($data = NULL)
{
  if (!empty($data)) {
    json_decode($data);
    return (json_last_error() === JSON_ERROR_NONE);
  }
  return false;
}

?>