document.addEventListener('DOMContentLoaded', function() {
    var chatIcon = document.getElementById('wpml-chat-icon');
    var chatWindow = document.getElementById('wpml-chat-window');

    // Function to toggle the chat window
    function toggleChatWindow() {
        chatWindow.style.display = (chatWindow.style.display === 'none' || !chatWindow.style.display) ? 'block' : 'none';
    }

    // Event listener for the chat icon click
    chatIcon.addEventListener('click', toggleChatWindow);

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
            chatResponses.scrollTop = chatResponses.scrollHeight; // Scroll to the bottom

            // AJAX request to handle the message
            jQuery.ajax({
                url: wpmlChat.ajax_url, // This variable should be created using wp_localize_script
                type: 'POST',
                data: {
                    'action': 'handle_openai_request',
                    'message': userMessage
                },
                success: function(response) {
                    if (response.success) {
                        try {
                            var botResponse = getBotResponse(response.data);
                            var botName = wpmlChat.api_type === 'Assistants' ? 'WPML AI Agent' : 'ChatGPT';
                            var botResponseElement = document.createElement('div');
                            botResponseElement.textContent = botName + ': ' + botResponse;
                            chatResponses.appendChild(botResponseElement);
                            chatResponses.scrollTop = chatResponses.scrollHeight; // Scroll to the bottom
                        } catch (error) {
                            handleError('Error processing response. Check console for details.', error, response);
                        }
                    } else {
                        handleError('Error: ' + response.data.message, null, response);
                    }
                },
                error: function(error) {
                    handleError('Error sending message. Check console for details.', error);
                }
            });
        }
        // Clear the input field after sending
        chatMessage.value = '';
    });

    function getBotResponse(data) {
        var botResponse = '';
        if (wpmlChat.api_type === 'Conversational' && data.choices) {
            botResponse = data.choices[0].message.content;
        } else if (wpmlChat.api_type === 'Assistants' && data.messages) {
            botResponse = data.messages[0].content;
        } else {
            throw new Error('Invalid response structure');
        }
        return botResponse;
    }

    function handleError(message, error = null, response = null) {
        console.error(message, error, response);
        var errorMessageElement = document.createElement('div');
        errorMessageElement.textContent = message;
        chatResponses.appendChild(errorMessageElement);
        chatResponses.scrollTop = chatResponses.scrollHeight; // Scroll to the bottom
    }
});



