<?php

class Tutor_LMS_QRCode_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'tutor_lms_qrcode';
    }

    public function get_title() {
        return __('QR Code Dinâmico', 'tutor-lms-presenca');
    }

    public function get_icon() {
        return 'fas fa-qrcode';
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

        $this->add_responsive_control(
            'qr_size',
            [
                'label' => __('Tamanho do QR Code', 'tutor-lms-presenca'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 500,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .tutor-lms-qrcode' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
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
            'qr_color',
            [
                'label' => __('Cor do QR Code', 'tutor-lms-presenca'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .tutor-lms-qrcode' => 'filter: brightness(0) saturate(100%) invert(0%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(0) contrast(1000%);'
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
            $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($url_checkin);

            echo '<img src="' . esc_url($qr_code_url) . '" alt="' . esc_attr__('QR Code para Check-in', 'tutor-lms-presenca') . '" class="tutor-lms-qrcode" />';
        }
    }
}