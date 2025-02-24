<?php

if (!defined('ABSPATH')) {
    exit; // Sair se acessado diretamente
}

class Tutor_LMS_Checkin_Button_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'tutor_lms_checkin_button';
    }

    public function get_title() {
        return __('Botão de Check-In Dinâmico', 'tutor-lms-presenca');
    }

    public function get_icon() {
        return 'fas fa-sign-in-alt';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Conteúdo', 'tutor-lms-presenca'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __('Texto do Botão', 'tutor-lms-presenca'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Fazer Check-in', 'tutor-lms-presenca'),
                'placeholder' => __('Digite o texto do botão', 'tutor-lms-presenca'),
            ]
        );

        $this->add_control(
            'icon',
            [
                'label' => __('Ícone', 'tutor-lms-presenca'),
                'type' => \Elementor\Controls_Manager::ICON,
                'default' => '',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Estilo', 'tutor-lms-presenca'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'button_color',
            [
                'label' => __('Cor do Botão', 'tutor-lms-presenca'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .tutor-lms-checkin-button' => 'color: {{VALUE}}; background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'label' => __('Tipografia', 'tutor-lms-presenca'),
                'selector' => '{{WRAPPER}} .tutor-lms-checkin-button',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'border',
                'label' => __('Borda', 'tutor-lms-presenca'),
                'selector' => '{{WRAPPER}} .tutor-lms-checkin-button',
            ]
        );

        $this->add_responsive_control(
            'padding',
            [
                'label' => __('Padding', 'tutor-lms-presenca'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .tutor-lms-checkin-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'align',
            [
                'label' => __('Alinhamento', 'tutor-lms-presenca'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Esquerda', 'tutor-lms-presenca'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Centro', 'tutor-lms-presenca'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Direita', 'tutor-lms-presenca'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .tutor-lms-checkin-button-wrapper' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        if (is_singular('lista_presenca')) {
            global $post;
            $aula_id = $post->ID;
            $url_checkin = esc_url(add_query_arg('checkin', $aula_id, home_url()));
            $settings = $this->get_settings_for_display();

            echo '<div class="tutor-lms-checkin-button-wrapper">';
            echo '<a href="' . $url_checkin . '" class="tutor-lms-checkin-button">';
            if ($settings['icon']) {
                echo '<i class="' . esc_attr($settings['icon']) . '"></i> ';
            }
            echo esc_html($settings['button_text']);
            echo '</a>';
            echo '</div>';
        } else {
            echo '<div>' . __('Este widget só pode ser usado em tipos de post "Lista de Presença".', 'tutor-lms-presenca') . '</div>';
        }
    }
}
Função de Registro:

Certifique-se de ter uma função para registrar o widget no Elementor:

// Função para registrar o widget
function register_tutor_lms_widgets() {
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Tutor_LMS_Checkin_Button_Widget());
}
add_action('elementor/widgets/register', 'register_tutor_lms_widgets');