@extends('layouts.app')

@section('content')
    <div class="container">
        <div id="chat">
            <div class="card">
                <div class="card-header">
                    <h4>Chat Application</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="receiver">Select Receiver:</label>
                        <select id="receiver" class="form-control">
                            <option value="">Select Receiver</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="messages" class="mt-4 p-3 border"
                        style="height: 300px; overflow-y: scroll; background-color: #f8f9fa;"></div>
                    <div id="input-area" class="input-group mt-3">
                        <input type="text" id="message" class="form-control" placeholder="Type your message">
                        <div class="input-group-append">
                            <button id="send" class="btn btn-primary">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        #messages .sender {
            text-align: left;
            background-color: #d4edda;
            padding: 8px;
            border-radius: 15px;
            margin-bottom: 10px;
            width: fit-content;
            max-width: 70%;
            margin-left: auto;
        }

        #messages .receiver {
            text-align: right;
            background-color: #cce5ff;
            padding: 8px;
            border-radius: 15px;
            margin-bottom: 10px;
            width: fit-content;
            max-width: 70%;
        }

        #messages {
            display: flex;
            flex-direction: column;
        }
    </style>

    <script>
        $(document).ready(function() {
            // Function to send the message
            function sendMessage() {
                const receiver_id = $('#receiver').val();
                const message = $('#message').val();

                if (receiver_id && message) {
                    $.post('/send-message', {
                        receiver_id: receiver_id,
                        message: message,
                        _token: '{{ csrf_token() }}'
                    }).done(function() {
                        $('#message').val('');
                        loadMessages();
                    }).fail(function() {
                        alert('Error sending message.');
                    });
                } else {
                    alert('Please select a receiver and enter a message.');
                }
            }

            // Click event for the send button
            $('#send').click(function() {
                sendMessage();
            });

            // Key press event for the message input field
            $('#message').keypress(function(event) {
                // Check if the Enter key is pressed
                if (event.which === 13) {
                    event.preventDefault(); // Prevent the default action of the Enter key
                    sendMessage();
                }
            });
        });

        $(document).ready(function() {
            // Initialize EventSource for real-time messages
            let eventSource;

            function loadMessages() {
                const receiver_id = $('#receiver').val();
                if (receiver_id) {
                    eventSource = new EventSource(`/messages/${receiver_id}`);
                    eventSource.onmessage = function(event) {
                        const messages = JSON.parse(event.data);
                        $('#messages').html('');
                        $.each(messages, function(index, message) {
                            const messageClass = (message.sender === '{{ auth()->user()->name }}') ?
                                'sender' : 'receiver';
                            $('#messages').append(
                                `<div class="message ${messageClass}"><strong>${message.sender}:</strong> ${message.message}</div>`
                            );
                        });

                        // Scroll to the bottom of the chat container
                        const messagesDiv = $('#messages');
                        messagesDiv.scrollTop(messagesDiv[0].scrollHeight);
                    };
                }
            }

            // Load messages when the receiver changes
            $('#receiver').change(function() {
                loadMessages();
            });
        });
    </script>
@endsection
