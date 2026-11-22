<?php

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
// Include database connection
include "conn.php";

// Fetch donations from the database
$donationsQuery = "SELECT * FROM donations";
$donationsResult = $conn->query($donationsQuery);

// Fetch donation requests from the database
$requestsQuery = "SELECT * FROM donation_requests";
$requestsResult = $conn->query($requestsQuery);

// Fetch contact messages from the database
$contactMessagesQuery = "SELECT * FROM contact_messages";
$contactMessagesResult = $conn->query($contactMessagesQuery);
// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle secure record deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid CSRF token.");
    }

    $deleteId = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
    $deleteType = $_POST['type'] ?? '';

    $allowedTables = [
        'donation' => 'donations',
        'request' => 'donation_requests',
        'message' => 'contact_messages'
    ];

    if ($deleteId && isset($allowedTables[$deleteType])) {
        $table = $allowedTables[$deleteType];

        $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: details.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Details - Charity Management System</title>
    <!-- Font Awesome CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('care.jpg'); /* Path to the image */
            background-size: cover; /* Ensures the image covers the whole page */
            background-position: center; /* Centers the image */
            background-repeat: no-repeat; /* Prevents the image from repeating */
            color: #555;
        }

        header {
            background-color: #f44336;
            color: white;
            padding: 1.5rem 0;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem 2rem;
        }

        header h1 {
            color: white; /* Change font color to white */
            text-align: center;
            font-size: 2.5rem;
            margin-top: 20px;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            padding: 2rem;
            background-color: #fff;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-top: 30px; /* Add space between header and container */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
        }

        th, td {
            padding: 1rem;
            text-align: center; /* Center align all table cells */
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #e53935;
            color: white;
            text-transform: uppercase;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .btn {
            background-color: #e53935;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            font-size: 0.9rem;
            border-radius: 5px;
            text-align: center;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #d32f2f;
        }

        .back-btn {
            background-color: #ff9800; /* Changed to orange color */
            display: inline-block;
            margin-top: 20px;
            text-align: center;
            padding: 10px 20px;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1rem;
            transition: background-color 0.3s, transform 0.3s;
        }

        .back-btn:hover {
            background-color: #f57c00; /* Darker orange on hover */
            transform: scale(1.05); /* Slightly scale the button on hover */
        }

        .table-header {
            margin-bottom: 20px;
        }

        h2 {
            font-size: 1.5rem;
            color: #333;
        }

        .no-data {
            text-align: center;
            color: #888;
            font-style: italic;
            padding: 10px 0;
        }

        .bold-text {
            font-weight: bold;
        }

        .action-btns {
            display: flex;
            justify-content: center; /* Center align buttons */
            gap: 10px; /* Add spacing between buttons */
        }
    </style>
</head>
<body>
<header>
  <img src="logo2.png" alt="" width="100px" height="100px">
  <h1>Raising Hope Charity Management Foundation&#174;</h1>
</header>

<div class="container">
    <h1>Details</h1>

    <!-- Donation Table -->
    <div class="table-header">
        <h2>Donation Details</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Campaign</th>
                <th>Amount ($)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($donationsResult->num_rows > 0) {
                while ($row = $donationsResult->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['email']) . "</td>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['campaign']) . "</td>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['amount']) . "</td>";
                    echo "<td>
                            <div class='action-btns'>
                                <a href='edit.php?id=" . $row['id'] . "&type=donation' class='btn'>
                                    <i class='fa-solid fa-pen-to-square'></i> Edit
                                </a>
                                <form method='POST' action='details.php' style='display:inline;'>
    <input type='hidden' name='delete_id' value='" . (int)$row['id'] . "'>
    <input type='hidden' name='type' value='donation'>
    <input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . "'>
    <button type='submit' name='delete' class='btn'>
        <i class='fa-solid fa-trash'></i> Delete
    </button>
</form>
                            </div>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='no-data'>No donations found</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Donation Request Table -->
    <div class="table-header">
        <h2>Donation Request Details</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Category</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($requestsResult->num_rows > 0) {
                while ($row = $requestsResult->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['email']) . "</td>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['phone']) . "</td>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['category']) . "</td>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['description']) . "</td>";
                    
                    echo "<td>
                            <div class='action-btns'>
                                <a href='edit.php?id=" . $row['id'] . "&type=request' class='btn'>
                                    <i class='fa-solid fa-pen-to-square'></i> Edit
                                </a>
                                <form method='POST' action='details.php' style='display:inline;'>
    <input type='hidden' name='delete_id' value='" . (int)$row['id'] . "'>
    <input type='hidden' name='type' value='request'>
    <input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . "'>
    <button type='submit' name='delete' class='btn'>
        <i class='fa-solid fa-trash'></i> Delete
    </button>
</form>
                            </div>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='no-data'>No donation requests found</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Contact Message Table -->
    <div class="table-header">
        <h2>Contact Messages</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($contactMessagesResult->num_rows > 0) {
                while ($row = $contactMessagesResult->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['email']) . "</td>";
                    echo "<td class='bold-text'>" . htmlspecialchars($row['message']) . "</td>";
                    echo "<td>
                            <div class='action-btns'>
                                <a href='edit.php?id=" . $row['id'] . "&type=message' class='btn'>
                                    <i class='fa-solid fa-pen-to-square'></i> Edit
                                </a>
                                <form method='POST' action='details.php' style='display:inline;'>
    <input type='hidden' name='delete_id' value='" . (int)$row['id'] . "'>
    <input type='hidden' name='type' value='message'>
    <input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . "'>
    <button type='submit' name='delete' class='btn'>
        <i class='fa-solid fa-trash'></i> Delete
    </button>
</form>
                            </div>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4' class='no-data'>No contact messages found</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Back Button -->
    <a href="login.php" class="btn back-btn">Back</a>
</div>

</body>
</html>

<?php
$conn->close();
?>