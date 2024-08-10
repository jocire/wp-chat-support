<?php
/*
Plugin Name: WPML Chat Support
Description: Support chat for WPML plugin users.
Version: 1.2
Author: Andreas Walter
*/

// Enqueue scripts and styles
function chatgpt_assistant_enqueue_scripts() {
    wp_enqueue_style('chatgpt-assistant-css', plugins_url('/css/wpml-chat.css', __FILE__));
    wp_enqueue_script('chatgpt-js', plugins_url('/js/wpml-chat.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('chatgpt-js', 'wpmlChat', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'api_type' => get_option('wpml_chat_api_type'),
        'assistant_id' => get_option('wpml_chat_assistant_id')
    ));
}
add_action('wp_enqueue_scripts', 'chatgpt_assistant_enqueue_scripts');

// Create menu item in admin area
function wpml_chat_support_menu() {
    add_menu_page('WPML Chat Support', 'WPML Chat Support', 'manage_options', 'wpml-chat-support', 'wpml_chat_support_options_page');
}
add_action('admin_menu', 'wpml_chat_support_menu');

// Options page content
function wpml_chat_support_options_page() {
    ?>
    <div class="wrap">
        <h1>WPML Chat Support Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wpml-chat-support-options');
            do_settings_sections('wpml-chat-support');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function wpml_chat_support_register_settings() {
    register_setting('wpml-chat-support-options', 'chatgpt_api_key', array(
        'sanitize_callback' => 'wpml_chat_support_sanitize_api_key'
    ));
    register_setting('wpml-chat-support-options', 'wpml_chat_icon_color');
    register_setting('wpml-chat-support-options', 'wpml_chat_button_color');
    register_setting('wpml-chat-support-options', 'wpml_chat_api_type');
    register_setting('wpml-chat-support-options', 'wpml_chat_assistant_id');
    add_settings_section('wpml-chat-support-main', 'Main Settings', null, 'wpml-chat-support');
    add_settings_field('chatgpt-api-key', 'ChatGPT API Key', 'wpml_chat_support_api_key_field', 'wpml-chat-support', 'wpml-chat-support-main');
    add_settings_field('wpml-chat-icon-color', 'Chat Icon Color', 'wpml_chat_icon_color_field', 'wpml-chat-support', 'wpml-chat-support-main');
    add_settings_field('wpml-chat-button-color', 'Chat Button Color', 'wpml_chat_button_color_field', 'wpml-chat-support', 'wpml-chat-support-main');
    add_settings_field('wpml-chat-api-type', 'API Type', 'wpml_chat_api_type_field', 'wpml-chat-support', 'wpml-chat-support-main');
    add_settings_field('wpml-chat-assistant-id', 'Assistant ID', 'wpml_chat_assistant_id_field', 'wpml-chat-support', 'wpml-chat-support-main');
}
add_action('admin_init', 'wpml_chat_support_register_settings');

function wpml_chat_support_sanitize_api_key($api_key) {
    return base64_encode(sanitize_text_field($api_key));
}

function wpml_chat_support_api_key_field() {
    $encoded_api_key = get_option('chatgpt_api_key');
    $api_key = base64_decode($encoded_api_key);
    echo '<input type="password" id="chatgpt_api_key" name="chatgpt_api_key" value="' . esc_attr($api_key) . '" />';
    echo '<style>
            #chatgpt_api_key {
                -webkit-text-security: disc;
            }
          </style>';
    echo '<script>
            document.getElementById("chatgpt_api_key").addEventListener("copy", function(e) {
                e.preventDefault();
            });
          </script>';
}

function wpml_chat_icon_color_field() {
    $wpml_chat_icon_color = get_option('wpml_chat_icon_color');   
    echo '<input type="color" id="wpml_chat_icon_color" name="wpml_chat_icon_color" value="' . esc_attr($wpml_chat_icon_color) . '" />';    
}

function wpml_chat_button_color_field() {
    $wpml_chat_button_color = get_option('wpml_chat_button_color');   
    echo '<input type="color" id="wpml_chat_button_color" name="wpml_chat_button_color" value="' . esc_attr($wpml_chat_button_color) . '" />';    
}

function wpml_chat_api_type_field() {
    $wpml_chat_api_type = get_option('wpml_chat_api_type');
    $api_types = array('Conversational' => 'Conversational', 'Assistants' => 'Assistants');
    echo '<select id="wpml_chat_api_type" name="wpml_chat_api_type">';
    foreach ($api_types as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($wpml_chat_api_type, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

function wpml_chat_assistant_id_field() {
    $wpml_chat_assistant_id = get_option('wpml_chat_assistant_id');
    echo '<input type="text" id="wpml_chat_assistant_id" name="wpml_chat_assistant_id" value="' . esc_attr($wpml_chat_assistant_id) . '" />';
}

// Handle AJAX request
add_action('wp_ajax_nopriv_handle_openai_request', 'handle_openai_request');
add_action('wp_ajax_handle_openai_request', 'handle_openai_request');

function handle_openai_request() {
    $message = sanitize_text_field($_POST['message']);
    $api_key = base64_decode(get_option('chatgpt_api_key'));
    $api_type = get_option('wpml_chat_api_type');
    $assistant_id = get_option('wpml_chat_assistant_id');

    if (!$api_key) {
        wp_send_json_error('API key is not set.');
        wp_die();
    }

    if ($api_type === 'Conversational') {
        $api_url = 'https://api.openai.com/v1/chat/completions';
        $post_fields = json_encode(array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array('role' => 'user', 'content' => $message)
            ),
            'max_tokens' => 150,
        ));
    } elseif ($api_type === 'Assistants' && $assistant_id) {       
        //$api_url = 'https://api.openai.com/v1/assistants/' . $assistant_id . '/messages';
        $api_url = 'https://api.openai.com/v1/threads/thread_qQ3lvOuDwBkBg9e4E8JV1BR7/messages';
        $post_fields = json_encode(array(
            'messages' => array(
                array('role' => 'user', 'content' => $message)
            )
        ));
    } else {
        wp_send_json_error('Invalid API type or missing Assistant ID.');
        wp_die();
    }

    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    );

    if ($api_type === 'Assistants') {
        $headers[] = 'OpenAI-Beta: assistants=v2';
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        wp_send_json_error('cURL Error: ' . $err);
    } else {
        $decoded_response = json_decode($response, true);
        wp_send_json_success($decoded_response);
    }

    wp_die();
}



// Output chat icon HTML in footer
function chatgpt_assistant_chat_icon() {    
    ?>
    <div id="wpml-chat-icon">
        <svg xmlns="http://www.w3.org/2000/svg" height="20" width="20" viewBox="0 0 512 512">
            <!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
            <path fill="<?php echo get_option('wpml_chat_icon_color');?>" d="M144 208c-17.7 0-32 14.3-32 32s14.3 32 32 32 32-14.3 32-32-14.3-32-32-32zm112 0c-17.7 0-32 14.3-32 32s14.3 32 32 32 32-14.3 32-32-14.3-32-32-32zm112 0c-17.7 0-32 14.3-32 32s14.3 32 32 32 32-14.3 32-32-14.3-32-32-32zM256 32C114.6 32 0 125.1 0 240c0 47.6 19.9 91.2 52.9 126.3C38 405.7 7 439.1 6.5 439.5c-6.6 7-8.4 17.2-4.6 26S14.4 480 24 480c61.5 0 110-25.7 139.1-46.3C192 442.8 223.2 448 256 448c141.4 0 256-93.1 256-208S397.4 32 256 32zm0 368c-26.7 0-53.1-4.1-78.4-12.1l-22.7-7.2-19.5 13.8c-14.3 10.1-33.9 21.4-57.5 29 7.3-12.1 14.4-25.7 19.9-40.2l10.6-28.1-20.6-21.8C69.7 314.1 48 282.2 48 240c0-88.2 93.3-160 208-160s208 71.8 208 160-93.3 160-208 160z"/>
        </svg>        
    </div>       
    <div id="wpml-chat-window" style="display:none;">             
        <form id="wpml-chat-form">        
            <main id="chatgpt-responses">
                <footer class="message-footer">
                    <span>                                                                       
                        <textarea id="chatgpt-message" placeholder="Type your message here..." rows="2"></textarea>
                        <button id="chatgpt-submit" type="submit" style="background-color:<?php echo get_option('wpml_chat_button_color');?>">Send</button>
                        <?php $api_type = get_option('wpml_chat_api_type'); ?>
                        <div class="api-type"><?php echo $api_type === 'Conversational' ? 'Conversational API' : 'Assistants API'; ?></div>
                    </span>
                </footer>           
            </main>
        </form>       
    </div>    
    <?php 
}
add_action('wp_footer', 'chatgpt_assistant_chat_icon');
?>
