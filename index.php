<?php

function identifica_comando_zpl($str, &$comando, &$parametros)
{
  $v_str = trim($str);
  $parametros = array();

  if (substr($v_str, 0, 1) == 'A') {
    $comando = substr($v_str, 0, 1);
    $parametros = explode(',', substr($v_str, 4));
  } elseif (stripos($v_str, 'XA') !== false) {
    // start of label
    $comando = substr($v_str, 0, 2);
  } elseif (stripos($v_str, 'LH') !== false) {
    // label home
    $comando = substr($v_str, 0, 2);
    $parametros = explode(',', substr($v_str, 2));
  } elseif (stripos($v_str, 'FX') !== false) {
    // ZPL comments
    $comando = substr($v_str, 0, 2);
    $parametros = substr($v_str, 2);
  } elseif (stripos($v_str, 'FO') !== false) {
    // field origin (cursor positioning)
    $comando = substr($v_str, 0, 2);
    $parametros = explode(',', substr($v_str, 2));
  } elseif (stripos($v_str, 'GB') !== false) {
    // graphic box (rectangle)
    $comando = substr($v_str, 0, 2);
    $parametros = explode(',', substr($v_str, 2));
  } elseif (stripos($v_str, 'FD') !== false) {
    // field data (label text)
    $comando = substr($v_str, 0, 2);
    $parametros[0] = substr($v_str, 2);
  } elseif (stripos($v_str, 'FW') !== false) {
    // field orientation (text orientation)
    $comando = substr($v_str, 0, 2);
    $parametros[0] = substr($v_str, 2);
  } elseif (stripos($v_str, 'FS') !== false) {
    // field separator (command end)
    $comando = substr($v_str, 0, 2);
  } elseif (stripos($v_str, 'IS') !== false) {
    // image save (saves content to Zebra memory)
    $comando = substr($v_str, 0, 2);
    $parametros = explode(',', substr($v_str, 4));
  } elseif (stripos($v_str, 'IM') !== false) {
    // image save (saves content to Zebra memory)
    $comando = substr($v_str, 0, 2);
    $parametros[0] = substr($v_str, 4);
  } else {
    $comando = '*** NOT IDENTIFIED *** ' . substr($v_str, 0, 2);
  }
}

function box($x1, $y1, $x2, $y2, $tam)
{
  return "
    var rect = new Konva.Rect({
        x: $x1,
        y: $y1,
        width: $x2,
        height: $y2,
        stroke: 'white',
        strokeWidth: ($tam / 10)
    });
    layer.add(rect);
  ";
}

function text($x, $y, $str, $altura = 1, $orientacao = null, $largura, $pos_x_global = 0, $pos_y_global = 0, $cor_texto = 'green', $padding = 0)
{
  $v_orientacao_js = 0;
  $ajuste_x = 0;
  if ($orientacao == 'R') {
    $v_orientacao_js = 90;
  } elseif ($orientacao == 'N') {
    $v_orientacao_js = 0;
    $ajuste_x = -25;
  }

  $x2 = $x + 12 + $pos_x_global + $ajuste_x + 70;
  $y2 = $y; 

  $altura = floatval($altura);
  $largura = floatval($largura);

  $fontSize = 10;

  $str = addslashes($str);

  return "
    var txtCampo = new Konva.Text({
        x: $x2,
        y: $y2 + current_y_position,
        text: '$str',
        fontSize: $fontSize,
        fontFamily: 'Arial',
        fontStyle: 'bold',
        fill: '$cor_texto',
        rotation: $v_orientacao_js,
        align: 'center',
        verticalAlign: 'middle'
    });
    current_y_position += 10;
    layer.add(txtCampo);
  ";
}

// Example usage of the text function
$parametros = array_merge($_POST, $_GET);
$str_zpl_parametro = isset($parametros['zpl']) ? $parametros['zpl'] : '';

// Replace '~' with standard separator '^'
$str_zpl_parametro = str_replace('~', '^', $str_zpl_parametro);

$comandos_zpl = explode('^', $str_zpl_parametro);

$pos_x = $pos_y = $label_home_x = $label_home_y = 0;
$orientacao = 'N';
$js = '';
$zpl_format = '';
$separador = '';
$altura_fonte = $largura_fonte = 10;
$cor_texto = 'black';

foreach ($comandos_zpl as $zpl) {
  if (!empty($zpl)) {
    identifica_comando_zpl($zpl, $cmd, $param);

    if ($cmd == 'FO') {
      if (count($param) == 2) {
        $pos_x = $param[0];
        $pos_y = $param[1];
      }
    } elseif ($cmd == 'FW') {
      $orientacao = $param[0];
    } elseif ($cmd == 'GB') {
      $js .= box($pos_x, $pos_y, $param[0], $param[1], $param[2]);
    } elseif ($cmd == 'FD') {
      $js .= text($pos_x, $pos_y, $param[0], $altura_fonte, $orientacao, $largura_fonte, $label_home_x, $label_home_y, $cor_texto);
    } elseif ($cmd == 'A') {
      $altura_fonte = isset($param[0]) ? $param[0] : $altura_fonte;
      $largura_fonte = isset($param[1]) ? $param[1] : $largura_fonte;
    } elseif ($cmd == 'LH') {
      $label_home_x = $param[0];
      $label_home_y = $param[1];
    } elseif ($cmd == 'IS') {
      $dir_base = dirname(__FILE__);
      $nome_arquivo = $param[0];
      $bytes = @file_put_contents("$dir_base/$nome_arquivo", $js);

      $js .= ($bytes > 0)
        ? "alert('file $nome_arquivo written... bytes=$bytes');"
        : "alert('error generating file $dir_base/$nome_arquivo');";
    } elseif ($cmd == 'IM') {
      $cor_texto = 'green';
      $dir_base = dirname(__FILE__);
      $nome_arquivo = $param[0];
      if ($str_arquivo = @file_get_contents("$dir_base/$nome_arquivo")) {
        $js = $str_arquivo . $js;
      } else {
        $js .= "alert('error loading file $dir_base/$nome_arquivo');";
      }
    } elseif ($cmd == 'BY') {
      $module_width = isset($param[0]) ? $param[0] : $module_width;
      $barcode_height = isset($param[2]) ? $param[2] : $barcode_height;
    } elseif ($cmd == 'BC') {
      $js .= "JsBarcode('#barcode', '$param[0]', { format: 'CODE128', width: $module_width, height: $barcode_height });";
    } elseif ($cmd == '*** NOT IDENTIFIED ***') {
      $js .= "alert('command ZPL not  identified: $param[0]');";
    }

    $zpl_format .= "$separador\"" . trim($zpl) . "\\n\"";
    $separador = "+\n";
  }
}

# $js .= "\ndocument.getElementById(\"txtFormattedZebra\").value = $zpl_format;";
echo $js;
