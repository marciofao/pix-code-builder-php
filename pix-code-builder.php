<?php
/**
 * Plugin Name: Pix Code Builder
 * Description: Gera código de pagamento PIX para WordPress
 * Author: Marcio Fao
 * Author URI: https://www.marciofao.com.br
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * License: GPL2
 * Domain Path: /languages
 * Text Domain: pix-code-builder
 * Version: 1.0
 */

require_once('admin-page-options.php');

// Add a "Settings" link to the plugin entry on the Plugins list page
function pcb_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=pix-code-builder-settings">' . __('Settings', 'pix-code-builder') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pcb_add_settings_link');


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
    // Convert BRL format to a float
    $valor = str_replace('.', '', $valor); // Remove the thousands separator
    $valor = str_replace(',', '.', $valor); // Replace the decimal separator
    $valor = floatval($valor);
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

function pcb_output_pix($valorTransacao = '0.00', $idTransacao = '') {
    $chave = get_option( 'pcb_pix_key' );
    if(isset($_POST['valorTransacao'])) {
        $valorTransacao = $_POST['valorTransacao'];
    }
    if(isset($_POST['idTransacao'])) {
        $idTransacao = $_POST['idTransacao'];
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
//allow ajax request for pcb_output_pix
add_action('wp_ajax_pcb_output_pix', 'pcb_output_pix');
add_action('wp_ajax_nopriv_pcb_output_pix', 'pcb_output_pix');
add_action('wp_enqueue_scripts', 'pcb_enqueue_scripts');

// Enqueue JavaScript and pass options to it
function pcb_enqueue_scripts() {
    wp_enqueue_script(
        'pcb-custom-script',
        plugins_url('js/pcb-custom-script.js', __FILE__),
        array('jquery'),
        '1.0',
        true
    );

    // Pass the CSS selector and AJAX URL to the JavaScript file
    wp_localize_script('pcb-custom-script', 'pcbOptions', array(
        'valueSelector' => get_option('pcb_value_css_selector'),
        'buttonText' => get_option('pcb_button_text', 'Gerar QR Code PIX'),
        'ajaxUrl' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'pcb_enqueue_scripts');

// Create the shortcode
function pcb_shortcode_pix_button($atts) {
    $atts = shortcode_atts(array(
        'idTransacao' => '',
    ), $atts, 'pcb_pix_button');

    // Generate the button HTML
    $button_text = esc_html(get_option('pcb_button_text', 'Gerar QR Code PIX'));
    $button_html = '<button id="pcb-pix-button" data-id="' . esc_attr($atts['idTransacao']) . '">' . $button_text . '</button>';

    return $button_html;
}
add_shortcode('pcb_pix_button', 'pcb_shortcode_pix_button');
