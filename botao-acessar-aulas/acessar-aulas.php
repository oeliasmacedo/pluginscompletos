<?php
/*
Plugin Name: Meu Widget Elementor
Description: Um widget personalizado para o Elementor.
Version: 1.0
Author: Seu Nome
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function register_botao_acesso_widget($widgets_manager) {
    if (class_exists('\Elementor\Widget_Base')) {
        class Botao_Acesso_Widget extends \Elementor\Widget_Base {

            public function get_name() {
                return 'botao_acesso_widget';
            }

            public function get_title() {
                return __('Botão de Acesso ao Curso', 'text-domain');
            }

            public function get_icon() {
                return 'eicon-button';
            }

            public function get_categories() {
                return ['general'];
            }

            protected function _register_controls() {
                // Seção de Conteúdo
                $this->start_controls_section(
                    'content_section',
                    [
                        'label' => __('Configurações do Botão', 'text-domain'),
                        'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                    ]
                );

                $this->add_control(
                    'button_text_enrolled',
                    [
                        'label' => __('Texto do botão (Inscrito)', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::TEXT,
                        'default' => __('Acessar Aulas', 'text-domain'),
                    ]
                );

                $this->add_control(
                    'button_text_not_enrolled',
                    [
                        'label' => __('Texto do botão (Não Inscrito)', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::TEXT,
                        'default' => __('Saiba Mais', 'text-domain'),
                    ]
                );

                $this->add_control(
                    'button_icon',
                    [
                        'label' => __('Ícone do Botão (Inscrito)', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::ICONS,
                        'label_block' => true,
                    ]
                );

                $this->add_control(
                    'button_icon_not_enrolled',
                    [
                        'label' => __('Ícone do Botão (Não Inscrito)', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::ICONS,
                        'label_block' => true,
                    ]
                );

                $this->add_control(
                    'icon_position',
                    [
                        'label' => __('Posição do Ícone', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'options' => [
                            'before' => __('Antes do Texto', 'text-domain'),
                            'after' => __('Depois do Texto', 'text-domain'),
                        ],
                        'default' => 'before',
                    ]
                );

                $this->end_controls_section();

                // Seção de Estilo do Botão
                $this->start_controls_section(
                    'style_section',
                    [
                        'label' => __('Estilo do Botão', 'text-domain'),
                        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                    ]
                );

                // Cor e Gradiente de Fundo
                $this->add_group_control(
                    \Elementor\Group_Control_Background::get_type(),
                    [
                        'name' => 'background_gradient',
                        'label' => __('Gradiente de Fundo', 'text-domain'),
                        'types' => ['classic', 'gradient'],
                        'selector' => '{{WRAPPER}} a button',
                    ]
                );

                $this->add_group_control(
                    \Elementor\Group_Control_Background::get_type(),
                    [
                        'name' => 'background_hover_gradient',
                        'label' => __('Gradiente de Fundo no Hover', 'text-domain'),
                        'types' => ['gradient'],
                        'selector' => '{{WRAPPER}} a button:hover',
                    ]
                );

                // Cor do Texto
                $this->add_control(
                    'button_text_color',
                    [
                        'label' => __('Cor do Texto', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} a button' => 'color: {{VALUE}};',
                        ],
                    ]
                );

                $this->add_control(
                    'button_hover_text_color',
                    [
                        'label' => __('Cor do Texto no Hover', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} a button:hover' => 'color: {{VALUE}};',
                        ],
                    ]
                );

                // Controle de Tipografia
                $this->add_group_control(
                    \Elementor\Group_Control_Typography::get_type(),
                    [
                        'name' => 'typography',
                        'label' => __('Tipografia', 'text-domain'),
                        'selector' => '{{WRAPPER}} a button',
                    ]
                );

                // Borda
                $this->add_group_control(
                    \Elementor\Group_Control_Border::get_type(),
                    [
                        'name' => 'border',
                        'label' => __('Borda', 'text-domain'),
                        'selector' => '{{WRAPPER}} a button',
                    ]
                );

                $this->add_group_control(
                    \Elementor\Group_Control_Border::get_type(),
                    [
                        'name' => 'border_hover',
                        'label' => __('Borda no Hover', 'text-domain'),
                        'selector' => '{{WRAPPER}} a button:hover',
                    ]
                );

                // Raio da Borda Responsivo
                $this->add_responsive_control(
                    'button_border_radius',
                    [
                        'label' => __('Raio da Borda', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%'],
                        'selectors' => [
                            '{{WRAPPER}} a button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                    ]
                );

                // Controle de Padding e Margin
                $this->add_responsive_control(
                    'button_padding',
                    [
                        'label' => __('Espaçamento Interno (Padding)', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%', 'em', 'vw'],
                        'selectors' => [
                            '{{WRAPPER}} a button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                    ]
                );

                $this->add_responsive_control(
                    'button_margin',
                    [
                        'label' => __('Margem', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%', 'em', 'vw'],
                        'selectors' => [
                            '{{WRAPPER}} a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                    ]
                );

                // Efeito e Transição no Hover
                $this->add_control(
                    'hover_transition',
                    [
                        'label' => __('Efeito de Transição no Hover', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SLIDER,
                        'range' => [
                            'px' => ['max' => 3, 'step' => 0.1],
                        ],
                        'selectors' => [
                            '{{WRAPPER}} a button' => 'transition: all {{SIZE}}s;',
                        ],
                    ]
                );

                $this->add_control(
                    'hover_animation',
                    [
                        'label' => __('Efeito de Movimento no Hover', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::HOVER_ANIMATION,
                    ]
                );

                // Filtros de Container Condicional
                $this->add_control(
                    'not_enrolled_filter',
                    [
                        'label' => __('Filtro para Não Inscritos', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'options' => [
                            'none' => __('Sem Filtro', 'text-domain'),
                            'grayscale' => __('Preto e Branco', 'text-domain'),
                            'blur' => __('Desfoque', 'text-domain'),
                            'sepia' => __('Sépia', 'text-domain'),
                        ],
                        'default' => 'none',
                    ]
                );

                // Controle do Ícone
                $this->add_control(
                    'icon_size',
                    [
                        'label' => __('Tamanho do Ícone', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SLIDER,
                        'range' => [
                            'px' => ['min' => 10, 'max' => 50],
                        ],
                        'selectors' => [
                            '{{WRAPPER}} .elementor-button-icon i, {{WRAPPER}} .elementor-button-icon svg' => 'font-size: {{SIZE}}px; width: {{SIZE}}px; height: {{SIZE}}px;',
                        ],
                    ]
                );

                $this->add_control(
                    'icon_color',
                    [
                        'label' => __('Cor do Ícone', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .elementor-button-icon i, {{WRAPPER}} .elementor-button-icon svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
                        ],
                    ]
                );

                $this->add_control(
                    'icon_hover_color',
                    [
                        'label' => __('Cor do Ícone no Hover', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} a button:hover .elementor-button-icon i, {{WRAPPER}} a button:hover .elementor-button-icon svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
                        ],
                    ]
                );

                $this->add_control(
                    'icon_vertical_align',
                    [
                        'label' => __('Alinhamento Vertical do Ícone', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'options' => [
                            'flex-start' => __('Topo', 'text-domain'),
                            'center' => __('Centro', 'text-domain'),
                            'flex-end' => __('Base', 'text-domain'),
                        ],
                        'default' => 'center',
                        'selectors' => [
                            '{{WRAPPER}} .elementor-button-icon' => 'align-self: {{VALUE}};',
                        ],
                    ]
                );

                $this->add_control(
                    'icon_spacing',
                    [
                        'label' => __('Espaçamento do Ícone', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SLIDER,
                        'range' => [
                            'px' => ['min' => 0, 'max' => 30],
                        ],
                        'selectors' => [
                            '{{WRAPPER}} .elementor-button-icon-before' => 'margin-right: {{SIZE}}px;',
                            '{{WRAPPER}} .elementor-button-icon-after' => 'margin-left: {{SIZE}}px;',
                        ],
                    ]
                );

                $this->end_controls_section();

                // Seção de Layout e Posição do Botão
                $this->start_controls_section(
                    'position_section',
                    [
                        'label' => __('Posição do Botão', 'text-domain'),
                        'tab' => \Elementor\Controls_Manager::TAB_LAYOUT,
                    ]
                );

                $this->add_responsive_control(
                    'button_alignment',
                    [
                        'label' => __('Alinhamento', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::CHOOSE,
                        'options' => [
                            'left' => [
                                'title' => __('Esquerda', 'text-domain'),
                                'icon' => 'eicon-text-align-left',
                            ],
                            'center' => [
                                'title' => __('Centro', 'text-domain'),
                                'icon' => 'eicon-text-align-center',
                            ],
                            'right' => [
                                'title' => __('Direita', 'text-domain'),
                                'icon' => 'eicon-text-align-right',
                            ],
                            'justify' => [
                                'title' => __('Esticar', 'text-domain'),
                                'icon' => 'eicon-text-align-justify',
                            ],
                        ],
                        'selectors' => [
                            '{{WRAPPER}} a' => 'display: flex; justify-content: {{VALUE}};',
                            '{{WRAPPER}} a button' => 'width: {{VALUE}} !important; width: 100% !important; flex: 1; text-align: center;',
                        ],
                        'default' => 'center',
                        'toggle' => false,
                    ]
                );

                $this->end_controls_section();
            }

            protected function render() {
                global $post;

                if (!isset($post->ID)) {
                    return;
                }

                $post_id = $post->ID;
                $user_id = get_current_user_id();
                $tutor_utils_exist = function_exists('tutor_utils');
                $is_enrolled = $tutor_utils_exist && tutor_utils()->is_enrolled($post_id, $user_id);

                $lesson_url = $tutor_utils_exist ? tutor_utils()->get_course_first_lesson() : null;
                $course_url = get_permalink($post_id);

                $button_text = $is_enrolled 
                    ? $this->get_settings_for_display('button_text_enrolled') 
                    : $this->get_settings_for_display('button_text_not_enrolled');

                $button_icon = $is_enrolled 
                    ? $this->get_settings_for_display('button_icon') 
                    : $this->get_settings_for_display('button_icon_not_enrolled');

                $url = $is_enrolled && $lesson_url ? $lesson_url : $course_url;

                $filter_class = '';
                if (!$is_enrolled) {
                    $not_enrolled_filter = $this->get_settings_for_display('not_enrolled_filter');
                    if ($not_enrolled_filter !== 'none') {
                        $filter_class = 'not-enrolled-filter-' . $not_enrolled_filter;
                    }
                }

                $this->add_render_attribute('button', 'class', 'elementor-button');

                if ($hover_animation = $this->get_settings_for_display('hover_animation')) {
                    $this->add_render_attribute('button', 'class', 'elementor-animation-' . $hover_animation);
                }

                $icon_position = $this->get_settings_for_display('icon_position');

                echo '<div class="course-widget ' . esc_attr($filter_class) . '">'; // Adiciona a classe de filtro
                echo '<a href="' . esc_url($url) . '" style="text-decoration: none; width: 100%;">';
                echo '<button ' . $this->get_render_attribute_string('button') . ' style="display: flex; align-items: center; justify-content: center; width: 100%;">';

                if ($icon_position == 'before' && !empty($button_icon['value'])) {
                    echo '<span class="elementor-button-icon elementor-align-icon elementor-button-icon-before">';
                    \Elementor\Icons_Manager::render_icon($button_icon, ['aria-hidden' => 'true']);
                    echo '</span>';
                }

                echo '<span>' . esc_html($button_text) . '</span>';

                if ($icon_position == 'after' && !empty($button_icon['value'])) {
                    echo '<span class="elementor-button-icon elementor-align-icon elementor-button-icon-after">';
                    \Elementor\Icons_Manager::render_icon($button_icon, ['aria-hidden' => 'true']);
                    echo '</span>';
                }

                echo '</button></a>';
                echo '</div>'; // Fecha o contêiner principal
            }
        }

        $widgets_manager->register(new \Botao_Acesso_Widget());
    }
}

add_action('elementor/widgets/widgets_registered', 'register_botao_acesso_widget');

// Enqueue CSS para Filtros
function enqueue_botao_acesso_css() {
    ?>
    <style>
        .not-enrolled-filter-grayscale {
            filter: grayscale(100%);
        }
        .not-enrolled-filter-blur {
            filter: blur(5px);
        }
        .not-enrolled-filter-sepia {
            filter: sepia(100%);
        }
    </style>
    <?php
}
add_action('wp_head', 'enqueue_botao_acesso_css');