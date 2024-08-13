@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <!-- Sidebar for Users -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h4>Users</h4>
                    </div>
                    <div id="user-list" class="card-body" style="height: 500px; overflow-y: auto;">
                        <ul class="list-group">
                            @foreach ($users as $user)
                                <li class="list-group-item user-item" data-id="{{ $user->id }}">
                                    {{ $user->name }}&nbsp;
                                    <span class="status-dot"
                                        style="float: right; width: 10px; height: 10px; border-radius: 50%; background-color: gray;">
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="col-md-9">
                <div id="chat" class="card">
                    <div class="card-header">
                        <h4 id="chat-header">Select a user to start chatting</h4>
                    </div>
                    <div class="card-body">
                        <div id="messages" class="mt-4 p-3 border"
                            style="height: 400px; overflow-y: scroll; background-color: #f8f9fa;"></div>
                        <div id="input-area" class="input-group mt-3">
                            <input type="text" id="message" class="form-control" placeholder="Type your message"
                                disabled>
                            <div class="input-group-append">
                                <button id="send" class="btn btn-primary" disabled>Send</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Sidebar styles */
        #user-list .user-item {
            cursor: pointer;
        }

        #user-list .user-item.active {
            background-color: #000;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: gray;
        }

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
            let eventSourceMessages;
            let eventSourceUsers;

            function loadMessages(receiverId) {
                if (!receiverId) return;

                if (eventSourceMessages) {
                    eventSourceMessages.close();
                }

                eventSourceMessages = new EventSource(`/messages/${receiverId}`);
                eventSourceMessages.onmessage = function(event) {
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

                eventSourceMessages.onerror = function(event) {
                    console.error("Error with messages EventSource:", event);
                };
            }

            function loadUsersSSE() {
                if (eventSourceUsers) {
                    eventSourceUsers.close();
                }

                eventSourceUsers = new EventSource('/stream-active-users');

                eventSourceUsers.onmessage = function(event) {
                    const users = JSON.parse(event.data);

                    users.forEach(user => {
                        // Find the list item for the current user
                        const userItem = $(`#user-list .user-item[data-id="${user.id}"]`);

                        // Determine if the user is active
                        const isActive = user.last_seen ? 'green' : 'gray';

                        // Update the status dot color if the user item exists
                        if (userItem.length > 0) {
                            userItem.find('.status-dot').css('background-color', isActive);
                        } else {
                            // If the user item does not exist, add it to the list
                            $('#user-list ul').append(`
                    <li class="list-group-item user-item" data-id="${user.id}" style="display: flex; align-items: center;">
                        <span class="status-dot" style="background-color: ${isActive};"></span> &nbsp;
                        <span>${user.name}</span>
                    </li>
                `);
                        }
                    });
                };

                eventSourceUsers.onerror = function(event) {
                    // Handle errors if necessary
                    console.error("Error with users EventSource:", event);
                };
            }


            $('#user-list').on('click', '.user-item', function() {
                const receiverId = $(this).data('id');
                $('.user-item').removeClass('active');
                $(this).addClass('active');
                $('#chat-header').text('Chat with ' + $(this).text());
                $('#message').prop('disabled', false);
                $('#send').prop('disabled', false);

                loadMessages(receiverId);
            });

            $('#send').click(function() {
                const receiverId = $('.user-item.active').data('id');
                const message = $('#message').val();

                if (receiverId && message) {
                    $.post('/send-message', {
                        receiver_id: receiverId,
                        message: message,
                        _token: '{{ csrf_token() }}'
                    }).done(function() {
                        $('#message').val('');
                        loadMessages(receiverId);
                    });
                } else {
                    alert('Please select a receiver and enter a message.');
                }
            });

            $('#message').keypress(function(event) {
                if (event.which == 13) {
                    $('#send').click();
                }
            });

            // Initialize SSE connections
            loadUsersSSE();
        });
    </script>
@endsection
