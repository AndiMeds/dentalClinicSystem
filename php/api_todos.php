<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

// Determine action
$action = $_GET['action'] ?? '';

// Retrieve staff ID, preferring session if available
session_start();
$staff_id = $_SESSION['staff_id'] ?? $_GET['staff_id'] ?? null;

if (!$staff_id) {
    echo json_encode(["success" => false, "error" => "Staff ID is required"]);
    exit;
}

switch ($action) {
    case 'getTodos':
        // Fetch all to-do items for the specified staff
        $stmt = $conn->prepare("SELECT * FROM todos WHERE staff_id = ?");
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $todos = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(["success" => true, "todos" => $todos]);
        break;

    case 'addTodo':
        // Add a new to-do item for the staff member
        $task = $_POST['task'] ?? '';
        if ($task) {
            $stmt = $conn->prepare("INSERT INTO todos (staff_id, task) VALUES (?, ?)");
            $stmt->bind_param("is", $staff_id, $task);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "todo_id" => $stmt->insert_id, "message" => "Task added successfully"]);
            } else {
                echo json_encode(["success" => false, "error" => "Failed to add task: " . $stmt->error]);
            }
        } else {
            echo json_encode(["success" => false, "error" => "Task content is required"]);
        }
        break;

    case 'updateTodo':
        // Update completion status of a to-do item
        $todo_id = $_POST['todo_id'] ?? null;
        $completed = $_POST['completed'] ?? null;
        if ($todo_id && isset($completed)) {
            $stmt = $conn->prepare("UPDATE todos SET completed = ? WHERE todo_id = ? AND staff_id = ?");
            $stmt->bind_param("iii", $completed, $todo_id, $staff_id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Task updated successfully"]);
            } else {
                echo json_encode(["success" => false, "error" => "Failed to update task: " . $stmt->error]);
            }
        } else {
            echo json_encode(["success" => false, "error" => "Todo ID and completion status are required"]);
        }
        break;

    case 'toggleComplete':
        // Toggle completion status of a to-do item
        $todo_id = $_POST['todo_id'] ?? null;
        $completed = $_POST['completed'] ?? null;
        if ($todo_id && isset($completed)) {
            $stmt = $conn->prepare("UPDATE todos SET completed = ? WHERE todo_id = ? AND staff_id = ?");
            $stmt->bind_param("iii", $completed, $todo_id, $staff_id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Task completion status toggled successfully"]);
            } else {
                echo json_encode(["success" => false, "error" => "Failed to toggle task: " . $stmt->error]);
            }
        } else {
            echo json_encode(["success" => false, "error" => "Todo ID and completion status are required"]);
        }
        break;

    case 'deleteTodo':
        // Delete a specific to-do item
        $todo_id = $_POST['todo_id'] ?? null;
        if ($todo_id) {
            $stmt = $conn->prepare("DELETE FROM todos WHERE todo_id = ? AND staff_id = ?");
            $stmt->bind_param("ii", $todo_id, $staff_id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Task deleted successfully"]);
            } else {
                echo json_encode(["success" => false, "error" => "Failed to delete task: " . $stmt->error]);
            }
        } else {
            echo json_encode(["success" => false, "error" => "Todo ID is required"]);
        }
        break;

    default:
        echo json_encode(["success" => false, "error" => "Invalid action specified"]);
        break;
}

$conn->close();
