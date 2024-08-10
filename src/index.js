document.addEventListener('DOMContentLoaded', function() {
    var chatIcon = document.getElementById('wpml-chat-icon');
    var chatWindow = document.getElementById('wpml-chat-window');

    // Function to toggle the chat window
    function toggleChatWindow() {
        if (chatWindow.style.display === 'none' || !chatWindow.style.display) {
            chatWindow.style.display = 'block';
        } else {
            chatWindow.style.display = 'none';
        }
    }

    // Event listener for the chat icon click
    chatIcon.addEventListener('click', function() {
        toggleChatWindow();
    });

    var chatForm = document.getElementById('wpml-chat-form');
    var chatMessage = document.getElementById('chatgpt-message');
    var chatResponses = document.getElementById('chatgpt-responses');

    chatForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        var userMessage = chatMessage.value.trim();

        if (userMessage) {
            // Display user's message
            var userMessageElement = document.createElement('div');
            userMessageElement.textContent = 'You: ' + userMessage;
            chatResponses.appendChild(userMessageElement);

            // On success, display bot's response
            jQuery.ajax({
                url: wpmlChat.ajax_url, // This variable should be created using wp_localize_script
                type: 'POST',
                data: {
                    'action': 'handle_openai_request',
                    'message': userMessage
                },
                success: function(response) {
                    if (response.success) {
                        var botResponse = response.data.choices[0].message.content;
                        var botResponseElement = document.createElement('div');
                        botResponseElement.textContent = 'Bot: ' + botResponse;
                        chatResponses.appendChild(botResponseElement);
                    } else {
                        console.error('Error:', response.data);
                    }
                },
                error: function(error) {
                    console.error('Error sending message:', error);
                }
            });
        }
        // Clear the input field after sending
        chatMessage.value = '';
    });
});
