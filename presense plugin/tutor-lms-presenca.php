<?php
/**
 * Plugin Name: Tutor LMS - Lista de Presença com QR Code
 * Description: Gerencia a presença dos alunos por meio de QR Codes para cada aula no Tutor LMS e permite confirmação manual de presença.
 * Version: 1.6.4
 * Author: Seu Nome
 * Text Domain: tutor-lms-presenca
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Função para incluir os widgets do Elementor
function tutor_lms_elementor_include_widgets() {
    if (did_action('elementor/loaded')) {
        include_once plugin_dir_path(__FILE__) . 'includes/class-checkin-button-widget.php';
        include_once plugin_dir_path(__FILE__) . 'includes/class-qrcode-widget.php';
        
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Tutor_LMS_Checkin_Button_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Tutor_LMS_QRCode_Widget());
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . __("O plugin Tutor LMS - Widgets no Elementor requer o Elementor para estar ativo.", 'tutor-lms-presenca') . '</p></div>';
        });
    }
}
add_action('elementor/widgets/init', 'tutor_lms_elementor_include_widgets');

if (!function_exists('criar_cpt_lista_presenca')) {
    function criar_cpt_lista_presenca() {
        $labels = array(
            'name'               => __('Listas de Presença', 'tutor-lms-presenca'),
            'singular_name'      => __('Lista de Presença', 'tutor-lms-presenca'),
            'menu_name'          => __('Listas de Presença', 'tutor-lms-presenca'),
            'name_admin_bar'     => __('Lista de Presença', 'tutor-lms-presenca'),
            'add_new'            => __('Adicionar Nova', 'tutor-lms-presenca'),
            'add_new_item'       => __('Adicionar Nova Lista de Presença', 'tutor-lms-presenca'),
            'new_item'           => __('Nova Lista de Presença', 'tutor-lms-presenca'),
            'edit_item'          => __('Editar Lista de Presença', 'tutor-lms-presenca'),
            'view_item'          => __('Ver Lista de Presença', 'tutor-lms-presenca'),
            'all_items'          => __('Todas as Listas de Presença', 'tutor-lms-presenca'),
            'search_items'       => __('Buscar Listas de Presença', 'tutor-lms-presenca'),
            'not_found'          => __('Nenhuma Lista de Presença encontrada.', 'tutor-lms-presenca'),
            'not_found_in_trash' => __('Nenhuma Lista de Presença no Lixo.', 'tutor-lms-presenca')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'show_in_menu'       => true,
            'menu_position'      => 30,
            'supports'           => array('title', 'thumbnail'),
            'menu_icon'          => 'dashicons-list-view',
        );
        register_post_type('lista_presenca', $args);
    }
}
add_action('init', 'criar_cpt_lista_presenca');

if (!function_exists('criar_cpt_grupos_usuarios')) {
    function criar_cpt_grupos_usuarios() {
        $labels = array(
            'name'               => __('Grupos de Usuários', 'tutor-lms-presenca'),
            'singular_name'      => __('Grupo de Usuário', 'tutor-lms-presenca'),
            'menu_name'          => __('Grupos de Usuários', 'tutor-lms-presenca'),
            'name_admin_bar'     => __('Grupo de Usuário', 'tutor-lms-presenca'),
            'add_new'            => __('Adicionar Novo', 'tutor-lms-presenca'),
            'add_new_item'       => __('Adicionar Novo Grupo de Usuário', 'tutor-lms-presenca'),
            'new_item'           => __('Novo Grupo de Usuário', 'tutor-lms-presenca'),
            'edit_item'          => __('Editar Grupo de Usuário', 'tutor-lms-presenca'),
            'view_item'          => __('Ver Grupo de Usuário', 'tutor-lms-presenca'),
            'all_items'          => __('Todos os Grupos de Usuários', 'tutor-lms-presenca'),
            'search_items'       => __('Buscar Grupos de Usuários', 'tutor-lms-presenca'),
            'not_found'          => __('Nenhum Grupo de Usuário encontrado.', 'tutor-lms-presenca'),
            'not_found_in_trash' => __('Nenhum Grupo de Usuário no Lixo.', 'tutor-lms-presenca')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'show_in_menu'       => 'edit.php?post_type=lista_presenca',
            'supports'           => array('title'),
            'menu_icon'          => 'dashicons-groups',
        );
        register_post_type('grupo_usuario', $args);
    }
}
add_action('init', 'criar_cpt_grupos_usuarios');

if (!function_exists('evolution_api_settings_init')) {
    function evolution_api_settings_init() {
        add_settings_section(
            'evolution_api_section',
            __('Configurações da Evolution API', 'tutor-lms-presenca'),
            '__return_null',
            'evolution-api'
        );

        $fields = [
            'evolution_domain' => __('Domain', 'tutor-lms-presenca'),
            'evolution_instance' => __('Instance', 'tutor-lms-presenca'),
            'evolution_apikey' => __('API Key', 'tutor-lms-presenca'),
            'evolution_msg_checkin' => __('Mensagem de Check-in', 'tutor-lms-presenca'),
            'evolution_msg_reminder' => __('Mensagem de Lembrete', 'tutor-lms-presenca'),
            'evolution_msg_falha' => __('Mensagem de Falta', 'tutor-lms-presenca')
        ];

        foreach ($fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                function() use ($field) { evolution_field_render($field); },
                'evolution-api',
                'evolution_api_section'
            );
            register_setting('evolution-api', $field);
        }
    }
}

if (!function_exists('evolution_field_render')) {
    function evolution_field_render($field) {
        $value = get_option($field, '');
        echo '<input type="text" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '" />';
    }
}

if (!function_exists('evolution_api_settings_page')) {
    function evolution_api_settings_page() {
        echo '<form action="options.php" method="post">';
        echo '<h1>' . __('Configurações do Evolution API', 'tutor-lms-presenca') . '</h1>';
        settings_fields('evolution-api');
        do_settings_sections('evolution-api');
        submit_button();
        echo '</form>';
    }
}

if (!function_exists('registrar_menu_plugin')) {
    function registrar_menu_plugin() {
        add_submenu_page(
            'edit.php?post_type=lista_presenca',
            __('Configurações da Evolution API', 'tutor-lms-presenca'),
            __('Configurações API', 'tutor-lms-presenca'),
            'manage_options',
            'evolution-api',
            'evolution_api_settings_page'
        );
        add_submenu_page(
            'edit.php?post_type=lista_presenca',
            __('Relatórios de Presença por Grupo', 'tutor-lms-presenca'),
            __('Relatórios por Grupo', 'tutor-lms-presenca'),
            'manage_options',
            'relatorios-presenca-grupo',
            'exibir_pagina_relatorios_grupo'
        );
        add_submenu_page(
            'edit.php?post_type=lista_presenca',
            __('Relatórios de Presença por Lista', 'tutor-lms-presenca'),
            __('Relatórios por Lista', 'tutor-lms-presenca'),
            'manage_options',
            'relatorios-presenca-lista',
            'exibir_pagina_relatorios_lista'
        );
    }
}
add_action('admin_menu', 'registrar_menu_plugin');

if (!function_exists('adicionar_metabox_infos_adicionais')) {
    function adicionar_metabox_infos_adicionais() {
        add_meta_box(
            'info_adicionais',
            __('Informações Adicionais do Lista de Presença', 'tutor-lms-presenca'),
            'exibir_metabox_infos_adicionais',
            'lista_presenca',
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'adicionar_metabox_infos_adicionais');

if (!function_exists('exibir_metabox_infos_adicionais')) {
    function exibir_metabox_infos_adicionais($post) {
        $tipo = get_post_meta($post->ID, 'tipo_lista', true) ?: 'presencial';
        $endereco = get_post_meta($post->ID, 'endereco_lista', true) ?: '';
        $link_zoom = get_post_meta($post->ID, 'link_zoom_lista', true) ?: '';
        $cronograma = get_post_meta($post->ID, 'cronograma_lista', true) ?: '';
        $instrucoes = get_post_meta($post->ID, 'instrucoes_lista', true) ?: '';

        echo '<label for="tipo_lista">' . __('Tipo de Lista de Presença:', 'tutor-lms-presenca') . '</label><br>';
        echo '<select id="tipo_lista" name="tipo_lista">
                <option value="presencial" ' . selected($tipo, 'presencial', false) . '>' . __('Presencial', 'tutor-lms-presenca') . '</option>
                <option value="online" ' . selected($tipo, 'online', false) . '>' . __('Online', 'tutor-lms-presenca') . '</option>
              </select><br><br>';

        echo '<div id="campo_endereco" style="' . ($tipo === 'online' ? 'display:none;' : '') . '">
                <label for="endereco_lista">' . __('Endereço:', 'tutor-lms-presenca') . '</label>
                <input type="text" id="endereco_lista" name="endereco_lista" value="' . esc_attr($endereco) . '"><br><br>
              </div>';

        echo '<div id="campo_zoom_link" style="' . ($tipo === 'presencial' ? 'display:none;' : '') . '">
                <label for="link_zoom_lista">' . __('Link do Zoom:', 'tutor-lms-presenca') . '</label>
                <input type="url" id="link_zoom_lista" name="link_zoom_lista" value="' . esc_attr($link_zoom) . '"><br><br>
              </div>';

        echo '<label for="cronograma_lista">' . __('Cronograma:', 'tutor-lms-presenca') . '</label><br>';
        echo '<textarea id="cronograma_lista" name="cronograma_lista" rows="4" style="width:100%;">' . esc_textarea($cronograma) . '</textarea><br><br>';

        echo '<label for="instrucoes_lista">' . __('Instruções para Participantes:', 'tutor-lms-presenca') . '</label><br>';
        echo '<textarea id="instrucoes_lista" name="instrucoes_lista" rows="4" style="width:100%;">' . esc_textarea($instrucoes) . '</textarea><br>';
        
        echo '
        <script type="text/javascript">
            document.getElementById("tipo_lista").addEventListener("change", function() {
                var tipo = this.value;
                document.getElementById("campo_endereco").style.display = (tipo === "online") ? "none" : "block";
                document.getElementById("campo_zoom_link").style.display = (tipo === "presencial") ? "none" : "block";
            });
        </script>';
    }
}

if (!function_exists('salvar_infos_adicionais')) {
    function salvar_infos_adicionais($post_id) {
        if (isset($_POST['tipo_lista'])) {
            update_post_meta($post_id, 'tipo_lista', sanitize_text_field($_POST['tipo_lista']));
        }
        if (isset($_POST['endereco_lista'])) {
            update_post_meta($post_id, 'endereco_lista', sanitize_text_field($_POST['endereco_lista']));
        }
        if (isset($_POST['link_zoom_lista'])) {
            update_post_meta($post_id, 'link_zoom_lista', esc_url_raw($_POST['link_zoom_lista']));
        }
        if (isset($_POST['cronograma_lista'])) {
            update_post_meta($post_id, 'cronograma_lista', sanitize_textarea_field($_POST['cronograma_lista']));
        }
        if (isset($_POST['instrucoes_lista'])) {
            update_post_meta($post_id, 'instrucoes_lista', sanitize_textarea_field($_POST['instrucoes_lista']));
        }
    }
}
add_action('save_post', 'salvar_infos_adicionais');

if (!function_exists('adicionar_metabox_data_hora')) {
    function adicionar_metabox_data_hora() {
        add_meta_box(
            'data_hora',
            __('Data e Hora da Aula', 'tutor-lms-presenca'),
            'exibir_metabox_data_hora',
            'lista_presenca',
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'adicionar_metabox_data_hora');

if (!function_exists('exibir_metabox_data_hora')) {
    function exibir_metabox_data_hora($post) {
        $inicio = get_post_meta($post->ID, 'inicio_aula', true) ?: '';
        $fim = get_post_meta($post->ID, 'fim_aula', true) ?: '';

        echo '<label for="inicio_aula">' . __('Início da Aula:', 'tutor-lms-presenca') . '</label><br>';
        echo '<input type="datetime-local" id="inicio_aula" name="inicio_aula" value="' . esc_attr($inicio) . '"><br><br>';
        echo '<label for="fim_aula">' . __('Fim da Aula:', 'tutor-lms-presenca') . '</label><br>';
        echo '<input type="datetime-local" id="fim_aula" name="fim_aula" value="' . esc_attr($fim) . '"><br>';
        
        echo '<button id="enviar_checkin_btn" class="button">' . __('Enviar Mensagem de Check-in', 'tutor-lms-presenca') . '</button>';

        ?>
        <script type="text/javascript">
            document.getElementById('enviar_checkin_btn').addEventListener('click', function() {
                var dataInicio = document.getElementById('inicio_aula').value;
                var tituloAula = '<?php echo addslashes(get_the_title($post->ID)); ?>';

                var data = {
                    action: 'enviar_checkin_mensagem',
                    inicio: dataInicio,
                    titulo: tituloAula,
                    post_id: '<?php echo $post->ID; ?>'
                };

                jQuery.post(ajaxurl, data, function(response) {
                    alert(response.data);
                });
            });
        </script>
        <?php
    }
}

add_action('wp_ajax_enviar_checkin_mensagem', 'ajax_enviar_checkin_mensagem');
if (!function_exists('ajax_enviar_checkin_mensagem')) {
    function ajax_enviar_checkin_mensagem() {
        check_ajax_referer('enviar_mensagem', 'security');
        
        $aula_id = intval($_POST['post_id']);
        $inicio = sanitize_text_field($_POST['inicio']);
        $titulo = sanitize_text_field($_POST['titulo']);
        
        $grupos_vinculados = get_post_meta($aula_id, 'grupos_lista', true) ?: [];
        foreach ($grupos_vinculados as $grupo_id) {
            $grupo_usuarios = get_post_meta($grupo_id, 'usuarios_grupo', true);
            if ($grupo_usuarios) {
                foreach ($grupo_usuarios as $usuario_id) {
                    $whatsapp = get_user_meta($usuario_id, 'whatsapp', true);
                    $mensagem = sprintf(
                        'Lembre-se de marcar presença na aula "%s" iniciando em %s.',
                        $titulo,
                        date_format(date_create($inicio), 'd/m/Y H:i')
                    );
                    enviar_mensagem_whatsapp($whatsapp, $mensagem);
                }
            }
        }
        wp_send_json_success(__('Mensagens de Check-in enviadas com sucesso.', 'tutor-lms-presenca'));
    }
}

if (!function_exists('enviar_mensagem_whatsapp')) {
    function enviar_mensagem_whatsapp($number, $message) {
        $domain = get_option('evolution_domain');
        $instance = get_option('evolution_instance');
        $apikey = get_option('evolution_apikey');

        if (!$domain || !$instance || !$apikey) {
            error_log('Evolution API: Configuração ausente de domínio, instância ou API key.');
            return;
        }

        $url = trailingslashit($domain) . 'message/sendText/' . $instance;
        $data = [
            'number' => $number,
            'text' => $message
        ];

        $args = [
            'body' => wp_json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json',
                'apikey' => $apikey
            ]
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('Erro ao enviar mensagem: ' . $response->get_error_message());
        } else {
            $body = wp_remote_retrieve_body($response);
            error_log('Resposta da Evolution API: ' . $body);
        }

        return $response;
    }
}

if (!function_exists('salvar_data_hora_aula')) {
    function salvar_data_hora_aula($post_id) {
        if (isset($_POST['inicio_aula'])) {
            update_post_meta($post_id, 'inicio_aula', sanitize_text_field($_POST['inicio_aula']));
        }
        if (isset($_POST['fim_aula'])) {
            update_post_meta($post_id, 'fim_aula', sanitize_text_field($_POST['fim_aula']));
        }
    }
}
add_action('save_post', 'salvar_data_hora_aula');

add_action('admin_init', 'evolution_api_settings_init');

if (!function_exists('gerar_botao_checkin')) {
    function gerar_botao_checkin($atts) {
        if (!isset($atts['aula_id'])) {
            return '';
        }
        
        $aula_id = intval($atts['aula_id']);
        $url_checkin = esc_url(add_query_arg('checkin', $aula_id, home_url()));

        return '<a href="' . $url_checkin . '" class="button">' . __('Fazer Check-in', 'tutor-lms-presenca') . '</a>';
    }
}
add_shortcode('botao_checkin', 'gerar_botao_checkin');

/* Shortcode para botão de check-in estático */
function gerar_botao_checkin($atts) {
    if (empty($atts['aula_id'])) {
        return '';
    }
    
    $aula_id = intval($atts['aula_id']);
    $url_checkin = esc_url(add_query_arg('checkin', $aula_id, home_url()));
    
    return sprintf('<a href="%s" class="button">%s</a>', $url_checkin, __('Fazer Check-in', 'tutor-lms-presenca'));
}
add_shortcode('botao_checkin', 'gerar_botao_checkin');

/* Shortcode para botão de check-in dinâmico */
function gerar_botao_checkin_dinamico() {
    if (!is_singular('lista_presenca')) {
        return '';
    }
    
    global $post;
    $aula_id = intval($post->ID);
    $url_checkin = esc_url(add_query_arg('checkin', $aula_id, home_url()));
    
    return sprintf('<a href="%s" class="button">%s</a>', $url_checkin, __('Fazer Check-in', 'tutor-lms-presenca'));
}
add_shortcode('botao_checkin_dinamico', 'gerar_botao_checkin_dinamico');

/* Shortcode para gerar QR Code estático */
function gerar_qrcode_checkin($atts) {
    if (empty($atts['aula_id'])) {
        return '';
    }
    
    $aula_id = intval($atts['aula_id']);
    $url_checkin = esc_url(add_query_arg('checkin', $aula_id, home_url()));
    $qr_code_url = esc_url('https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($url_checkin));
    
    return sprintf('<img src="%s" alt="%s">', $qr_code_url, esc_attr__('QR Code para Check-in', 'tutor-lms-presenca'));
}
add_shortcode('qrcode_checkin', 'gerar_qrcode_checkin');

/* Shortcode para gerar QR Code dinâmico */
function gerar_qrcode_checkin_dinamico() {
    if (!is_singular('lista_presenca')) {
        return '';
    }
    
    global $post;
    $aula_id = intval($post->ID);
    $url_checkin = esc_url(add_query_arg('checkin', $aula_id, home_url()));
    $qr_code_url = esc_url('https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($url_checkin));
    
    return sprintf('<img src="%s" alt="%s">', $qr_code_url, esc_attr__('QR Code para Check-in', 'tutor-lms-presenca'));
}
add_shortcode('qrcode_checkin_dinamico', 'gerar_qrcode_checkin_dinamico');

if (!function_exists('gerar_qrcode_checkin')) {
    function gerar_qrcode_checkin($atts) {
        if (!isset($atts['aula_id'])) {
            return '';
        }
        
        $aula_id = intval($atts['aula_id']);
        $url_checkin = esc_url(add_query_arg('checkin', $aula_id, home_url()));
        $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($url_checkin);

        return '<img src="' . esc_url($qr_code_url) . '" alt="' . esc_attr__('QR Code para Check-in', 'tutor-lms-presenca') . '">';
    }
}
add_shortcode('qrcode_checkin', 'gerar_qrcode_checkin');

if (!function_exists('processar_checkin_usuario')) {
    function processar_checkin_usuario() {
        if (isset($_GET['checkin'])) {
            $aula_id = intval($_GET['checkin']);
            $current_user = wp_get_current_user();

            if ($current_user->ID && $aula_id) {
                $grupos_vinculados = get_post_meta($aula_id, 'grupos_lista', true) ?: [];
                $participa = false;

                foreach ($grupos_vinculados as $grupo_id) {
                    $grupo_usuarios = get_post_meta($grupo_id, 'usuarios_grupo', true);
                    if ($grupo_usuarios && in_array($current_user->ID, $grupo_usuarios)) {
                        $participa = true;
                        break;
                    }
                }

                if ($participa) {
                    $presencas = get_post_meta($aula_id, 'presenca_manual', true) ?: [];
                    if (!in_array($current_user->ID, $presencas)) {
                        $presencas[] = $current_user->ID;
                        update_post_meta($aula_id, 'presenca_manual', $presencas);

                        $mensagem_checkin = get_option('evolution_msg_checkin', 'Sua presença foi registrada. Obrigado!');
                        $whatsapp = get_user_meta($current_user->ID, 'whatsapp', true);
                        enviar_mensagem_whatsapp($whatsapp, $mensagem_checkin);

                        $pagina_checkin = get_page_by_title(__('Confirmação de Presença', 'tutor-lms-presenca'));
                        if ($pagina_checkin) {
                            wp_redirect(get_permalink($pagina_checkin->ID) . '?checkin=' . $aula_id . '&user_id=' . $current_user->ID);
                            exit;
                        }
                    }
                }
            }
        }
    }
}
add_action('init', 'processar_checkin_usuario');

if (!function_exists('mostrar_mensagem_sucesso_checkin')) {
    function mostrar_mensagem_sucesso_checkin() {
        if (isset($_GET['checkin_sucesso'])) {
            echo '<div class="notice notice-success"><p>' . __('Sua presença foi registrada com sucesso.', 'tutor-lms-presenca') . '</p></div>';
        }
    }
}
add_action('wp_footer', 'mostrar_mensagem_sucesso_checkin');

if (!function_exists('adicionar_metabox_grupo_usuarios')) {
    function adicionar_metabox_grupo_usuarios() {
        add_meta_box(
            'grupo_usuarios',
            __('Selecionar Usuários', 'tutor-lms-presenca'),
            'exibir_metabox_grupo_usuarios',
            'grupo_usuario',
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'adicionar_metabox_grupo_usuarios');

if (!function_exists('exibir_metabox_grupo_usuarios')) {
    function exibir_metabox_grupo_usuarios($post) {
        $usuarios = get_users();
        $selecionados = get_post_meta($post->ID, 'usuarios_grupo', true) ?: [];
        echo '<ul>';
        foreach ($usuarios as $usuario) {
            $checked = in_array($usuario->ID, $selecionados) ? 'checked' : '';
            echo '<li><input type="checkbox" name="usuarios_grupo[]" value="' . esc_attr($usuario->ID) . '" ' . $checked . '> ' . esc_html($usuario->display_name) . '</li>';
        }
        echo '</ul>';
    }
}

if (!function_exists('salvar_usuarios_grupo')) {
    function salvar_usuarios_grupo($post_id) {
        if (isset($_POST['usuarios_grupo'])) {
            update_post_meta($post_id, 'usuarios_grupo', array_map('intval', $_POST['usuarios_grupo']));
        } else {
            delete_post_meta($post_id, 'usuarios_grupo');
        }
    }
}
add_action('save_post', 'salvar_usuarios_grupo');

if (!function_exists('adicionar_metabox_lista_grupos')) {
    function adicionar_metabox_lista_grupos() {
        add_meta_box(
            'lista_grupos',
            __('Selecionar Grupos', 'tutor-lms-presenca'),
            'exibir_metabox_lista_grupos',
            'lista_presenca',
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'adicionar_metabox_lista_grupos');

if (!function_exists('exibir_metabox_lista_grupos')) {
    function exibir_metabox_lista_grupos($post) {
        $grupos = get_posts(array('post_type' => 'grupo_usuario', 'numberposts' => -1));
        $selecionados = get_post_meta($post->ID, 'grupos_lista', true) ?: [];
        echo '<ul>';
        foreach ($grupos as $grupo) {
            $checked = in_array($grupo->ID, $selecionados) ? 'checked' : '';
            echo '<li><input type="checkbox" name="grupos_lista[]" value="' . esc_attr($grupo->ID) . '" ' . $checked . '> ' . esc_html($grupo->post_title) . '</li>';
        }
        echo '</ul>';
    }
}

if (!function_exists('salvar_lista_grupos')) {
    function salvar_lista_grupos($post_id) {
        if (isset($_POST['grupos_lista'])) {
            update_post_meta($post_id, 'grupos_lista', array_map('intval', $_POST['grupos_lista']));
        } else {
            delete_post_meta($post_id, 'grupos_lista');
        }
    }
}
add_action('save_post', 'salvar_lista_grupos');

if (!function_exists('adicionar_metabox_presenca_manual')) {
    function adicionar_metabox_presenca_manual() {
        add_meta_box(
            'presenca_manual',
            __('Dar Presença Manual', 'tutor-lms-presenca'),
            'exibir_metabox_presenca_manual',
            'lista_presenca',
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'adicionar_metabox_presenca_manual');

if (!function_exists('exibir_metabox_presenca_manual')) {
    function exibir_metabox_presenca_manual($post) {
        $grupos_vinculados = get_post_meta($post->ID, 'grupos_lista', true) ?: [];
        $usuarios = [];
        
        foreach ($grupos_vinculados as $grupo_id) {
            $grupo_usuarios = get_post_meta($grupo_id, 'usuarios_grupo', true);
            if ($grupo_usuarios) {
                $usuarios = array_merge($usuarios, $grupo_usuarios);
            }
        }

        $usuarios = array_unique($usuarios);
        $presencas = get_post_meta($post->ID, 'presenca_manual', true) ?: [];

        echo '<ul>';
        foreach ($usuarios as $usuario_id) {
            $user_info = get_userdata($usuario_id);
            if ($user_info) {
                $checked = in_array($usuario_id, $presencas) ? 'checked' : '';
                echo '<li>';
                echo '<label>';
                echo '<input type="checkbox" name="presenca_manual[]" value="' . esc_attr($usuario_id) . '" ' . $checked . '> ';
                echo esc_html($user_info->display_name);
                echo '</label>';
                echo '</li>';
            }
        }
        echo '</ul>';
    }
}

if (!function_exists('salvar_presenca_manual')) {
    function salvar_presenca_manual($post_id) {
        if (isset($_POST['presenca_manual'])) {
            update_post_meta($post_id, 'presenca_manual', array_map('intval', $_POST['presenca_manual']));
        } else {
            delete_post_meta($post_id, 'presenca_manual');
        }
    }
}
add_action('save_post', 'salvar_presenca_manual');

if (!function_exists('checar_inicio_aula_lembrete')) {
    function checar_inicio_aula_lembrete() {
        if (!wp_next_scheduled('enviar_lembrete_aula')) {
            wp_schedule_event(time(), 'hourly', 'enviar_lembrete_aula');
        }
    }
}
add_action('init', 'checar_inicio_aula_lembrete');

if (!function_exists('executar_lembrete_aula')) {
    add_action('enviar_lembrete_aula', 'executar_lembrete_aula');
    function executar_lembrete_aula() {
        $agora = current_time('Y-m-d\TH:i');
        $listas = get_posts(array('post_type' => 'lista_presenca', 'numberposts' => -1, 'meta_key' => 'inicio_aula'));

        foreach ($listas as $lista) {
            $inicio = get_post_meta($lista->ID, 'inicio_aula', true);

            if ($agora >= $inicio && $agora < date('Y-m-d\TH:i', strtotime($inicio . '+10 minutes'))) {
                $grupos_vinculados = get_post_meta($lista->ID, 'grupos_lista', true) ?: [];
                
                foreach ($grupos_vinculados as $grupo_id) {
                    $grupo_usuarios = get_post_meta($grupo_id, 'usuarios_grupo', true);
                    if ($grupo_usuarios) {
                        foreach ($grupo_usuarios as $usuario_id) {
                            $whatsapp = get_user_meta($usuario_id, 'whatsapp', true);
                            $mensagem_reminder = get_option('evolution_msg_reminder', 'Lembre-se de marcar presença na sua próxima aula.');
                            enviar_mensagem_whatsapp($whatsapp, $mensagem_reminder);
                        }
                    }
                }
            }
        }
    }
}

if (!function_exists('exibir_pagina_relatorios_grupo')) {
    function exibir_pagina_relatorios_grupo() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Relatórios de Presença por Grupo', 'tutor-lms-presenca') . '</h1>';
        
        $grupos = get_posts(array('post_type' => 'grupo_usuario', 'numberposts' => -1));

        foreach ($grupos as $grupo) {
            $usuarios_grupo = get_post_meta($grupo->ID, 'usuarios_grupo', true) ?: [];
            
            echo '<h2>' . esc_html($grupo->post_title) . '</h2>';
            echo '<table class="widefat fixed" cellspacing="0">
            <thead><tr><th>' . __('Usuário', 'tutor-lms-presenca') . '</th><th>' . __('Aulas Participadas', 'tutor-lms-presenca') . '</th><th>' . __('Aulas Faltadas', 'tutor-lms-presenca') . '</th></tr></thead>
            <tbody>';

            foreach ($usuarios_grupo as $usuario_id) {
                $usuario = get_userdata($usuario_id);
                if ($usuario) {
                    $participou = 0;
                    $faltou = 0;
                    
                    $listas = get_posts(array('post_type' => 'lista_presenca', 'numberposts' => -1));
                    foreach ($listas as $lista) {
                        $grupos_vinculados = get_post_meta($lista->ID, 'grupos_lista', true) ?: [];
                        $presencas = get_post_meta($lista->ID, 'presenca_manual', true) ?: [];
                        
                        if (in_array($grupo->ID, $grupos_vinculados)) {
                            if (in_array($usuario_id, $presencas)) {
                                $participou++;
                            } else {
                                $faltou++;
                            }
                        }
                    }

                    echo '<tr><td>' . esc_html($usuario->display_name) . '</td><td>' . $participou . '</td><td>' . $faltou . '</td></tr>';
                }
            }

            echo '</tbody></table>';
            echo '<form method="post" action=""><input type="hidden" name="reset_group_id" value="' . esc_attr($grupo->ID) . '">';
            submit_button(__('Zerar Relatório do Grupo', 'tutor-lms-presenca'));
            echo '</form>';
        }

        if (isset($_POST['reset_group_id'])) {
            zerar_relatorio_grupo(intval($_POST['reset_group_id']));
        }

        echo '</div>';
    }
}

if (!function_exists('exibir_pagina_relatorios_lista')) {
    function exibir_pagina_relatorios_lista() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Relatórios de Presença por Lista', 'tutor-lms-presenca') . '</h1>';
        
        $listas = get_posts(array('post_type' => 'lista_presenca', 'numberposts' => -1));

        foreach ($listas as $lista) {
            $presencas = get_post_meta($lista->ID, 'presenca_manual', true) ?: [];
            $titulo = get_the_title($lista->ID);
            
            echo '<h2>' . esc_html($titulo) . '</h2>';
            echo '<table class="widefat fixed" cellspacing="0">
            <thead><tr><th>' . __('Usuário', 'tutor-lms-presenca') . '</th></tr></thead>
            <tbody>';  
            
            foreach ($presencas as $usuario_id) {
                $usuario = get_userdata($usuario_id);
                if ($usuario) {
                    echo '<tr><td>' . esc_html($usuario->display_name) . '</td></tr>';
                }
            }

            echo '</tbody></table>';
            echo '<form method="post">';
            echo '<input type="hidden" name="export_lista_id" value="' . esc_attr($lista->ID) . '">';
            submit_button(__('Exportar para CSV', 'tutor-lms-presenca'));
            echo '</form>';
        }

        if (isset($_POST['export_lista_id'])) {
            exportar_lista_para_csv(intval($_POST['export_lista_id']));
        }

        echo '</div>';
    }
}

if (!function_exists('exportar_lista_para_csv')) {
    function exportar_lista_para_csv($lista_id) {
        $presencas = get_post_meta($lista_id, 'presenca_manual', true) ?: [];
        $titulo = get_the_title($lista_id);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($titulo) . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('Usuário', 'Email'));

        foreach ($presencas as $usuario_id) {
            $usuario = get_userdata($usuario_id);
            if ($usuario) {
                fputcsv($output, array($usuario->display_name, $usuario->user_email));
            }
        }

        fclose($output);
        exit;
    }
}

if (!function_exists('zerar_relatorio_grupo')) {
    function zerar_relatorio_grupo($grupo_id) {
        $usuarios_grupo = get_post_meta($grupo_id, 'usuarios_grupo', true) ?: [];

        foreach ($usuarios_grupo as $usuario_id) {
            delete_user_meta($usuario_id, 'presencas_registradas');
        }
    }
}