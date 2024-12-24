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
                        <input type="text" id="search-user" class="form-control mb-3" placeholder="Search users...">
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
                            <label for="attachment" style="padding: 5px 10px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor"
                                    class="bi bi-plus-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                    <path
                                        d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
                                </svg>
                            </label>
                            <input type="file" class="d-none" name="attachment" id="attachment">
                            <input type="text" id="message" class="form-control" placeholder="Type your message"
                                disabled>&nbsp;
                            <div class="input-group-append">
                                <button id="send" class="btn btn-primary" disabled>Send</button>
                            </div>
                        </div>

                        <!-- Preview Container for Attached File -->
                        <div id="attachment-preview" class="mt-2" style="display: none;">
                            <span id="preview-close">&times;</span>
                            <img id="preview-image" src="" alt="Attachment Preview"
                                style="width: 70px; display: none;">
                            <span id="preview-filename"></span>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        #preview-close {
            cursor: pointer;
            color: red;
            position: absolute;
            top: 540px;
            left: 80px;
            font-size: 22px;
            border-radius: 4px;
            font-weight: bold;
        }

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
            font-weight: bold;
            background-color: #b9e296;
            text-align: left;
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
            font-weight: bold;
            background-color: #c7d9ff;
            text-align: left;
        }

        small.sendert {
            display: flex;
            justify-content: flex-end;
            font-weight: 100;
        }


        small.receivert {
            display: flex;
            justify-content: flex-end;
            font-weight: 100;
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

        .badge {
            background-color: green;
            color: white;
            padding: 3px 6px;
            border-radius: 50%;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>

    <script>
        document.getElementById('search-user').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const users = document.querySelectorAll('#user-list .user-item');

            users.forEach(function(user) {
                const userName = user.textContent.trim().toLowerCase();

                if (userName.includes(searchText)) {
                    user.style.display = ''; // Show the user if it matches the search text
                } else {
                    user.style.display = 'none'; // Hide the user if it doesn't match
                }
            });
        });

        $(document).ready(function() {
            let eventSourceMessages;
            let eventSourceUsers;
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            });

            function loadMessages(receiverId) {
                if (!receiverId) return;

                if (eventSourceMessages) {
                    eventSourceMessages.close();
                }

                eventSourceMessages = new EventSource(`/messages/${receiverId}`);
                eventSourceMessages.onmessage = function(event) {
                    const messages = JSON.parse(event.data);
                    $('#messages').html('');
                    const sclass = ["sender", "sendert"];
                    const rclass = ["receiver", "receivert"];

                    $.each(messages, function(index, message) {
                        const isSender = (message.sender === '{{ auth()->user()->name }}');
                        const messageClass = isSender ? sclass : rclass;

                        let statusIcon = '';
                        if (isSender) {
                            if (message.is_read) {
                                statusIcon = '✓✓'; // Double check for read
                            } else if (message.is_delivered) {
                                statusIcon = '✓'; // Single check for delivered
                            }
                        }

                        // Prepare message content
                        let messageContent = '';
                        if (message.message) {
                            messageContent += message.message;
                        }

                        // Handle attachment if it exists
                        if (message.attachment) {
                            const fileExtension = message.attachment.split('.').pop().toLowerCase();
                            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                                // Display image directly
                                messageContent +=
                                    `<br><img src="${message.attachment}" alt="Attachment" style="max-width: 100px;">`;
                            } else {
                                // Display file download link
                                messageContent +=
                                    `<br><a href="/storage/${message.attachment}" target="_blank">Download Attachment</a>`;
                            }
                        }

                        // If both messageContent and attachment are empty, don't show 'null'
                        if (!messageContent.trim()) {
                            messageContent = '(Attachment only)';
                        }

                        // Append message to chat
                        $('#messages').append(
                            `<div class="message ${messageClass[0]}"> 
                            ${messageContent} 
                            <small class="${messageClass[1]}">
                                ${isSender ? `<span class="status">${statusIcon} &nbsp;</span>` : ''}
                                ${message.created_at.split(' ')[1]}
                            </small>
                        </div>`
                        );
                    });

                    // Scroll to the bottom of the chat container
                    const messagesDiv = $('#messages');
                    // messagesDiv.scrollTop(messagesDiv[0].scrollHeight);
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
                        const userItem = $(`#user-list .user-item[data-id="${user.id}"]`);
                        const lastSeenTime = new Date(user.last_seen);
                        const timeDifference = currentTime - lastSeenTime;
                        const isActive = timeDifference <= 60000 ? 'green' : 'gray';
                        const unreadBadge = user.unread_count > 0 ?
                            `<span class="badge">${user.unread_count}</span>` : '';

                        if (userItem.length > 0) {
                            userItem.find('.status-dot').css('background-color', isActive);
                            userItem.attr("last-seen", user.last_seen);
                            userItem.find('.badge').remove();
                            userItem.append(unreadBadge);
                        } else {
                            $('#user-list ul').append(`
                                <li class="list-group-item user-item" data-id="${user.id}" data-lastseen="${user.last_seen}" style="display: flex; align-items: center;">
                                   ${user.name}  <span class="status-dot" style="background-color: ${isActive};"></span> &nbsp;
                                     ${unreadBadge}
                                </li>`);
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
                // Mark messages as read when the chat is opened
                $.post('/messages/read', {
                        sender_id: receiverId
                    })
                    .done(function() {
                        console.log('Messages marked as read');
                    })
                    .fail(function() {
                        console.error('Failed to mark messages as read');
                    });
                loadMessages(receiverId);
            });

            // Initialize SSE connections
            loadUsersSSE();
        });

        $(document).ready(function() {
            // Handle file selection
            $('#attachment').change(function(e) {
                const file = e.target.files[0];
                if (file) {
                    $('#attachment-preview').show();
                    $('#preview-filename').text(file.name);
                    $('#message').prop('disabled', false);
                    $('#send').prop('disabled', false);

                    // Show image preview if it's an image file
                    const fileReader = new FileReader();
                    fileReader.onload = function(e) {
                        const fileType = file.type.split('/')[0];
                        if (fileType === 'image') {
                            $('#preview-image').attr('src', e.target.result).show();
                        } else {
                            $('#preview-image').hide();
                        }
                    };
                    fileReader.readAsDataURL(file);
                }
            });

            // Clear attachment preview when close button is clicked
            $('#preview-close').click(function() {
                $('#attachment').val('');
                $('#attachment-preview').hide();
                $('#preview-image').hide();
                $('#preview-filename').text('');
                $('#message').prop('disabled', true);
                $('#send').prop('disabled', true);
            });

            // Send button click handler
            $('#send').click(function() {
                const receiverId = $('.user-item.active').data('id');
                const message = $('#message').val();
                const attachment = $('#attachment')[0].files[0];

                if (receiverId && (message || attachment)) {
                    const formData = new FormData();
                    formData.append('receiver_id', receiverId);
                    formData.append('message', message);
                    if (attachment) {
                        formData.append('attachment', attachment);
                    }
                    formData.append('_token', '{{ csrf_token() }}');

                    $.ajax({
                        url: '/send-message',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function() {
                            $('#message').val('');
                            $('#attachment').val('');
                            $('#attachment-preview').hide();
                            $('#preview-image').hide();
                            $('#preview-filename').text('');
                            loadMessages(receiverId);
                        },
                        error: function(xhr, status, error) {
                            alert('An error occurred while sending the message.');
                        }
                    });
                } else {
                    alert('Please select a receiver and enter a message or attachment.');
                }
            });

            // Handle Enter key press for sending message
            $('#message').keypress(function(event) {
                if (event.which == 13) {
                    $('#send').click();
                }
            });
        });
    </script>
@endsection
