<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/calendar.css">
    <title>Appointment Calendar</title>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />
    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://apis.google.com/js/api.js"></script>

</head>

<body>
    <div class="container">
        <div class="home_content">
            <div class="sidenav">
                <?php include "sidenav.html"; ?>
            </div>
            <!-- Calendar Section -->
            <div id="calendar"></div>

            <!-- To-Do List Sidebar -->
            <div class="todo-sidebar">
                <h1>TO - DO LIST</h1>
                <div class="todo-form">
                    <input type="text" id="todoInput" placeholder="Add a new task...">
                    <button type="button" id="addTodoButton">Add Task</button>
                </div>
                <ul id="todoList" class="todo-list"></ul>
            </div>

            <!-- Loading Overlay -->
            <div class="loading-overlay">
                <div class="spinner"></div>
            </div>


            <!-- Edit Appointment Modal -->
            <div id="editAppointmentModal" class="modal">
                <div class="modal-content">
                    <h2>APPOINTMENT DETAILS</h2>
                    <div id="appointmentDetails" class="appointment-details"></div>
                    <form id="appointmentForm">
                        <input type="hidden" id="appointmentId">
                        <div class="form-group">
                            <label class="form-label" for="remarks">Remarks</label>
                            <textarea id="remarks" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" class="form-control">
                                <option value="">Select Status</option>
                                <option value="pending">PENDING</option>
                                <option value="confirmed">CONFIRMED</option>
                                <option value="completed">COMPLETED</option>
                                <option value="cancelled">CANCELLED</option>
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="button" class="close-modal">Cancel</button>
                            <button type="submit">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="/js/calendar.js"></script>

    <script>
        $(document).ready(function () {
            loadTodos();

            // Load to-do items
            function loadTodos() {
                $.ajax({
                    url: '/php/api_todos.php?action=getTodos',
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        const todoList = $('#todoList');
                        todoList.empty();
                        response.todos.forEach(todo => {
                            const listItem = createTodoElement(todo);
                            todoList.append(listItem);
                        });
                    },
                    error: function (error) {
                        console.error('Error loading todos:', error);
                    }
                });
            }

            // Add a new to-do
            function addTodo() {
                const task = $('#todoInput').val().trim();
                if (task === "") return;

                $.ajax({
                    url: '/php/api_todos.php?action=addTodo',
                    type: 'POST',
                    data: { task },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            loadTodos();
                            $('#todoInput').val('');
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function (error) {
                        console.error('Error adding todo:', error);
                    }
                });
            }

            // Bind the Add Task button to the addTodo function
            $('#addTodoButton').on('click', addTodo);

            // Toggle task completion
            function toggleComplete(todo_id, completed) {
                $.ajax({
                    url: '/php/api_todos.php?action=toggleComplete',
                    type: 'POST',
                    data: { todo_id, completed },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            loadTodos();
                        } else {
                            console.error(response.message);
                        }
                    },
                    error: function (error) {
                        console.error('Error updating todo:', error);
                    }
                });
            }

            // Delete a to-do item
            function deleteTodo(todo_id) {
                $.ajax({
                    url: '/php/api_todos.php?action=deleteTodo',
                    type: 'POST',
                    data: { todo_id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            loadTodos();
                        } else {
                            console.error(response.message);
                        }
                    },
                    error: function (error) {
                        console.error('Error deleting todo:', error);
                    }
                });
            }

            // Helper to create a to-do list item element
            function createTodoElement(todo) {
                const listItem = $('<li>').addClass('todo-item').attr('data-id', todo.todo_id);

                const checkbox = $('<input type="checkbox">')
                    .prop('checked', todo.completed == 1)
                    .on('change', function () {
                        toggleComplete(todo.todo_id, this.checked ? 1 : 0);
                    });

                const taskText = $('<span>').text(todo.task).toggleClass('completed', todo.completed == 1);

                const deleteButton = $('<button>').addClass('delete-btn').text('DELETE').css({
                    fontSize: '12px',
                    padding: '2px 5px',
                    lineHeight: '1'
                }).on('click', function () {
                    deleteTodo(todo.todo_id);
                });



        listItem.append(checkbox, taskText, deleteButton);
        return listItem;
                }
        function toggleComplete(todo_id, completed) {
            $.ajax({
                url: '/php/api_todos.php?action=toggleComplete',
                type: 'POST',
                data: { todo_id, completed },
                dataType: 'json',
                success: function (response) {
                    console.log("Server response:", response); // Log the entire response
                    if (response && response.success) {
                        loadTodos();
                    } else {
                        console.error("Error: Success status not received or undefined");
                    }
                },
                error: function (error) {
                    console.error('Error updating todo:', error);
                }
            });
        }

            });
    </script>

</body>

</html>