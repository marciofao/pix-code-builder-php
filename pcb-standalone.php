<?php 

//this code is meant to run in any php hosting environment, not only wordpress

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
     exit(0);
    }

function pcb_formataCampo($id, $valor) {
    return $id . str_pad(strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
}

function pcb_calculaCRC16($dados) {
    $resultado = 0xFFFF;
    for ($i = 0; $i < strlen($dados); $i++) {
        $resultado ^= (ord($dados[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if ($resultado & 0x8000) {
                $resultado = ($resultado << 1) ^ 0x1021;
            } else {
                $resultado <<= 1;
            }
            $resultado &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($resultado), 4, '0', STR_PAD_LEFT));
}

function pcb_geraPix($chave, $idTx = '', $valor = 0.00) {
    
    $valor = floatval($valor);
    //die($valor);
    $resultado = "000201";
    $resultado .= pcb_formataCampo("26", "0014br.gov.bcb.pix" . pcb_formataCampo("01", $chave));
    $resultado .= "52040000"; // Código fixo
    $resultado .= "5303986";  // Moeda (Real)
    if ($valor > 0) {
        $resultado .= pcb_formataCampo("54", number_format($valor, 2, '.', ''));
    }
    $resultado .= "5802BR"; // País
    $resultado .= "5901N";  // Nome
    $resultado .= "6001C";  // Cidade
    $resultado .= pcb_formataCampo("62", pcb_formataCampo("05", $idTx ?: '***'));
    $resultado .= "6304"; // Início do CRC16
    $resultado .= pcb_calculaCRC16($resultado); // Adiciona o CRC16 ao final
    return $resultado;
}

// Exemplos de chave PIX
//
// E-mail: nome@exemplo.com.br
// CPF: 12345678901 (só números)
// CNPJ: 12345678000123 (só números)
// Celular: +5511912345678 (+55 + DDD + número)
//

function pcb_output_pix($valorTransacao = '0.00', $idTransacao = '', $chave = '') {

    if(isset($_POST['valorTransacao'])) {
        $valorTransacao = $_POST['valorTransacao'];
    }
    if(isset($_POST['idTransacao'])) {
        $idTransacao = $_POST['idTransacao'];
    }
    if($chave == '') {
       return false;
    }
    $codigoPix = pcb_geraPix($chave, $idTransacao, $valorTransacao);
    $qr_code_img_src = "https://quickchart.io/qr?text=" . urlencode($codigoPix);

    $response = [
        'codigoPix' => $codigoPix,
        'qr_code_img_src' => $qr_code_img_src,
        'valorTransacao' => $valorTransacao,
        'idTransacao' => $idTransacao,
    ];

    if(defined('DOING_AJAX') && DOING_AJAX) {
        return wp_send_json($response);
    }
    
    return $response;
}

if(isset($_GET['pix'])){
    if(!isset($_GET['v'])){
        die('Valor "v" não informado');
    } 

    $valor = $_GET['v'];
    $idTransacao = $_GET['id']??'';
    $codigoPix = $_GET['pix'];
    die(json_encode(pcb_output_pix($valor, $idTransacao, $codigoPix)));
}