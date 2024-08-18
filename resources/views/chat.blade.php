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
                                <li class="list-group-item user-item" last-seen="" data-id="{{ $user->id }}">
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
                        <small class="last_seen_user"></small>
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

        .message {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
            max-width: 30%;
            position: relative;
        }

        .sender {
            background-color: #b9e296;
            text-align: right;
            margin-left: auto;
        }

        .sender::before {
            content: '';
            position: absolute;
            top: 0px;
            right: -10px;
            border-width: 10px;
            border-style: solid;
            border-color: transparent transparent #b9e296 transparent;
        }

        .receiver {
            background-color: #c7d9ff;
            text-align: left;
        }

        small.sender {
            display: flex;
            justify-content: flex-end;
        }


        small.receiver {
            display: flex;
            justify-content: flex-start;
        }

        .receiver::before {
            content: '';
            position: absolute;
            top: 0;
            left: -10px;
            border-width: 11px;
            border-style: solid;
            border-color: transparent transparent #c7d9ff transparent;
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
                            `<div class="message ${messageClass}"><strong>${message.sender}:</strong> ${message.message} <br> <small class="${messageClass}">${message.created_at.split(' ')[1]}</small></div>`
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
                    const currentTime = new Date();

                    users.forEach(user => {
                        // Find the list item for the current user
                        const userItem = $(`#user-list .user-item[data-id="${user.id}"]`);

                        // Convert the last_seen timestamp to a Date object
                        const lastSeenTime = new Date(user.last_seen);

                        // Calculate the time difference in milliseconds
                        const timeDifference = currentTime - lastSeenTime;

                        // Determine if the user is active (within the last minute)
                        const isActive = timeDifference <= 60000 ? 'green' : 'gray';

                        // Update the status dot color if the user item exists
                        if (userItem.length > 0) {
                            userItem.find('.status-dot').css('background-color', isActive);
                            userItem.attr("last-seen", user.last_seen);
                        } else {
                            // If the user item does not exist, add it to the list
                            $('#user-list ul').append(`
                    <li class="list-group-item user-item" data-id="${user.id}" data-lastseen="${user.last_seen}" style="display: flex; align-items: center;">
                        <span class="status-dot" style="background-color: ${isActive};"></span> &nbsp;
                        <span>${user.name}</span>
                    </li>
                `);
                        }
                    });
                };

                eventSourceUsers.onerror = function(event) {
                    // Handle errors if necessary
                    // console.error("Error with users EventSource:", event);
                };
            }

            $('#user-list').on('click', '.user-item', function() {
                const receiverId = $(this).data('id');
                $('.user-item').removeClass('active');
                $(this).addClass('active');
                $('#chat-header').text('Chat with ' + $(this).text());

                var currentDate = new Date();
                var currentDay = currentDate.getDate();
                var currentMonth = currentDate.getMonth();
                var currentYear = currentDate.getFullYear();

                var lastSeen = $(this).attr("last-seen") ? new Date($(this).attr("last-seen")) : null;

                var lastSeenText = '';

                if (lastSeen) {
                    var lastSeenDay = lastSeen.getDate();
                    var lastSeenMonth = lastSeen.getMonth();
                    var lastSeenYear = lastSeen.getFullYear();

                    if (lastSeenDay === currentDay && lastSeenMonth === currentMonth && lastSeenYear ===
                        currentYear) {
                        // Last seen is today, show only time
                        lastSeenText = lastSeen.toLocaleTimeString();
                    } else {
                        // Last seen is not today, show full date and time
                        lastSeenText = lastSeen.toLocaleDateString() + ' ' + lastSeen.toLocaleTimeString();
                    }
                }

                $('.last_seen_user').text('Last Seen: ' + lastSeenText);
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
